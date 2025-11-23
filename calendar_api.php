<?php
// calendar_api.php - API for calendar functionality
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require 'db.php'; // Assuming this includes the $pdo connection

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$my_user_id = $_SESSION['user_id'];
$my_user_type = strtolower($_SESSION['user_type']);
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// --- Helper Functions ---

/**
 * Maps status code to display text, color, and Tailwind class.
 * Status codes: 1=Confirmed, 2=Pending, 0=Cancelled.
 */
function get_status_details($status_code) {
    switch ($status_code) {
        case 1:
            return ['text' => 'Confirmed', 'color' => '#10b981', 'class' => 'bg-green-100 text-green-700'];
        case 2:
            return ['text' => 'Pending', 'color' => '#f59e0b', 'class' => 'bg-yellow-100 text-yellow-700'];
        case 0:
            return ['text' => 'Cancelled', 'color' => '#6b7280', 'class' => 'bg-gray-100 text-gray-700'];
        default:
            return ['text' => 'Unknown', 'color' => '#ef4444', 'class' => 'bg-red-100 text-red-700'];
    }
}

/**
 * Validates CSRF Token for POST requests.
 */
function validate_csrf() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return true;
    }
    $token = $_POST['csrf_token'] ?? '';
    if (empty($token) || $token !== ($_SESSION['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['error' => 'Security Error: Invalid CSRF token.']);
        exit;
    }
    return true;
}

// --- API Logic ---

try {
    // Validate CSRF for POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        validate_csrf();
    }
    
    switch ($action) {
        case 'get_events':
            $start = $_GET['start'] ?? date('Y-m-01');
            $end = $_GET['end'] ?? date('Y-m-t');
            
            $events = [];
            $schedules = [];
            $appointments = [];
            
            if ($my_user_type === 'doctor') {
                // Get doctor's appointments
                $sql = "SELECT a.id, a.booking_date as date, a.status,
                               CONCAT(i.first_name, ' ', i.last_name) as title,
                               i.contact, i.user_id as patient_id,
                               'appointment' as type
                        FROM tblappointment a
                        JOIN tblinfo i ON i.user_id = a.user_id
                        WHERE a.doctor = ? AND a.booking_date BETWEEN ? AND ?
                        ORDER BY a.booking_date";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$my_user_id, $start, $end]);
                $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get doctor's schedules
                $stmt = $pdo->prepare("SELECT s.* FROM tblschedule s WHERE s.user_id = ?");
                $stmt->execute([$my_user_id]);
                $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get unavailable dates
                $stmt = $pdo->prepare("SELECT date, reason FROM tblnoappointment 
                                       WHERE doctor_id = ? AND date BETWEEN ? AND ?");
                $stmt->execute([$my_user_id, $start, $end]);
                $unavailable = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Format unavailable dates as events
                foreach ($unavailable as $na) {
                    $events[] = [
                        'id' => 'unavailable_' . $na['date'],
                        'title' => 'Unavailable: ' . $na['reason'],
                        'start' => $na['date'], 
                        'allDay' => true,
                        'color' => '#ef4444', // Red
                        'type' => 'unavailable',
                        'extendedProps' => ['reason' => $na['reason'], 'type' => 'unavailable']
                    ];
                }
                
            } elseif ($my_user_type === 'admin') {
                // Get all appointments
                $sql = "SELECT a.id, a.booking_date as date, a.status,
                               CONCAT(pi.first_name, ' ', pi.last_name) as patient_name,
                               CONCAT('Dr. ', di.first_name, ' ', di.last_name) as doctor_name,
                               di.contact as doctor_contact, pi.contact as patient_contact,
                               a.doctor as doctor_id, a.user_id as patient_id,
                               'appointment' as type
                        FROM tblappointment a
                        LEFT JOIN tblinfo pi ON pi.user_id = a.user_id
                        LEFT JOIN tblinfo di ON di.user_id = a.doctor
                        WHERE a.booking_date BETWEEN ? AND ?
                        ORDER BY a.booking_date";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$start, $end]);
                $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
            } else { // Patient view
                // Get their appointments
                $sql = "SELECT a.id, a.booking_date as date, a.status,
                               CONCAT('Dr. ', i.first_name, ' ', i.last_name) as doctor_name,
                               i.contact as doctor_contact, i.email as doctor_email,
                               a.doctor as doctor_id,
                               'appointment' as type
                        FROM tblappointment a
                        JOIN tblinfo i ON i.user_id = a.doctor
                        WHERE a.user_id = ? AND a.booking_date BETWEEN ? AND ?
                        ORDER BY a.booking_date";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$my_user_id, $start, $end]);
                $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Format appointments as events (Common for all roles)
            foreach ($appointments as $apt) {
                $details = get_status_details((int)$apt['status']);
                
                $event = [
                    'id' => 'apt_' . $apt['id'],
                    'start' => $apt['date'],
                    'color' => $details['color'],
                    'extendedProps' => [
                        'type' => 'appointment',
                        'status_text' => $details['text'],
                        'status_class' => $details['class'],
                        'appointment_id' => $apt['id'],
                    ]
                ];
                
                if ($my_user_type === 'doctor') {
                    $event['title'] = $apt['title'] . ' (' . $details['text'] . ')';
                    $event['extendedProps']['patient_name'] = $apt['title'];
                    $event['extendedProps']['patient_contact'] = $apt['contact'];
                } elseif ($my_user_type === 'admin') {
                    $event['title'] = $apt['patient_name'] . ' w/ ' . $apt['doctor_name'];
                    $event['extendedProps']['patient_name'] = $apt['patient_name'];
                    $event['extendedProps']['doctor_name'] = $apt['doctor_name'];
                    $event['extendedProps']['doctor_contact'] = $apt['doctor_contact'];
                    $event['extendedProps']['patient_contact'] = $apt['patient_contact'];
                } else { // Client
                    $event['title'] = $apt['doctor_name'] . ' (' . $details['text'] . ')';
                    $event['extendedProps']['doctor_name'] = $apt['doctor_name'];
                    $event['extendedProps']['doctor_contact'] = $apt['doctor_contact'];
                }
                
                $events[] = $event;
            }
            
            echo json_encode(['success' => true, 'events' => $events, 'schedules' => $schedules]);
            break;

        case 'get_day_details':
            $date = $_GET['date'] ?? date('Y-m-d');
            $appointments = [];
            $schedules = [];
            $unavailable_reason = null;
            
            if ($my_user_type === 'doctor') {
                // Get appointments for specific day
                $stmt = $pdo->prepare("SELECT a.*, 
                                              CONCAT(i.first_name, ' ', i.last_name) as patient_name,
                                              i.contact, i.address, i.email
                                       FROM tblappointment a
                                       JOIN tblinfo i ON i.user_id = a.user_id
                                       WHERE a.doctor = ? AND a.booking_date = ?
                                       ORDER BY a.id");
                $stmt->execute([$my_user_id, $date]);
                $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get schedule for this day
                $dayNum = date('N', strtotime($date)); // 1=Monday, 7=Sunday
                $stmt = $pdo->prepare("SELECT * FROM tblschedule WHERE user_id = ? AND day = ?");
                $stmt->execute([$my_user_id, $dayNum]);
                $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Check for unavailable status
                $stmt = $pdo->prepare("SELECT reason FROM tblnoappointment 
                                       WHERE doctor_id = ? AND date = ?");
                $stmt->execute([$my_user_id, $date]);
                $unavailable = $stmt->fetch(PDO::FETCH_ASSOC);
                $unavailable_reason = $unavailable['reason'] ?? null;
                
            } else { // Admin and Patient view
                $sql = "SELECT a.*, 
                               CONCAT('Dr. ', di.first_name, ' ', di.last_name) as doctor_name,
                               di.contact as doctor_contact, di.email as doctor_email,
                               CONCAT(pi.first_name, ' ', pi.last_name) as patient_name,
                               pi.contact as patient_contact, pi.address as patient_address
                        FROM tblappointment a
                        LEFT JOIN tblinfo di ON di.user_id = a.doctor
                        LEFT JOIN tblinfo pi ON pi.user_id = a.user_id
                        WHERE a.booking_date = ?";

                $params = [$date];

                if ($my_user_type !== 'admin') {
                    $sql .= " AND a.user_id = ?";
                    $params[] = $my_user_id;
                }
                
                $sql .= " ORDER BY a.id";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Add status details to appointments
            foreach ($appointments as &$apt) {
                $status_details = get_status_details((int)$apt['status']);
                $apt['status_text'] = $status_details['text'];
                $apt['status_class'] = $status_details['class'];
            }
            unset($apt);
            
            echo json_encode([
                'success' => true, 
                'appointments' => $appointments,
                'schedules' => $schedules,
                'unavailable_reason' => $unavailable_reason,
                'date' => $date
            ]);
            break;

        case 'cancel_appointment':
            // Only doctors and admins can cancel
            if ($my_user_type !== 'doctor' && $my_user_type !== 'admin') {
                http_response_code(403);
                echo json_encode(['error' => 'Only authorized users can cancel appointments']);
                exit;
            }
            
            $appointmentId = $_POST['id'] ?? null;
            
            if (empty($appointmentId) || !is_numeric($appointmentId)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid Appointment ID']);
                exit;
            }
            
            // Update status to 0 (Cancelled)
            if ($my_user_type === 'doctor') {
                // Doctor can only cancel *their* appointments
                $stmt = $pdo->prepare("UPDATE tblappointment SET status = 0 
                                       WHERE id = ? AND doctor = ? AND status != 0");
                $stmt->execute([$appointmentId, $my_user_id]);
            } else { // Admin
                // Admin can cancel any appointment
                $stmt = $pdo->prepare("UPDATE tblappointment SET status = 0 
                                       WHERE id = ? AND status != 0");
                $stmt->execute([$appointmentId]);
            }
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Appointment ID ' . $appointmentId . ' cancelled successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Appointment not found, already cancelled, or you are not authorized to cancel it.']);
            }
            break;

        case 'get_available_slots':
            // (Functionality remains largely the same for booking logic)
            $doctor_id = $_GET['doctor_id'] ?? '';
            $date = $_GET['date'] ?? date('Y-m-d');
            
            if (empty($doctor_id)) {
                http_response_code(400);
                echo json_encode(['error' => 'Doctor ID required']);
                exit;
            }
            
            $dayNum = date('N', strtotime($date));
            
            $stmt = $pdo->prepare("SELECT * FROM tblschedule 
                                     WHERE user_id = ? AND day = ?");
            $stmt->execute([$doctor_id, $dayNum]);
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->prepare("SELECT id, reason FROM tblnoappointment 
                                     WHERE doctor_id = ? AND date = ?");
            $stmt->execute([$doctor_id, $date]);
            $isUnavailable = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($isUnavailable) {
                echo json_encode(['success' => true, 'available' => false, 'reason' => $isUnavailable['reason'] ?? 'Doctor unavailable']);
                exit;
            }
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tblappointment 
                                     WHERE doctor = ? AND booking_date = ? AND status != 0");
            $stmt->execute([$doctor_id, $date]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $slots = [];
            $totalMax = 0;
            
            foreach ($schedules as $schedule) {
                $totalMax += (int)$schedule['max_appointment'];
                $slots[] = [
                    'time' => substr($schedule['time'], 0, 5),
                    'time2' => $schedule['time2'] !== '00:00:00' ? substr($schedule['time2'], 0, 5) : null,
                    'max_appointments' => (int)$schedule['max_appointment']
                ];
            }
            
            $available = ($existing['count'] < $totalMax);
            $remaining = max(0, $totalMax - $existing['count']);
            
            echo json_encode([
                'success' => true,
                'available' => $available,
                'slots' => $slots,
                'booked' => (int)$existing['count'],
                'total_capacity' => $totalMax,
                'remaining' => $remaining
            ]);
            break;

        case 'book_appointment':
            // Added CSRF check by validate_csrf() earlier
            if ($my_user_type !== 'user') {
                http_response_code(403);
                echo json_encode(['error' => 'Only patients can book appointments']);
                exit;
            }
            
            $doctor_id = $_POST['doctor_id'] ?? '';
            $date = $_POST['date'] ?? '';
            
            if (empty($doctor_id) || empty($date)) {
                http_response_code(400);
                echo json_encode(['error' => 'Doctor ID and date required']);
                exit;
            }
            
            // Check if slot is available - using internal request to reuse logic
            // NOTE: Using file_get_contents is risky for internal API calls, 
            // but is kept here to honor the original structure. A function call is safer.
            $response = file_get_contents("calendar_api.php?action=get_available_slots&doctor_id=" . urlencode($doctor_id) . "&date=" . urlencode($date));
            $availability = json_decode($response, true);
            
            if (!($availability['success'] ?? false) || !($availability['available'] ?? false)) {
                echo json_encode(['error' => $availability['reason'] ?? 'No slots available for this date']);
                exit;
            }
            
            // Create appointment
            $stmt = $pdo->prepare("INSERT INTO tblappointment (user_id, doctor, booking_date, status) 
                                     VALUES (?, ?, ?, 2)"); // status 2 = pending
            $stmt->execute([$my_user_id, $doctor_id, $date]);
            $appointmentId = $pdo->lastInsertId();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Appointment booked successfully. It is currently pending confirmation.',
                'appointment_id' => $appointmentId
            ]);
            break;
            
        case 'export_ical':
            $start = $_GET['start'] ?? date('Y-m-d');
            $end = $_GET['end'] ?? date('Y-m-d');
            $appointments = [];

            if ($my_user_type === 'doctor') {
                $sql = "SELECT a.booking_date, CONCAT(i.first_name, ' ', i.last_name) as title
                        FROM tblappointment a JOIN tblinfo i ON i.user_id = a.user_id
                        WHERE a.doctor = ? AND a.booking_date BETWEEN ? AND ? AND a.status = 1"; 
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$my_user_id, $start, $end]);
                $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $filename = 'doctor_schedule_' . $my_user_id . '.ics';
                $cal_name = 'My Doctor Schedule';
                $title_prefix = 'Appointment with ';
            } elseif ($my_user_type === 'user') {
                 $sql = "SELECT a.booking_date, CONCAT('Dr. ', i.first_name, ' ', i.last_name) as title
                        FROM tblappointment a JOIN tblinfo i ON i.user_id = a.doctor
                        WHERE a.user_id = ? AND a.booking_date BETWEEN ? AND ? AND a.status = 1"; 
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$my_user_id, $start, $end]);
                $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $filename = 'my_appointments_' . $my_user_id . '.ics';
                $cal_name = 'My Appointments';
                $title_prefix = 'Appointment with ';
            } else {
                echo json_encode(['success' => false, 'message' => 'Export not supported for Admin view']);
                exit;
            }

            // --- Basic iCal Generation ---
            $ical_content = "BEGIN:VCALENDAR\nVERSION:2.0\nPRODID:-//AppointmentEase//NONSGML v1.0//EN\nX-WR-CALNAME:{$cal_name}\n";
            
            foreach ($appointments as $apt) {
                $title = $title_prefix . $apt['title'];
                // Assuming all-day event for the export since time is not in tblappointment
                $date_start = str_replace('-', '', $apt['booking_date']);
                $date_end = str_replace('-', '', date('Y-m-d', strtotime($apt['booking_date'] . ' +1 day')));

                $ical_content .= "BEGIN:VEVENT\n";
                $ical_content .= "DTSTAMP:".date('Ymd\THis')."Z\n";
                $ical_content .= "UID:".uniqid()."\n";
                $ical_content .= "SUMMARY:{$title}\n";
                $ical_content .= "DTSTART;VALUE=DATE:{$date_start}\n";
                $ical_content .= "DTEND;VALUE=DATE:{$date_end}\n";
                $ical_content .= "END:VEVENT\n";
            }

            $ical_content .= "END:VCALENDAR\n";
            
            echo json_encode([
                'success' => true,
                'ical' => $ical_content,
                'filename' => $filename
            ]);
            
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server Error: ' . $e->getMessage()]);
}

// EOF calendar_api.php
?>