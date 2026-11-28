<?php
declare(strict_types=1);

/** Global category catalog: staff need the existing event operations permission. */
function eventCategoryCanManage(array $roles,array $permissions=[]): bool {
 return in_array('admin',$roles,true)||(in_array('staff',$roles,true)&&in_array('operate',$permissions,true));
}
function eventCategoryAccess(?array $u): bool {
 if($u===null)return false;
 if(in_array('admin',$u['roles'],true))return true;
 return eventCategoryCanManage($u['roles'],in_array('staff',$u['roles'],true)?staffPermissions($u):[]);
}
function eventCategoryRequireManager(): array {
 $u=requireLogin();must(eventCategoryAccess($u),'Only administrators and authorised event operations staff can manage event categories.');return $u;
}
function eventCategoryReady(): bool {
 static $ready=null;
 if($ready!==null)return $ready;
 // Permit the legacy event forms until the coordinated catalog migration completes.
 if(!(int)scalar("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='schema_migrations'"))return $ready=false;
 return $ready=(bool)scalar("SELECT COUNT(*) FROM schema_migrations WHERE RIGHT(version, LENGTH('_event_category_seed.sql'))='_event_category_seed.sql' AND status='applied'");
}
function eventCategoryIcons(): array {
 return ['tag'=>'◇ General','music'=>'♫ Music','mic'=>'♩ Conference','image'=>'▧ Exhibition','business'=>'▣ Corporate','education'=>'✎ Education','sport'=>'★ Sports','people'=>'♟ Convention','culture'=>'✦ Culture'];
}
function eventCategoryIcon(string $key): string {return explode(' ',eventCategoryIcons()[$key]??eventCategoryIcons()['tag'],2)[0];}
function eventCategoryValidate(array $data): array {
 foreach(['name','description','icon','active'] as $key)must(isset($data[$key])&&is_string($data[$key]),'Invalid category information.');
 $name=trim($data['name']);$desc=trim($data['description']);$icon=$data['icon'];
 must($name!==''&&strlen($name)<=80&&preg_match('//u',$name)===1&&!preg_match('/[\x00-\x1f\x7f]/',$name),'Enter a valid category name (up to 80 UTF-8 bytes).');
 must($desc!==''&&strlen($desc)<=500&&preg_match('//u',$desc)===1&&!preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/',$desc),'Enter a description (up to 500 UTF-8 bytes).');
 must(isset(eventCategoryIcons()[$icon]),'Select a valid category icon.');
 must(in_array($data['active'],['0','1'],true),'Select a valid category status.');
 return ['name'=>$name,'description'=>$desc,'icon'=>$icon,'active'=>(int)$data['active']];
}
function eventCategoryValidateImage(string $bytes): string {
 must(strlen($bytes)>0&&strlen($bytes)<=512*1024,'Image must be 512 KB or smaller.');
 $info=@getimagesizefromstring($bytes);
 must(is_array($info)&&in_array($info['mime']??'',['image/jpeg','image/png','image/webp'],true),'Use a JPG, PNG or WebP image.');
 must($info[0]>0&&$info[1]>0&&$info[0]<=2048&&$info[1]<=2048,'Image dimensions must be at most 2048 × 2048 pixels.');
 $mime=(new finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
 must($mime===$info['mime'],'Invalid image contents.');return $mime;
}
function eventCategoryUpload(): ?array {
 $file=$_FILES['category_image']??null;
 if($file===null||($file['error']??null)===UPLOAD_ERR_NO_FILE)return null;
 must(($file['error']??null)===UPLOAD_ERR_OK&&is_string($file['tmp_name']??null)&&is_uploaded_file($file['tmp_name']),'Image upload failed. Choose a smaller image and try again.');
 $size=filesize($file['tmp_name']);must($size!==false&&$size<=512*1024,'Image must be 512 KB or smaller.');
 $bytes=file_get_contents($file['tmp_name']);must(is_string($bytes),'Could not read uploaded image.');
 return ['mime'=>eventCategoryValidateImage($bytes),'bytes'=>$bytes];
}
/** The legacy tag is the compatibility key. All category comparisons use the catalog collation. */
function eventCategoryMatch(string $field): string {
 // Field expressions below are hard-coded by callers, never request data.
 return "CONVERT(TRIM($field) USING utf8mb4) COLLATE utf8mb4_unicode_ci";
}
function eventCategoryByName(string $name,bool $lock=false): ?array {
 return one('SELECT id,name,active FROM event_categories WHERE name=?'.($lock?' FOR UPDATE':''),[$name]);
}
function eventCategoryAssignmentAllowed(array $category,?int $previousId): bool {
 return (int)$category['active']===1||($previousId!==null&&(int)$category['id']===$previousId);
}
/** Called inside the same transaction as event/application writes. Retain existing inactive associations only. */
function eventCategoryPostedTag(?string $previous=null): string {
 if(!eventCategoryReady())return req('category_tag',80);
 $id=positive('event_category_id',PHP_INT_MAX);
 $category=one('SELECT id,name,active FROM event_categories WHERE id=? FOR UPDATE',[$id]);
 $prior=$previous!==null?eventCategoryByName(trim($previous)):null;
 must($category!==null&&eventCategoryAssignmentAllowed($category,$prior?(int)$prior['id']:null),'Select an active event category. An inactive category can only be retained on an existing record.');
 return $category['name'];
}
function eventCategoryApprovalTag(string $tag): string {
 if(!eventCategoryReady())return $tag;
 $category=eventCategoryByName(trim($tag),true);
 must($category!==null&&(int)$category['active']===1,'The proposal category is no longer active. Request a revision before approving.');
 return $category['name'];
}
function eventCategorySelect(?string $previous=null): void {
 if(!eventCategoryReady()){field('category_tag','Event category (tag)','text',$previous??'');return;}
 $prior=$previous!==null?eventCategoryByName(trim($previous)):null;
 $rows=all('SELECT id,name,icon,active FROM event_categories WHERE active=1 OR id=? ORDER BY name',[$prior['id']??0]);
 echo '<div class="mb-3"><label class="form-label" for="eventCategorySelection">Event category <span aria-hidden="true">*</span></label><select class="form-select" name="event_category_id" id="eventCategorySelection" required><option value="">Choose an event category</option>';
 foreach($rows as $r)echo '<option value="'.(int)$r['id'].'"'.((int)$r['id']===(int)($prior['id']??0)?' selected':'').'>'.e(eventCategoryIcon($r['icon']).' '.$r['name'].(!$r['active']?' (inactive — existing association)':'')).'</option>';
 echo '</select><div class="form-text">Choose one category. This is the event type, not the ticket price category.</div></div>';
}
/** Keep legacy event, application and recommendation references consistent on rename. */
function eventCategoryRenameReferences(string $old,string $new): void {
 if($old===$new)return;
 foreach(['events','event_applications','user_preferences'] as $table){
  q('UPDATE '.$table.' SET category_tag=? WHERE '.eventCategoryMatch('category_tag').'=?',[$new,$old]);
 }
}
function eventCategoryHandle(): void {
 $action=$_POST['action']??'';if(!in_array($action,['event_category_create','event_category_update'],true))return;
 $u=eventCategoryRequireManager();must(eventCategoryReady(),'Event category migration is not ready. Contact the database owner.');
 $data=eventCategoryValidate($_POST);$upload=eventCategoryUpload();$remove=isset($_POST['remove_image']);
 must(!($upload&&$remove),'Choose either a replacement image or remove the current image.');
 $id=$action==='event_category_update'?positive('id',PHP_INT_MAX):0;
 try {
  tx(function()use($u,$data,$upload,$remove,$id){
   $old=$id?one('SELECT id,name,active FROM event_categories WHERE id=? FOR UPDATE',[$id]):null;
   must(!$id||$old!==null,'Event category not found.');
   $duplicate=eventCategoryByName($data['name']);must(!$duplicate||(int)$duplicate['id']===$id,'A category with this name already exists.');
   if(!$id){
    q('INSERT INTO event_categories(name,description,icon,active,image_mime,image_data,created_by,updated_by) VALUES(?,?,?,?,?,?,?,?)',[$data['name'],$data['description'],$data['icon'],$data['active'],$upload['mime']??null,$upload['bytes']??null,$u['id'],$u['id']]);
    audit('event_category_create','event_categories',(int)db()->lastInsertId(),$data['name']);
   }else{
    $sql='UPDATE event_categories SET name=?,description=?,icon=?,active=?,updated_by=?';$params=[$data['name'],$data['description'],$data['icon'],$data['active'],$u['id']];
    if($upload||$remove){$sql.=',image_mime=?,image_data=?';$params[]=$upload['mime']??null;$params[]=$upload['bytes']??null;}
    $params[]=$id;q($sql.' WHERE id=?',$params);
    eventCategoryRenameReferences($old['name'],$data['name']);
    audit('event_category_update','event_categories',$id,$old['name'].' → '.$data['name'].'; status '.($data['active']?'active':'inactive'));
   }
  });
 }catch(PDOException $ex){if(($ex->errorInfo[1]??null)===1062)throw new DomainException('A duplicate category or legacy interest prevents this change. No changes were saved.');throw $ex;}
 message($id?'Event category updated.':'Event category created.');go('event_category');
}
/** Raster images only; bytes live in the shared DB, not an individual developer's filesystem. */
function eventCategoryImage(): never {
 $id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
 $row=$id&&eventCategoryReady()?one('SELECT image_mime,image_data FROM event_categories WHERE id=?',[$id]):null;
 if(!$row||!in_array($row['image_mime'],['image/jpeg','image/png','image/webp'],true)||!is_string($row['image_data'])){http_response_code(404);exit;}
 header('Content-Type: '.$row['image_mime']);header('X-Content-Type-Options: nosniff');header("Content-Security-Policy: default-src 'none'; sandbox");header('Cache-Control: no-store');header('Content-Length: '.strlen($row['image_data']));echo $row['image_data'];exit;
}
function eventCategoryFilters(array $input): array {
 $search=is_string($input['search']??null)?trim($input['search']):'';
 if(strlen($search)>80||preg_match('//u',$search)!==1)$search='';
 $status=is_string($input['status']??null)&&in_array($input['status'],['active','inactive'],true)?$input['status']:'all';
 return ['search'=>$search,'status'=>$status];
}
function eventCategoryView(): void {
 eventCategoryRequireManager();
 if(!eventCategoryReady()){echo '<div class="alert alert-info">Event Category Management is waiting for the approved database migration. Existing ticket categories are unchanged.</div>';return;}
 $f=eventCategoryFilters($_GET);$where=[];$p=[];
 if($f['search']!==''){
  $where[]="(LOCATE(?,c.name)>0 OR LOCATE(?,c.description)>0 OR LOCATE(?,CONCAT('EC',LPAD(c.id,GREATEST(3,CHAR_LENGTH(c.id)),'0')))>0)";
  $p=array_fill(0,3,$f['search']);
 }
 if($f['status']!=='all'){$where[]='c.active=?';$p[]=$f['status']==='active'?1:0;}
 $match=eventCategoryMatch('e.category_tag');
 $rows=all('SELECT c.id,c.name,c.description,c.icon,c.active,c.image_mime,c.created_at,c.updated_at,(SELECT COUNT(*) FROM events e WHERE '.$match.'=c.name) AS event_count FROM event_categories c'.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY c.name',$p);
 $stats=one('SELECT COUNT(*) total,COALESCE(SUM(active=1),0) active FROM event_categories');
 $editId=(int)($_GET['edit']??0);$editing=$editId?one('SELECT id,name,description,icon,active,image_mime FROM event_categories WHERE id=?',[$editId]):null;
 must(!$editId||$editing!==null,'Event category not found.');
 echo '<section class="ec-page"><div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><div><h2 class="h5 mb-1">Organise your SICC events</h2><p class="text-muted mb-0">Manage event types separately from ticket prices. Deactivation preserves existing events.</p></div><a class="btn btn-primary" href="'.e(url('event_category',['new'=>1])).'#category-form">+ Add category</a></div><div class="row g-3 mb-4">';
 foreach(['Total categories'=>(int)$stats['total'],'Active'=>(int)$stats['active'],'Inactive'=>(int)$stats['total']-(int)$stats['active']] as $label=>$count)echo '<div class="col-4"><div class="ec-stat"><span>'.e($label).'</span><strong>'.$count.'</strong></div></div>';
 echo '</div>';
 if($editing||isset($_GET['new']))eventCategoryForm($editing);
 echo '<section class="card shadow-sm mb-4"><div class="card-body"><form method="get" class="row g-2 align-items-end mb-3"><input type="hidden" name="page" value="event_category"><div class="col-md-7"><label for="ec-search" class="form-label">Search categories</label><input id="ec-search" class="form-control" name="search" maxlength="80" type="search" placeholder="Name, description or ID" value="'.e($f['search']).'"></div><div class="col-md-3"><label class="form-label" for="ec-status">Status</label><select id="ec-status" name="status" class="form-select">';
 foreach(['all'=>'All statuses','active'=>'Active','inactive'=>'Inactive'] as $key=>$label)echo '<option value="'.$key.'"'.($key===$f['status']?' selected':'').'>'.$label.'</option>';
 echo '</select></div><div class="col-md-2 d-flex gap-1"><button class="btn btn-primary">Search</button><a class="btn btn-outline-secondary" href="'.e(url('event_category')).'">Clear</a></div></form>';
 if(!$rows)echo '<div class="text-center p-4"><h3 class="h6">No categories found</h3><p class="text-muted">Try another search or add a new event category.</p></div>';
 else{
  echo '<div class="table-responsive"><table class="table align-middle ec-table"><thead><tr><th>Category</th><th>Events</th><th>Status</th><th>Action</th></tr></thead><tbody>';
  foreach($rows as $r){$id=(int)$r['id'];$visual=$r['image_mime']?'<img class="ec-thumb" src="'.e(url('event_category_image',['id'=>$id])).'" alt="" loading="lazy">':'<span class="ec-icon" aria-hidden="true">'.e(eventCategoryIcon($r['icon'])).'</span>';
   echo '<tr><td><div class="d-flex align-items-center gap-3">'.$visual.'<div><strong>'.e($r['name']).'</strong><div class="small text-muted">EC'.str_pad((string)$id,3,'0',STR_PAD_LEFT).' · '.e(eventCategoryIcon($r['icon'])).'</div><div class="small ec-desc">'.e($r['description']).'</div></div></div></td><td><a href="'.e(url('event_category',['view'=>$id])).'#category-events">'.(int)$r['event_count'].' events</a></td><td>'.b($r['active']?'Active':'Inactive',$r['active']?'success':'secondary').'</td><td><a class="btn btn-sm btn-outline-primary" href="'.e(url('event_category',['edit'=>$id])).'#category-form">Edit</a></td></tr>';
  }echo '</tbody></table></div>';
 }echo '</div></section>';
 if(isset($_GET['view'])){
  $cat=one('SELECT id,name FROM event_categories WHERE id=?',[(int)$_GET['view']]);must($cat!==null,'Event category not found.');
  $events=all('SELECT title,status FROM events WHERE '.eventCategoryMatch('category_tag').'=? ORDER BY title',[$cat['name']]);
  echo '<section class="card p-3 mb-4" id="category-events"><h3 class="h5">Events in '.e($cat['name']).'</h3>';if(!$events)echo '<p>No events assigned.</p>';else tbl(['Event','Status'],array_map(fn($r)=>[e($r['title']),b($r['status'])],$events));echo '</section>';
 }
 if($editing){$history=all("SELECT a.action,a.details,a.created_at,u.full_name FROM audit_logs a LEFT JOIN users u ON u.id=a.actor_id WHERE a.entity='event_categories' AND a.entity_id=? ORDER BY a.id DESC LIMIT 20",[$editId]);echo '<section class="card p-3"><h3 class="h5">Recent activity</h3>';tbl(['Action','Changed by','Date','Details'],array_map(fn($r)=>[e($r['action']),e($r['full_name']??'System'),e($r['created_at']),e($r['details'])],$history));echo '</section>';}
 echo '</section>';
}
function eventCategoryForm(?array $row): void {
 $v=$row??['id'=>0,'name'=>'','description'=>'','icon'=>'tag','active'=>1,'image_mime'=>null];
 echo '<section class="card shadow-sm mb-4" id="category-form"><div class="card-body"><h3 class="h5">'.($row?'Edit event category':'Create event category').'</h3><form method="post" enctype="multipart/form-data" action="'.e(url('event_category')).'">'.csrfInput().'<input type="hidden" name="action" value="'.($row?'event_category_update':'event_category_create').'">';if($row)echo '<input type="hidden" name="id" value="'.(int)$v['id'].'">';
 field('name','Category name *','text',$v['name'],'required maxlength="80"');
 echo '<div class="mb-3"><label for="ec-description" class="form-label">Description *</label><textarea id="ec-description" class="form-control" name="description" required maxlength="500" rows="3">'.e($v['description']).'</textarea></div>';
 choices('icon','Category icon',eventCategoryIcons(),$v['icon']);choices('active','Status',['1'=>'Active','0'=>'Inactive'],$v['active']);
 if($v['image_mime'])echo '<div class="mb-2"><img class="ec-preview" src="'.e(url('event_category_image',['id'=>$v['id']])).'" alt="Current category image"><label class="ms-2"><input type="checkbox" name="remove_image" value="1"> Remove current image</label></div>';
 echo '<div class="mb-3"><label class="form-label" for="ec-image">Optional category image</label><input id="ec-image" class="form-control" name="category_image" type="file" accept="image/jpeg,image/png,image/webp"><div class="form-text">JPG, PNG or WebP · maximum 512 KB and 2048 × 2048 pixels.</div></div><p class="small text-muted">Inactive categories cannot be selected for new events. Existing records are retained.</p><button class="btn btn-primary">Save category</button> <a class="btn btn-outline-secondary" href="'.e(url('event_category')).'">Cancel</a></form></div></section>';
}
