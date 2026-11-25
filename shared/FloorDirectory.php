<?php
declare(strict_types=1);
/**
 * SICC space names transcribed from the supplied floor-by-floor reference.
 * These are directory entries, NOT approved venue allocations, capacities,
 * actual geometries, seating plans, or an official SICC architectural drawing.
 */
function siccFloorDirectory(): array {
 return [
  2=>[
   'Kinabatangan Foyer','Kinabatangan I','Kinabatangan II','Kinabatangan III',
   'Business Centre','Kapalai & Manukan','Mabul','Mataking','Balambangan',
   'Silam Lounge (VVIP Lounge)','Silam Foyer','Promenade',
  ],
  3=>[
   'Jesselton Square (Open Plaza)','Danum (VVIP Room)','Imbak I (VVIP Room)',
   'Imbak Lounge (VIP Lounge)','Imbak II (VVIP Room)','VIP/Imbak Foyer',
   'Lobby','Meranti Cafe','Porte Cochere','Sayat-Sayat',
  ],
  4=>[
    'Sipadan Foyer','Sipadan I-III','PAH Foyer'
    ],
  5=>[
   'Kadamaian','Mesilau','Dinawan','Madai','Sepilok','Selingan',
   'Monsopiad','Klias','Mantanani','Rafflesia','Kianbalu I',
   'Kinabalu II','Kinabalu III','Kinabalu I-III','Kinabalu Foyer I',
  ],
 ];
}
/** Image assets match the named venue entries. Other rooms remain without an image until available. */
function siccVenueImage(string $name, int $level): ?string {

    // Level 5 venue images
    if ($level === 5) {
        $images = [
            'Kadamaian' => 'kadamaian.webp',
            'Mesilau' => 'mesilau.webp',
            'Dinawan' => 'dinawan.webp',
            'Madai' => 'madai.webp',
            'Sepilok' => 'sepilok.webp',
            'Selingan' => 'selingan.webp',
            'Monsopiad' => 'monsopiad.webp',
            'Klias' => 'klias.webp',
            'Mantanani' => 'mantanani.webp',
            'Rafflesia' => 'rafflesia.webp',
            'Kianbalu I' => 'kianbalu-i.webp',
            'Kinabalu II' => 'kinabalu-ii.webp',
            'Kinabalu III' => 'kinabalu-iii.webp',
            'Kinabalu I-III' => 'kinabalu-i-iii.webp',
            'Kinabalu Foyer I' => 'kinabalu-foyer-i.webp',
        ];

        return isset($images[$name])
            ? 'assets/venues/' . $images[$name]
            : null;
    }

    // Level 4 venue images
    if ($level === 4) {
        $images = [
            'Sipadan Foyer' => 'sipadan-foyer.webp',
            'Sipadan I-III' => 'sipadan-i-iii.webp',
            'PAH Foyer' => 'pah-foyer.webp',
        ];

        return isset($images[$name])
            ? 'assets/venues/' . $images[$name]
            : null;
    }

    // Level 3 SICC venue illustrations. Level 2 mappings below remain unchanged.
    if ($level === 3) {
        $images = [
            'Jesselton Square (Open Plaza)' => 'jesselton-square.webp',
            'Danum (VVIP Room)' => 'danum.webp',
            'Imbak I (VVIP Room)' => 'imbak-i.webp',
            'Imbak Lounge (VIP Lounge)' => 'imbak-lounge.webp',
            'Imbak II (VVIP Room)' => 'imbak-ii.webp',
            'VIP/Imbak Foyer' => 'vip-imbak-foyer.webp',
            'Lobby' => 'lobby.webp',
            'Meranti Cafe' => 'meranti-cafe.webp',
            'Porte Cochere' => 'porte-cochere.webp',
            'Sayat-Sayat' => 'sayat-sayat.webp',
        ];
        return isset($images[$name]) ? 'assets/venues/' . $images[$name] : null;
    }
    
    if ($level !== 2) return null;
    $images = [
        'Kinabatangan Foyer' => 'kinabatangan-foyer.webp',
        'Kinabatangan I' => 'kinabatangan-i.webp',
        'Kinabatangan II' => 'kinabatangan-ii.webp',
        'Kinabatangan III' => 'kinabatangan-iii.webp',
        'Business Centre' => 'business-centre.webp',
        'Kapalai & Manukan' => 'kapalai-manukan.webp',
        'Mabul' => 'mabul.webp',
        'Mataking' => 'mataking.webp',
        'Balambangan' => 'balambangan.webp',
        'Silam Lounge (VVIP Lounge)' => 'silam-lounge.webp',
        'Silam Foyer' => 'silam-foyer.png',
        'Promenade' => 'promenade.png',
    ];
    return isset($images[$name]) ? 'assets/venues/'.$images[$name] : null;
}
/**
 * Provisional planning ranges for the student demo UI, NOT official or certified
 * occupancy limits. Do not use these values for bookings, safety approvals,
 * layout capacity, or ticket allocations. The venue module retains authoritative
 * administrator-approved numbers.
 */
