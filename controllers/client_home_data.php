<?php
// client_home.php - Secure Patient Dashboard
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

session_require_auth(['user']); 

$user_id = session_get_user_id();
$user_name = session_get_username();

// Fetch Page Data
$stmt = $pdo->prepare("SELECT * FROM tblinfo WHERE user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);

$display_name = $user_name;
if ($info && !empty($info['first_name'])) {
    $display_name = $info['first_name'] . ' ' . $info['last_name'];
}
$currentUser = ['name' => $display_name];
$today = date('Y-m-d');

// Fetch Appointments
$stmt = $pdo->prepare("
    SELECT a.booking_date, a.id, a.status, d.first_name, d.last_name
    FROM tblappointment a
    LEFT JOIN tblinfo d ON d.user_id = a.doctor
    WHERE a.user_id = ?
    ORDER BY a.booking_date ASC
");
$stmt->execute([$user_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$upcomingList = [];
$recentHistory = [];
$nextAppointment = null;

foreach ($rows as $r) {
  $date = $r['booking_date'];
  if (!$date) continue;
  
  $doctorName = trim(($r['last_name'] ?? '') . ', ' . ($r['first_name'] ?? ''));
  $statusInt = (int)$r['status'];
  
  // UPDATED STATUS MAPPING
  if ($statusInt === 1) $status = 'Confirmed';
  elseif ($statusInt === 2) $status = 'Pending';
  elseif ($statusInt === 3) $status = 'Completed'; // Added Completed
  elseif ($statusInt === 0) $status = 'Cancelled';
  else $status = 'Unknown';

  // Filter Logic
  // Show "Upcoming" if date is today/future AND status is NOT Cancelled OR Completed
  if ($date >= $today && $statusInt !== 0 && $statusInt !== 3) {
    $item = [
      'doctor' => $doctorName ?: 'TBD',
      'date' => $date,
      'status' => $status, // Capitalized by variable assignment logic above
      'id' => $r['id']
    ];
    $upcomingList[] = $item;
    
    if (!$nextAppointment && ($status === 'Confirmed' || $status === 'Pending')) {
        $nextAppointment = $item;
    }
  } else {
    // History: Past dates, Cancelled, or Completed
    $recentHistory[] = [
      'doctor' => $doctorName ?: 'TBD',
      'date' => $date,
      'status' => $status
    ];
  }
}
$recentHistory = array_slice(array_reverse($recentHistory), 0, 3);
?>