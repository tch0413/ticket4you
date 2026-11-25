<?php
declare(strict_types=1);
require_once __DIR__.'/app/core.php';
require_once __DIR__.'/app/auth.php';
require_once __DIR__.'/app/domain.php';
require_once __DIR__.'/app/i18n.php';
require_once __DIR__.'/app/layout.php';
require_once __DIR__.'/app/login_ui.php';
require_once __DIR__.'/app/uam_phase2.php';
require_once __DIR__.'/modules/UserAccount.php';
require_once __DIR__.'/modules/Venue.php';
require_once __DIR__.'/shared/FloorDirectory.php';
require_once __DIR__.'/modules/Event.php';
require_once __DIR__.'/modules/Session.php';
require_once __DIR__.'/modules/Category.php';
require_once __DIR__.'/modules/Booking.php';
require_once __DIR__.'/modules/Support.php';
require_once __DIR__.'/modules/Scheduling.php';
require_once __DIR__.'/features/RefundPolicy.php';
require_once __DIR__.'/features/Rewards.php';
require_once __DIR__.'/features/MFA.php';
require_once __DIR__.'/features/Recommendations.php';
require_once __DIR__.'/shared/Checkin.php';
require_once __DIR__.'/shared/Notifications.php';
require_once __DIR__.'/shared/Dashboard.php';
require_once __DIR__.'/shared/AdminAnalytics.php';
require_once __DIR__.'/shared/CustomerPortal.php';
require_once __DIR__.'/shared/EventDetails.php';
require_once __DIR__.'/shared/Export.php';

sessionInit();
localeCode();
$page=(string)($_GET['page']??'home');
try {
 if($page==='analytics_data')adminAnalyticsData();
 // GET handlers for single-use email verification / ticket transfer / sign-out.
 if(in_array($page,['verify','logout'],true))uamHandle();
 if($page==='transfer')bookingHandle();
 if($_SERVER['REQUEST_METHOD']==='POST'){
  verifyCsrf();
  $action=(string)($_POST['action']??'');must($action!=='','Form action required.');
  customerPortalHandle();uamHandle();venueHandle();eventHandle();sessionHandle();categoryHandle();bookingHandle();supportHandle();scheduleHandle();
  policyHandle();rewardsHandle();mfaHandle();recommendationHandle();checkinHandle();notificationHandle();
  throw new DomainException('Unknown or unsupported form action.');
 }
 if($page==='ticket_pdf'||($page==='report'&&isset($_GET['pdf'])))exportHandle($page);
 $pages=[
  'home'=>'Upcoming SICC Events','floor'=>'SICC Floor Directory','register'=>'Register',
  'login'=>'Choose sign-in portal','login_customer'=>'Customer sign in','login_organiser'=>'Organiser sign in',
  'login_staff'=>'Staff sign in','login_admin'=>'Administrator sign in','forgot'=>'Forgot password','reset'=>'Password recovery',
  'account'=>'My Account','uam'=>'User Account Management','venue'=>'Venue Management',
  'events'=>'Event Management','session'=>'Session Management','category'=>'Category Management',
  'booking'=>'Booking / Tickets / Payments','support'=>'Customer Support & Disputes',
  'schedule'=>'Staff Scheduling','policy'=>'Refund Policy Interface','rewards'=>'Customer Rewards',
  'mfa'=>'Multi-Factor Authentication','checkin'=>'QR Validation & Check-in',
  'notifications'=>'Notifications','dashboard'=>'My Dashboard','report'=>'Reports','analytics'=>'Analytics details',
  'customer_events'=>'Discover events','customer_bookings'=>'My bookings','customer_tickets'=>'My tickets',
  'customer_favorites'=>'Saved events','customer_rewards'=>'Customer Rewards',
  'customer_recommendations'=>'Event recommendations','event_details'=>'Event details',
  'guest'=>'Guest Ticket Access','staffhelp'=>'Staff Contact Admin'
 ];
 if(!isset($pages[$page])){http_response_code(404);throw new DomainException('Page not found.');}
 layoutTop($page==='dashboard' && customerWorkspaceActive(user(),'dashboard') ? 'Upcoming SICC Events' : ($page==='dashboard' && isAdmin() ? 'Administrator Dashboard' : $pages[$page]));
 switch($page){
  case 'home': publicEventsView();break;
  case 'event_details':eventDetailsView();break;
  case 'floor': floorDirectoryView();break;
  case 'register':case 'login':case 'login_customer':case 'login_organiser':case 'login_staff':case 'login_admin':
  case 'forgot':case 'reset':case 'account':case 'uam': uamView($page);break;
  case 'venue':venueView();break;
  case 'events':eventView();break;
  case 'session':sessionView();break;
  case 'category':categoryView();break;
  case 'booking':bookingView();break;
  case 'support':supportView();break;
  case 'schedule':scheduleView();break;
  case 'policy':policyView();break;
  case 'rewards':rewardsView();break;
  case 'mfa':mfaView();break;
  case 'checkin':checkinView();break;
  case 'notifications':notificationView();break;
  case 'dashboard':dashboardView();break;
  case 'customer_events':case 'customer_bookings':case 'customer_tickets':
  case 'customer_favorites':case 'customer_rewards':case 'customer_recommendations':
   customerPortalView($page);break;
  case 'report':reportView();break;
  case 'analytics':adminAnalyticsDetails();break;
  case 'guest':guestTicketView();break;
  case 'staffhelp':staffHelpView();break;
 }
 layoutBottom();
} catch(DomainException $e){
 http_response_code(http_response_code()>=400?http_response_code():422);
 // Keep the selected sign-in page visible on wrong-role or wrong-password errors.
 // Credentials are never repeated back into HTML or stored in a flash message.
 if(in_array($page,['login_customer','login_organiser','login_staff','login_admin','forgot'],true)){
  $heading=$pages[$page]??'Sign in';
  layoutTop($heading);
  echo '<div class="alert alert-warning" role="alert">'.e($e->getMessage()).'</div>';
  loginScreensView($page);layoutBottom();
 }else{
  layoutTop('Unable to complete request');echo '<div class="alert alert-warning">'.e($e->getMessage()).'</div><p><a href="?page=home">Return to events</a> · <a href="javascript:history.back()">Go back</a></p>';layoutBottom();
 }
} catch(Throwable $e){
 error_log('[T4Y] '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine());
 http_response_code(500);layoutTop('Application error');
 echo '<div class="alert alert-danger">A server or database error occurred. Check the PHP error log and the installation instructions.</div>';
 if(cfg('T4Y_DEBUG','0')==='1')echo '<pre>'.e($e->getMessage()).'</pre>';
 layoutBottom();
}
