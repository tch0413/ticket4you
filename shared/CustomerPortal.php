<?php
declare(strict_types=1);
/** Own the customer card bookmark action; do not alter Event Management's CRUD handler. */
function customerPortalHandle(): void {
    if (!posted('customer_favourite')) return;
    $u = requireRole('customer');
    must((bool)$u['verified_at'], 'Verify your email before saving events.');
    $eventId = positive('event_id');
    must((bool)scalar("SELECT COUNT(*) FROM events e WHERE e.id=? AND e.status='published' AND (e.visible_before_sales=1 OR e.sales_start<=NOW()) AND EXISTS(SELECT 1 FROM event_sessions s WHERE s.event_id=e.id AND s.status='scheduled' AND s.start_at>NOW())",[$eventId]), 'Event is no longer available.');
    tx(function() use($u,$eventId): void {
        $existing=one('SELECT event_id FROM favorites WHERE user_id=? AND event_id=?',[$u['id'],$eventId]);
        if($existing)q('DELETE FROM favorites WHERE user_id=? AND event_id=?',[$u['id'],$eventId]);
        else q('INSERT IGNORE INTO favorites(user_id,event_id) VALUES(?,?)',[$u['id'],$eventId]);
    });
    $return=(string)($_POST['return_to']??'customer_events');
    if(!in_array($return,['dashboard','customer_events','customer_favorites'],true))$return='customer_events';
    go($return);
}
/** Customer-only screens. All queries scope personal records to the authenticated user. */
function customerPortalView(string $page): void {
    $u = requireRole('customer');
    switch ($page) {
        case 'dashboard':
            $upcoming = (int)scalar("SELECT COUNT(DISTINCT o.id) FROM booking_orders o JOIN event_sessions s ON s.id=o.session_id WHERE o.user_id=? AND o.status='paid' AND s.start_at>NOW()", [$u['id']]);
            $tickets = (int)scalar("SELECT COUNT(*) FROM tickets t JOIN booking_items bi ON bi.id=t.booking_item_id JOIN booking_orders o ON o.id=bi.order_id WHERE o.user_id=? AND o.status='paid' AND t.status='valid'", [$u['id']]);
            $favourites = (int)scalar('SELECT COUNT(*) FROM favorites WHERE user_id=?', [$u['id']]);
            $first = trim(explode(' ', trim((string)$u['full_name']))[0] ?? '');
            echo '<section class="customer-welcome"><div><div class="customer-welcome-kicker">'.e(tr('YOUR SICC EXPERIENCE')).'</div><h2>'.e(tr('Welcome back')).($first!==''?', '.e($first):'').'<span class="customer-welcome-dot">.</span></h2>';
            echo '<p>'.e(tr('Discover upcoming SICC events, reserve tickets and keep everything in one place.')).'</p><a class="btn btn-light" href="'.e(url('customer_bookings')).'">'.e(tr('My bookings')).' <span aria-hidden="true">↗</span></a></div><div class="customer-welcome-decoration" aria-hidden="true">T4Y</div></section>';
            echo '<div class="customer-stats"><a class="customer-stat" href="'.e(url('customer_bookings')).'"><span>'.e(tr('Upcoming bookings')).'</span><strong>'.e($upcoming).'</strong><small>'.e(tr('View bookings')).' ↗</small></a>';
            echo '<a class="customer-stat" href="'.e(url('customer_tickets')).'"><span>'.e(tr('Valid tickets')).'</span><strong>'.e($tickets).'</strong><small>'.e(tr('View my tickets')).' ↗</small></a>';
            echo '<a class="customer-stat" href="'.e(url('customer_favorites')).'"><span>'.e(tr('Saved events')).'</span><strong>'.e($favourites).'</strong><small>'.e(tr('View saved events')).' ↗</small></a></div>';
            customerEventsView(true, $u); break;
        case 'customer_events': customerEventsView(false, $u); break;
        case 'customer_bookings': customerBookingsView($u); break;
        case 'customer_tickets': customerTicketsView($u); break;
        case 'customer_favorites': customerFavoritesView($u); break;
        case 'customer_rewards': customerRewardsView($u); break;
        case 'customer_recommendations': recommendationView($u); break;
        default: throw new DomainException('Unknown customer page.');
    }
}
/** Only SICC published events with a future scheduled session are discoverable. */
function customerVisibleEvents(): array {
    return all("SELECT e.id,e.title,e.description,e.category_tag,e.venue_id,v.name AS venue_name,v.level_no AS venue_level,
       (SELECT MIN(s.start_at) FROM event_sessions s WHERE s.event_id=e.id AND s.status='scheduled' AND s.start_at>NOW()) AS next_session,
       (SELECT MIN(c.price) FROM ticket_categories c JOIN event_sessions s ON s.id=c.session_id
         WHERE s.event_id=e.id AND s.status='scheduled' AND s.start_at>NOW() AND c.status='active' AND c.type!='complimentary') AS starting_price,
       (SELECT MAX(c.price) FROM ticket_categories c JOIN event_sessions s ON s.id=c.session_id
         WHERE s.event_id=e.id AND s.status='scheduled' AND s.start_at>NOW() AND c.status='active' AND c.type!='complimentary') AS highest_price,
       (SELECT GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ', ') FROM ticket_categories c JOIN event_sessions s ON s.id=c.session_id
         WHERE s.event_id=e.id AND s.status='scheduled' AND s.start_at>NOW() AND c.status='active' AND c.type!='complimentary') AS category_names
       FROM events e JOIN venues v ON v.id=e.venue_id
       WHERE e.status='published' AND (e.visible_before_sales=1 OR e.sales_start<=NOW())
         AND EXISTS (SELECT 1 FROM event_sessions s WHERE s.event_id=e.id AND s.status='scheduled' AND s.start_at>NOW())
       ORDER BY next_session ASC, e.id DESC LIMIT 200");
}
/** Render an accessible card with the real configured venue and category pricing. */
function customerEventCard(array $ev, bool $isSaved=false): void {
    [$title,$description]=eventText((int)$ev['id'],(string)$ev['title'],(string)$ev['description']);
    $start=$ev['next_session']?strtotime((string)$ev['next_session']):false;
    $date=$start?date('d M Y',$start):'TBA';
    $time=$start?date('g:i A',$start):'TBA';
    $min=$ev['starting_price']??null;
    $max=$ev['highest_price']??$min;
    if($min===null)$price=tr('Ticket options coming soon');
    elseif((float)$min===0.0 && (float)$max>0)$price=tr('Free').' – '.money($max);
    elseif((float)$max===0.0)$price=tr('Free admission');
    elseif($max!==null && cents((string)$min)!==cents((string)$max))$price=money($min).' – '.money($max);
    else $price=money($min);
    $catList=trim((string)($ev['category_names']??''));
    $venueLabel=(string)$ev['venue_name'].' · '.tr('Level').' '.(int)($ev['venue_level']??0);
    echo '<article class="customer-event-card"><div class="customer-event-cover"><span class="customer-event-tag">'.e($ev['category_tag']).'</span><span class="customer-cover-mark" aria-hidden="true">SICC</span><span class="customer-cover-lines" aria-hidden="true"></span></div>';
    echo '<div class="customer-event-content"><p class="customer-event-date">'.e($date).' <span class="event-date-dot"></span> '.e($time).' MYT</p><h3><a class="customer-event-title-link" href="'.e(url('event_details',['event_id'=>$ev['id']])).'">'.e($title).'</a></h3><p class="customer-event-desc">'.e(mbSafe($description,135)).'</p>';
    echo '<p class="customer-event-venue"><span aria-hidden="true">⌖</span> '.e($venueLabel).' · SICC</p>';
    echo '<div class="customer-event-categories"><strong>'.e(tr('Ticket categories')).'</strong><span>'.e($catList!==''?$catList:tr('Ticket options coming soon')).'</span></div>';
    echo '<div class="customer-event-price"><span>'.e(tr('Ticket price')).'</span><strong>'.e($price).'</strong></div>';
    echo '<div class="customer-event-actions"><a class="btn btn-outline-primary" href="'.e(url('event_details',['event_id'=>$ev['id']])).'">'.e(tr('View details')).'</a>';
    if($min!==null)echo '<a class="btn btn-primary" href="'.e(url('booking',['event_id'=>$ev['id']])).'">'.e(tr('Book now')).' ↗</a>';
    echo postButton('customer_favourite',$isSaved?'♥':'♡','btn customer-favorite-button'.($isSaved?' is-saved':''),['event_id'=>$ev['id'],'return_to'=>in_array(($_GET['page']??''),['dashboard','customer_events','customer_favorites'],true)?(string)$_GET['page']:'customer_events']);
    echo '</div></div></article>';
}
function customerEventsView(bool $dashboard, array $u): void {
    $events = customerVisibleEvents();
    $search = isset($_GET['search']) && is_string($_GET['search']) ? trim(mbSafe($_GET['search'],90)) : '';
    $category = isset($_GET['category']) && is_string($_GET['category']) ? trim(mbSafe($_GET['category'],80)) : '';
    $categories = array_values(array_unique(array_map(static fn(array $x): string=>(string)$x['category_tag'], $events)));
    sort($categories, SORT_NATURAL | SORT_FLAG_CASE);
    if ($category !== '' && !in_array($category, $categories, true)) $category='';
    $filtered = array_values(array_filter($events, static function(array $ev) use ($search,$category): bool {
        if ($category!=='' && $category !== $ev['category_tag']) return false;
        if ($search==='') return true;
        return stripos($ev['title'].' '.$ev['description'].' '.$ev['category_tag'].' '.$ev['venue_name'], $search)!==false;
    }));
    $saved = array_map('intval', array_column(all('SELECT event_id FROM favorites WHERE user_id=?',[$u['id']]),'event_id'));
    echo '<section class="customer-discovery" id="customer-events"><div class="customer-section-heading"><div><div class="customer-section-kicker">'.e(tr('EXPLORE AT SICC')).'</div><h2>'.e($dashboard?tr('Events you can explore'):tr('Discover events')).'</h2><p>'.e(tr('Browse published events happening at the Sabah International Convention Centre.')).'</p></div><span class="customer-event-count">'.count($filtered).' '.e(tr(count($filtered)===1?'Event':'Events')).'</span></div>';
    echo '<form class="customer-discovery-filters" method="get" action=""><input type="hidden" name="page" value="'.($dashboard?'dashboard':'customer_events').'">';
    echo '<label class="visually-hidden" for="eventSearch">'.e(tr('Search events')).'</label><div class="customer-searchbox"><span aria-hidden="true">⌕</span><input id="eventSearch" type="search" name="search" value="'.e($search).'" maxlength="90" placeholder="'.e(tr('Search events, venues or interests')).'"></div>';
    echo '<label class="visually-hidden" for="eventCategory">'.e(tr('Event category')).'</label><select id="eventCategory" name="category"><option value="">'.e(tr('All categories')).'</option>';
    foreach($categories as $cat) echo '<option value="'.e($cat).'"'.($category===$cat?' selected':'').'>'.e($cat).'</option>';
    echo '</select><button type="submit" class="btn btn-primary">'.e(tr('Find events')).'</button>';
    if($search!==''||$category!=='')echo '<a class="customer-reset-search" href="'.e(url($dashboard?'dashboard':'customer_events')).'">'.e(tr('Clear')).'</a>';
    echo '</form>';
    if(!$filtered)echo '<div class="customer-empty-state"><div class="customer-empty-icon" aria-hidden="true">✦</div><h3>'.e(tr('No matching events right now')).'</h3><p>'.e(tr('Try another search, or check back for newly published SICC events.')).'</p><a href="'.e(url($dashboard?'dashboard':'customer_events')).'" class="btn btn-outline-primary">'.e(tr('View all events')).'</a></div>';
    else {echo '<div class="customer-event-grid">';foreach($filtered as $ev)customerEventCard($ev,in_array((int)$ev['id'],$saved,true));echo '</div>';}
    echo '</section>';
}
function customerBookingsView(array $u): void {
    $filters=bookingHistoryFilters((int)$u['id'],$_GET);
    $orders = all('SELECT o.id,o.booking_ref,o.status,o.grand_total,o.paid_at,o.hold_expires_at,s.label,s.start_at,e.title FROM booking_orders o JOIN event_sessions s ON s.id=o.session_id JOIN events e ON e.id=s.event_id'.$filters['where'].' ORDER BY o.id DESC LIMIT 100',$filters['params']);
    echo '<section class="customer-section-heading"><div><div class="customer-section-kicker">'.e(tr('YOUR ORDERS')).'</div><h2>'.e(tr('My bookings')).'</h2><p>'.e(tr('Review bookings, payment status and receipts.')).'</p></div><a class="btn btn-outline-primary" href="'.e(url('customer_events')).'">'.e(tr('Explore events')).'</a></section>';
    bookingHistoryForm('customer_bookings',$filters);
    if(!$orders){if($filters['search']!==''||$filters['status']!=='')echo '<p>No bookings match the selected filters.</p>';else customerEmpty('No bookings yet','Find your first event and reserve your tickets.','customer_events','Browse events');return;}
    panel('Booking history');$rows=[];
    foreach($orders as $o){$rows[]=[e($o['booking_ref']),e($o['title']),e(($o['label']??'').' · '.$o['start_at']).' MYT',b(bookingDisplayStatus($o)),money($o['grand_total']),'<a class="btn btn-sm btn-outline-primary" href="'.e(url('booking',['order_id'=>$o['id']])).'">'.e(tr('Open')).'</a>'];}
    tbl(['Booking reference','Event','Session','Status','Total','Action'],$rows);endPanel();
}
function customerTicketsView(array $u): void {
    $tickets=all('SELECT t.ticket_code,t.status,t.checked_in_at,t.issued_at,c.name AS category_name,o.id AS order_id,e.title,s.start_at FROM tickets t JOIN booking_items bi ON bi.id=t.booking_item_id JOIN booking_orders o ON o.id=bi.order_id JOIN ticket_categories c ON c.id=bi.category_id JOIN event_sessions s ON s.id=o.session_id JOIN events e ON e.id=s.event_id WHERE o.user_id=? AND o.status=\'paid\' AND LOWER(t.holder_email)=LOWER(?) ORDER BY s.start_at DESC,t.id DESC LIMIT 150',[$u['id'],$u['email']]);
    echo '<section class="customer-section-heading"><div><div class="customer-section-kicker">'.e(tr('YOUR ADMISSIONS')).'</div><h2>'.e(tr('My tickets')).'</h2><p>'.e(tr('Open a ticket to view its QR code and current admission status.')).'</p></div></section>';
    if(!$tickets){customerEmpty('No tickets issued yet','Your tickets will appear after a successful checkout.','customer_events','Explore events');return;}
    panel('Issued e-tickets');$rows=[];
    foreach($tickets as $t)$rows[]=[e($t['ticket_code']),e($t['title']),e($t['category_name']),e($t['start_at']).' MYT',b($t['status']).($t['checked_in_at']?' · '.e(tr('Checked in')):''),'<a class="btn btn-sm btn-outline-primary" href="'.e(url('booking',['order_id'=>$t['order_id']])).'">'.e(tr('View QR')).'</a>'];
    tbl(['Ticket','Event','Category','Session','Status','Access'],$rows);endPanel();
}
function customerFavoritesView(array $u): void {
    $events=customerVisibleEvents();$saved=array_map('intval',array_column(all('SELECT event_id FROM favorites WHERE user_id=?',[$u['id']]),'event_id'));
    $savedEvents=array_values(array_filter($events,static fn(array $ev):bool=>in_array((int)$ev['id'],$saved,true)));
    echo '<section class="customer-section-heading"><div><div class="customer-section-kicker">'.e(tr('YOUR SHORTLIST')).'</div><h2>'.e(tr('Saved events')).'</h2><p>'.e(tr('Published upcoming events you have saved.')).'</p></div></section>';
    if(!$savedEvents){customerEmpty('No saved upcoming events','Save an event to find it here later.','customer_events','Explore events');return;}
    echo '<div class="customer-event-grid">';foreach($savedEvents as $ev)customerEventCard($ev,true);echo '</div>';
}
function customerRewardsView(array $u): void {
    $balance=rewardsBalance((int)$u['id']);
    $ledger=all('SELECT points_delta,entry_type,created_at FROM reward_ledger WHERE user_id=? ORDER BY id DESC LIMIT 80',[$u['id']]);
    echo '<section class="customer-section-heading"><div><div class="customer-section-kicker">'.e(tr('REWARDS & BENEFITS')).'</div><h2>'.e(tr('Customer Rewards')).'</h2><p>'.e(tr('Earn and redeem available points through eligible bookings.')).'</p></div></section>';
    echo '<div class="customer-points-banner"><div><span>'.e(tr('Your points balance')).'</span><strong>'.e($balance).'</strong></div><span class="customer-points-decoration" aria-hidden="true">✧</span></div>';
    if(!$ledger){customerEmpty('No rewards activity yet','Explore eligible SICC events and start earning rewards after checkout.','customer_events','Browse events');return;}
    panel('Points activity');tbl(['Points change','Type','Date'],array_map(static fn(array $l):array=>[e($l['points_delta']),e($l['entry_type']),e($l['created_at'])],$ledger));endPanel();
}
function customerEmpty(string $heading,string $description,string $page,string $action): void {
    echo '<div class="customer-empty-state"><div class="customer-empty-icon" aria-hidden="true">✧</div><h3>'.e(tr($heading)).'</h3><p>'.e(tr($description)).'</p><a class="btn btn-primary" href="'.e(url($page)).'">'.e(tr($action)).'</a></div>';
}