function siccVenuePlanningInfo(): array {
 return [
  2 => [
   'Kinabatangan Foyer'=>[[350,600],'Pre-function foyer','A flexible arrival and gathering area serving the Kinabatangan halls.','Registration, receptions, networking'],
   'Kinabatangan I'=>[[700,1200],'Exhibition / conference hall','Large divisible convention-hall space suited to adaptable event layouts.','Exhibitions, conferences, large seminars'],
   'Kinabatangan II'=>[[700,1200],'Exhibition / conference hall','Large convention-hall section suitable for organised visitor flow and temporary event layouts.','Exhibitions, conventions, showcases'],
   'Kinabatangan III'=>[[700,1200],'Exhibition / conference hall','Large configurable hall section for exhibitions, presentations and formal functions.','Trade events, conferences, receptions'],
   'Business Centre'=>[[20,40],'Business support space','A quieter support area for small professional meetings and event-related work.','Business services, consultations, small briefings'],
   'Kapalai & Manukan'=>[[60,100],'Combined meeting suite','A flexible combined meeting space for discussion-led events and presentations.','Meetings, workshops, training'],
   'Mabul'=>[[20,40],'Meeting room','A smaller meeting room suited to focused discussions and workshops.','Boardroom meetings, interviews, breakouts'],
   'Mataking'=>[[30,60],'Meeting room','A compact space for collaborative sessions and scheduled talks.','Training, workshops, small seminars'],
   'Balambangan'=>[[40,80],'Meeting room','A versatile meeting space for medium-sized professional gatherings.','Briefings, seminars, meetings'],
   'Silam Lounge (VVIP Lounge)'=>[[15,30],'VVIP lounge','A private hospitality setting intended for more intimate guest reception.','VIP reception, private hosting, meetings'],
   'Silam Foyer'=>[[40,80],'Foyer','An adjoining circulation and gathering area for lounge guests.','Guest arrival, registration, networking'],
   'Promenade'=>[null,'Outdoor promenade','An outdoor waterfront-oriented space; no numerical capacity is estimated here.','Walking, sightseeing, informal gatherings'],
  ],
  3 => [
   'Jesselton Square (Open Plaza)'=>[[500,1500],'Outdoor open plaza','An open-air plaza adaptable to event setups, subject to access and safety plans.','Community events, outdoor showcases, public gatherings'],
   'Danum (VVIP Room)'=>[[12,24],'VVIP room','A private meeting and hosting space for small groups.','VIP meetings, private discussions'],
   'Imbak I (VVIP Room)'=>[[15,30],'VVIP room','A compact high-privacy room for hosted discussions.','Executive meetings, VIP briefings'],
   'Imbak Lounge (VIP Lounge)'=>[[20,40],'VIP lounge','An intimate waiting and hospitality setting for invited guests.','VIP receptions, guest hospitality'],
   'Imbak II (VVIP Room)'=>[[15,30],'VVIP room','A private breakout or hosting room for selected attendees.','Executive meetings, private sessions'],
   'VIP/Imbak Foyer'=>[[40,70],'VIP foyer','A transition and reception area adjoining the VIP facilities.','Arrival, informal networking, check-in'],
   'Lobby'=>[[150,300],'Main lobby','The central arrival and circulation area of the venue.','Visitor orientation, registration, arrivals'],
   'Meranti Cafe'=>[[60,120],'Café / dining','A dining and hospitality venue within SICC. This is a planning-only guest range.','Dining, refreshments, small hospitality functions'],
   'Porte Cochere'=>[[30,60],'Vehicle drop-off / arrivals','An arrival and vehicle-access area; the estimate is for indicative transient visitors, not seated event occupancy.','Drop-off, pick-up, guest arrivals'],
   'Sayat-Sayat'=>[[40,80],'Meeting / hospitality space','A small-to-medium space for hosted gatherings, pending venue-layout confirmation.','Meetings, small functions'],
  ],
  4 => [
   'Sipadan Foyer'=>[[300,500],'Convention foyer','A pre-function space adjoining the Sipadan convention facilities.','Registration, receptions, networking'],
   'Sipadan I-III'=>[[2000,4000],'Combined convention hall','Large combined hall for major events; capacity varies substantially by seating plan.','Conferences, conventions, banquets'],
   'PAH Foyer'=>[[200,400],'Performing arts foyer','An arrival and interval gathering area supporting the performing arts facilities.','Ticket check-in, interval gatherings, receptions'],
  ],
  5 => [
   'Kadamaian'=>[[80,150],'Meeting / conference room','A flexible meeting room for formal programmes and group sessions.','Conferences, workshops, training'],
   'Mesilau'=>[[60,120],'Meeting room','A versatile professional room suited to presentations and discussions.','Seminars, meetings, training'],
   'Dinawan'=>[[60,120],'Meeting room','A configurable meeting space for mid-sized groups.','Workshops, briefings, breakout events'],
   'Madai'=>[[30,70],'Meeting room','An intimate meeting area for focused sessions.','Training, consultations, group meetings'],
   'Sepilok'=>[[30,70],'Meeting room','A small meeting room for a range of discussion-led formats.','Board meetings, workshops'],
   'Selingan'=>[[25,50],'Meeting room','A compact breakout room for facilitated sessions.','Team meetings, private workshops'],
   'Monsopiad'=>[[80,150],'Meeting / function room','A flexible room for seminars and small formal functions.','Seminars, networking, group sessions'],
   'Klias'=>[[30,70],'Meeting room','A smaller professional space suited to short sessions.','Meetings, workshops, talks'],
   'Mantanani'=>[[40,80],'Meeting room','An adaptable space for discussions and scheduled activities.','Training, meetings, presentations'],
   'Rafflesia'=>[[80,160],'Meeting / function room','A medium-sized function space for formal or collaborative programmes.','Conferences, meetings, receptions'],
   'Kianbalu I'=>[[80,140],'Divisible meeting room','One section of the combined Kinabalu meeting suite; estimates are not additive to the combined configuration.','Presentations, seminars, workshops'],
   'Kinabalu II'=>[[80,140],'Divisible meeting room','A section of the Kinabalu suite for individually scheduled meetings.','Seminars, training, presentations'],
   'Kinabalu III'=>[[80,140],'Divisible meeting room','A flexible section of the Kinabalu suite for independent events.','Meetings, discussions, presentations'],
   'Kinabalu I-III'=>[[250,450],'Combined meeting suite','The full three-room combined arrangement; not a separate simultaneous capacity from the individual rooms.','Conferences, workshops, medium functions'],
   'Kinabalu Foyer I'=>[[100,200],'Meeting-suite foyer','An arrival and gathering area supporting adjacent meeting rooms.','Registration, receptions, networking'],
  ],
 ];
}
function siccVenueEstimate(string $name,int $level): ?array {
 return siccVenuePlanningInfo()[$level][$name][0]??null;
}
function siccVenueRecord(string $name,int $level): array {
 $row=siccVenuePlanningInfo()[$level][$name]??[null,'Venue','Contact the venue for approved arrangements.','Venue-dependent'];
 return [
  'name'=>$name,'level'=>$level,'estimate'=>$row[0],
  'category'=>$row[1],'description'=>$row[2],'uses'=>$row[3],
  'image'=>siccVenueImage($name,$level),
 ];
}
function siccEstimatedCapacityLabel(?array $range): string {
 return $range===null?'Not estimated':number_format($range[0]).'–'.number_format($range[1]).' people';
}
function siccRoomAtFloor(string $name, int $level): bool {
 return in_array($name,siccFloorDirectory()[$level]??[],true);
}
function siccProposedCode(string $name,int $level): string {
 $slug=strtoupper((string)preg_replace('/[^A-Za-z0-9]+/','-',$name));
 return substr('L'.$level.'-'.trim($slug,'-'),0,23).'-'.strtoupper(substr(hash('sha256',$level.'-'.$name),0,6));
}
/**
 * Render every venue card as an accessible modal trigger. Never navigate away
 * or link directly to a photo; visitors can scroll the details and close it.
 */
