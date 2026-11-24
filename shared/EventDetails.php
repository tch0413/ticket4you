<?php
declare(strict_types=1);
/** Read-only public SICC event information. All booking actions remain in Booking.php. */
function eventDetailsView(): void {
    $raw = $_GET['event_id'] ?? null;
    $eid = filter_var($raw, FILTER_VALIDATE_INT);
    must($eid !== false && $eid !== null && (int)$eid > 0, 'Select an event to view its details.');
    $event = one("SELECT e.*,v.name AS venue_name,v.level_no AS venue_level,v.facilities AS venue_facilities
                  FROM events e JOIN venues v ON v.id=e.venue_id
                  WHERE e.id=? AND e.status='published'
                    AND (e.visible_before_sales=1 OR e.sales_start<=NOW())", [(int)$eid]);
    must((bool)$event, 'This event is not currently public.');
    $sessions = all("SELECT s.*,v.name AS session_venue,v.level_no AS session_level
                    FROM event_sessions s JOIN venues v ON v.id=s.venue_id
                    WHERE s.event_id=? AND s.status='scheduled' AND s.start_at>NOW()
                    ORDER BY s.start_at,s.id",[(int)$eid]);
    must((bool)$sessions,'This event has no upcoming sessions.');
    [$title,$desc] = eventText((int)$eid,(string)$event['title'],(string)$event['description']);
    echo '<div class="event-detail-hero"><span class="event-detail-tag">'.e($event['category_tag']).' · SICC ONLY</span><h2>'.e($title).'</h2>';
    echo '<p class="event-detail-lead">'.nl2br(e($desc)).'</p>';
    echo '<div class="event-detail-summary"><span>⌖ '.e($event['venue_name']).' · '.e(tr('Level')).' '.e($event['venue_level']).'</span>';
    echo '<span>'.count($sessions).' '.e(count($sessions)===1?tr('Session'):tr('Sessions')).'</span>';
    echo '<span>'.e(tr('Timezone')).': Asia/Kuching (MYT)</span></div></div>';
    if(str_contains((string)($event['venue_facilities']??''),'DEMO ONLY')) {
        echo '<p class="event-detail-notice">Academic demonstration: the venue capacity is a fictional test value, not a verified SICC safety capacity. No real tickets are sold.</p>';
    }
    echo '<section class="customer-section-heading"><div><div class="customer-section-kicker">'.e(tr('PLAN YOUR VISIT')).'</div><h2>'.e(tr('Sessions & ticket options')).'</h2><p>'.e(tr('Choose a session below. Ticket availability is checked again when you reserve.')).'</p></div></section>';
    foreach($sessions as $s){
        $start=strtotime((string)$s['start_at']);$end=strtotime((string)$s['end_at']);
        $catRows=all("SELECT id,name,description,type,price,eligibility FROM ticket_categories
                       WHERE session_id=? AND status='active' AND type!='complimentary' ORDER BY price ASC,id",[(int)$s['id']]);
        $onSale=eventIsOnSale(array_merge($s,[
          'event_status'=>$event['status'],'sales_start'=>$event['sales_start'],'sales_end'=>$event['sales_end']
        ]));
        $available=inventory((int)$s['id'])['available'];
        echo '<article class="event-detail-session"><div class="event-detail-session-head"><div><h3>'.e($s['label']).'</h3>';
        echo '<p class="text-muted mb-0">'.e(date('D, d M Y',$start)).' · '.e(date('g:i A',$start)).'–'.e(date('g:i A',$end)).' MYT<br>⌖ '.e($s['session_venue']).' · '.e(tr('Level')).' '.e($s['session_level']).'</p></div>';
        if($onSale && $available>0 && $catRows)echo '<a class="btn btn-primary" href="'.e(url('booking',['event_id'=>$eid])).'">'.e(tr('Book now')).' ↗</a>';
        else echo '<span class="badge text-bg-secondary">'.e($available<=0?tr('Sold out'):tr('Not currently on sale')).'</span>';
        echo '</div>';
        if(!$catRows){echo '<p class="text-muted mt-3">'.e(tr('Ticket categories are not available yet.')).'</p>';}else{
            echo '<div class="event-detail-category-list">';
            foreach($catRows as $cat){
                $catAvailable=inventory((int)$s['id'],(int)$cat['id'])['available'];
                $price=$cat['type']==='free'?tr('Free'):money($cat['price']);
                echo '<div class="event-detail-category"><strong>'.e($cat['name']).'</strong><span class="price">'.e($price).'</span>';
                if(!empty($cat['description']))echo '<small>'.e($cat['description']).'</small>';
                if(!empty($cat['eligibility']))echo '<small>'.e(tr('Eligibility')).': '.e($cat['eligibility']).'</small>';
                echo '<small>'.e($catAvailable).' '.e(tr('available')).'</small></div>';
            }
            echo '</div>';
        }
        echo '<p class="text-muted small mb-0">'.e(tr('Maximum tickets per booking')).': '.e($event['max_per_order']).' · '.e(tr('Booking hold')).': '.e($event['hold_minutes']).' '.e(tr('minutes')).' · '.e(tr('Fee per paid ticket')).': '.e(money($event['fee_per_ticket'])).'</p>';
        echo '</article>';
    }
    echo '<p><a class="btn btn-outline-primary" href="'.e(url(user()?'customer_events':'home')).'">← '.e(tr('Back to events')).'</a></p>';
}