function floorDirectoryView(): void {
 $floors=siccFloorDirectory();$total=array_sum(array_map('count',$floors));
 $admin=isAdmin();$configured=[];$records=[];
 if($admin){
  foreach(all('SELECT name,level_no,status FROM venues') as $venue){
   $configured[$venue['level_no'].':'.$venue['name']]=$venue['status'];
  }
 }
 echo '<div class="directory-intro"><div><span class="directory-kicker">SABAH INTERNATIONAL CONVENTION CENTRE</span><h2>'.e(tr('Spaces by floor')).'</h2><p>Level 2–5 · '.e($total).' '.e(tr('spaces')).' · SICC, Sabah</p></div><div class="directory-sculpture" aria-hidden="true"><span>02</span><span>03</span><span>04</span><span>05</span></div></div>';
 echo '<div class="directory-controls"><label class="directory-search"><span aria-hidden="true">⌕</span><span class="visually-hidden">'.e(tr('Find a space')).'</span><input type="search" id="floorRoomSearch" placeholder="'.e(tr('Find a space')).'" autocomplete="off"></label><div class="floor-tabs" role="group" aria-label="'.e(tr('Spaces by floor')).'">';
 echo '<button class="floor-filter is-active" type="button" data-floor="all" aria-pressed="true">'.e(tr('All levels')).'</button>';
 foreach($floors as $number=>$rooms)echo '<button class="floor-filter" type="button" data-floor="'.e($number).'" aria-pressed="false">'.e(tr('Level')).' '.e($number).'</button>';
 echo '</div></div>';
 echo '<p class="directory-disclaimer"><span aria-hidden="true">ⓘ</span> Estimated guest ranges are illustrative planning figures only, not SICC-approved capacities. Actual safe occupancy depends on the room layout and SICC approval. Illustrations are not verified room photographs.</p>';
 echo '<div class="floor-collections" id="floorCollections">';
 foreach($floors as $number=>$rooms){
  echo '<section class="floor-section" data-floor-section="'.e($number).'"><div class="floor-section-header"><span class="floor-index">'.sprintf('%02d',$number).'</span><div><span class="floor-section-eyebrow">SICC / DIRECTORY</span><h3>'.e(tr('Level')).' '.e($number).'</h3></div><span class="floor-room-count">'.count($rooms).' '.e(tr('spaces')).'</span></div><div class="floor-room-grid">';
  foreach($rooms as $name){
   $key=$number.':'.$name;
   $record=siccVenueRecord($name,(int)$number);
   $records[$key]=$record;
   $image=$record['image'];
   echo '<article class="room-tile room-tile--modal'.($image?' room-tile--illustrated':'').'" data-room-name="'.e(strtolower($name)).'">';
   echo '<button class="room-tile-open" type="button" data-bs-toggle="modal" data-bs-target="#venueDetailsModal" data-venue-key="'.e($key).'" aria-label="'.e('View venue details for '.$name).'">';
   if($image){
    echo '<span class="room-tile-photo"><img src="'.e($image).'" alt="'.e($name).'" loading="lazy" decoding="async" width="1280" height="960"></span>';
   } else {
    echo '<span class="room-tile-placeholder" aria-hidden="true"><span class="room-tile-placeholder-emblem">SICC</span><span class="room-tile-placeholder-level">'.e('LEVEL '.str_pad((string)$number,2,'0',STR_PAD_LEFT)).'</span></span>';
   }
   echo '<span class="room-tile-info"><span class="room-tile-marker" aria-hidden="true">◆</span><span class="room-tile-body"><span class="room-tile-title">'.e($name).'</span><span class="room-tile-meta">'.e(tr('Level')).' '.e($number).' · '.e($record['category']).'</span><span class="room-tile-capacity">'.e($record['estimate']?'Approx. '.siccEstimatedCapacityLabel($record['estimate']):'Outdoor · capacity not estimated').'</span></span><span class="room-tile-arrow" aria-hidden="true">↗</span></span>';
   echo '</button>';
   if($admin){
    if(isset($configured[$key]))echo '<div class="room-tile-admin"><span class="room-status">'.e(ucfirst($configured[$key])).'</span></div>';
    else echo '<div class="room-tile-admin"><a class="room-manage-link" href="'.e(url('venue',['prefill'=>$name,'floor'=>$number])).'" aria-label="'.e(tr('Add to venue management')).': '.e($name).'">+ '.e(tr('Add to venue management')).'</a></div>';
   }
   echo '</article>';
  }
  echo '</div></section>';
 }
 echo '</div><p id="floorSearchEmpty" class="directory-no-results" hidden>No matching spaces found. Try another search or level.</p>';
 if($admin)echo '<div class="directory-bottom-link"><a class="btn btn-outline-primary" href="'.e(url('venue')).'">'.e(tr('View operational venues')).' <span aria-hidden="true">↗</span></a></div>';
 echo '<div class="modal fade venue-detail-modal" id="venueDetailsModal" tabindex="-1" aria-labelledby="venueModalName" aria-hidden="true">';
 echo '<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">';
 echo '<div class="modal-header venue-modal-heading"><div><span class="venue-modal-eyebrow">SICC · VENUE DIRECTORY</span><h2 class="modal-title" id="venueModalName">Venue details</h2><p class="venue-modal-level" id="venueModalLevel"></p></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close venue details"></button></div>';
 echo '<div class="modal-body venue-modal-body"><div class="venue-modal-image" id="venueModalImage"></div><div class="venue-modal-content">';
 echo '<span class="venue-modal-kicker" id="venueModalCategory"></span><h3>About this venue</h3><p id="venueModalDescription"></p>';
 echo '<div class="venue-modal-details"><div class="venue-modal-fact"><span>Estimated guest capacity</span><strong id="venueModalCapacity"></strong></div><div class="venue-modal-fact"><span>Location</span><strong id="venueModalLocation"></strong></div></div>';
 echo '<h3>Suggested uses</h3><p id="venueModalUses"></p><p class="venue-modal-caveat">Planning estimates are not certified or approved attendance limits. Final venue capacity, seating configuration and availability must be confirmed by SICC before booking.</p>';
 echo '</div></div><div class="modal-footer"><button type="button" class="btn btn-primary venue-modal-close" data-bs-dismiss="modal">Back to floor directory</button></div>';
 echo '</div></div></div>';
 $json=json_encode($records,JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_INVALID_UTF8_SUBSTITUTE|JSON_THROW_ON_ERROR);
 echo '<script type="application/json" id="venueDirectoryData">'.$json.'</script>';
}
