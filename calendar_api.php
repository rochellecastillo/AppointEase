<?php
// calendar_api.php - API for calendar functionality (updated)
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require 'db.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$my_user_id = $_SESSION['user_id'];
$my_user_type = strtolower($_SESSION['user_type']);
$action = $_GET['action'] ?? $_POST['action'] ?? '';

function respond($arr) {
    echo json_encode($arr);
    exit;
}

function get_status_details($status_code) {
    switch ((int)$status_code) {
        case 1:
            return ['text' => 'Confirmed', 'color' => '#10b981', 'class' => 'bg-green-100 text-green-700'];
        case 2:
            return ['text' => 'Pending', 'color' => '#f59e0b', 'class' => 'bg-yellow-100 text-yellow-700'];
        case 0:
            return ['text' => 'Cancelled', 'color' => '#6b7280', 'class' => 'bg-gray-100 text-gray-700'];
        case 3:
            return ['text' => 'Completed', 'color' => '#06b6d4', 'class' => 'bg-sky-100 text-sky-700'];
        default:
            return ['text' => 'Unknown', 'color' => '#ef4444', 'class' => 'bg-red-100 text-red-700'];
    }
}

function validate_csrf() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return true;
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($token) || $token !== ($_SESSION['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Security Error: Invalid CSRF token.']);
        exit;
    }
    return true;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') validate_csrf();

    switch ($action) {
        case 'get_events':
            $start = $_GET['start'] ?? date('Y-m-01');
            $end = $_GET['end'] ?? date('Y-m-t');

            $events = [];
            $schedules = [];

            if ($my_user_type === 'doctor') {
                // Appointments for doctor
                $sql = "SELECT a.id, a.booking_date as date, a.booking_time, a.status,
                               CONCAT(i.first_name, ' ', i.last_name) as title,
                               i.contact, i.user_id as patient_id
                        FROM tblappointment a
                        JOIN tblinfo i ON i.user_id = a.user_id
                        WHERE a.doctor = ? AND a.booking_date BETWEEN ? AND ?
                        ORDER BY a.booking_date";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$my_user_id, $start, $end]);
                $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // schedules
                $stmt = $pdo->prepare("SELECT s.* FROM tblschedule s WHERE s.user_id = ?");
                $stmt->execute([$my_user_id]);
                $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // unavailable ranges (date_start .. date_end). We'll expose each day as an event
                $stmt = $pdo->prepare("SELECT doctor_id, date_start, date_end, reason FROM tblnoappointment WHERE doctor_id = ? AND (date_start <= ? AND date_end >= ?)");
                // Note: We'll fetch records that intersect the requested window by using start>=? and end<=? would miss crossing ranges. So fetch any with overlap.
                $stmt->execute([$my_user_id, $end, $start]);
                $unavailables = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($unavailables as $na) {
                    $dStart = new DateTime($na['date_start']);
                    $dEnd = new DateTime($na['date_end']);
                    // iterate inclusive
                    for ($dt = clone $dStart; $dt <= $dEnd; $dt->modify('+1 day')) {
                        $dayStr = $dt->format('Y-m-d');
                        $events[] = [
                            'id' => 'unavailable_' . $dayStr,
                            'title' => 'Unavailable',
                            'start' => $dayStr,
                            'allDay' => true,
                            'color' => '#ef4444',
                            'extendedProps' => ['type' => 'unavailable', 'reason' => $na['reason']]
                        ];
                    }
                }
            } elseif ($my_user_type === 'admin') {
                $sql = "SELECT a.id, a.booking_date as date, a.booking_time, a.status,
                               CONCAT(pi.first_name, ' ', pi.last_name) as patient_name,
                               CONCAT('Dr. ', di.first_name, ' ', di.last_name) as doctor_name,
                               di.contact as doctor_contact, pi.contact as patient_contact
                        FROM tblappointment a
                        LEFT JOIN tblinfo pi ON pi.user_id = a.user_id
                        LEFT JOIN tblinfo di ON di.user_id = a.doctor
                        WHERE a.booking_date BETWEEN ? AND ?
                        ORDER BY a.booking_date";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$start, $end]);
                $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else { // patient
                $sql = "SELECT a.id, a.booking_date as date, a.booking_time, a.status,
                               CONCAT('Dr. ', i.first_name, ' ', i.last_name) as doctor_name,
                               i.contact as doctor_contact
                        FROM tblappointment a
                        JOIN tblinfo i ON i.user_id = a.doctor
                        WHERE a.user_id = ? AND a.booking_date BETWEEN ? AND ?
                        ORDER BY a.booking_date";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$my_user_id, $start, $end]);
                $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            // format appointments
            foreach ($appointments as $apt) {
                $details = get_status_details((int)$apt['status']);
                $title = $apt['title'] ?? ($apt['patient_name'] ?? ($apt['doctor_name'] ?? 'Appointment'));
                // include time if available
                $displayTitle = $title . ' (' . $details['text'] . ')';
                $ext = [
                    'type' => 'appointment',
                    'status_text' => $details['text'],
                    'status_class' => $details['class'],
                    'appointment_id' => $apt['id'],
                    'booking_time' => $apt['booking_time'] ? substr($apt['booking_time'],0,5) : null
                ];
                if ($my_user_type === 'doctor') {
                    $ext['patient_name'] = $apt['title'] ?? null;
                    $ext['patient_contact'] = $apt['contact'] ?? null;
                } elseif ($my_user_type === 'admin') {
                    $ext['patient_name'] = $apt['patient_name'] ?? null;
                    $ext['doctor_name'] = $apt['doctor_name'] ?? null;
                    $ext['doctor_contact'] = $apt['doctor_contact'] ?? null;
                    $ext['patient_contact'] = $apt['patient_contact'] ?? null;
                } else {
                    $ext['doctor_name'] = $apt['doctor_name'] ?? null;
                    $ext['doctor_contact'] = $apt['doctor_contact'] ?? null;
                }

                $event = [
                    'id' => 'apt_' . $apt['id'],
                    'start' => $apt['date'],
                    'color' => $details['color'],
                    'title' => $displayTitle,
                    'extendedProps' => $ext
                ];

                // if there's a time, include time-specific fields (FullCalendar can show allDay false)
                if (!empty($apt['booking_time'])) {
                    // create a combined ISO datetime (assume server dates are for local timezone)
                    $event['start'] = $apt['date'] . 'T' . substr($apt['booking_time'],0,8);
                    $event['allDay'] = false;
                } else {
                    $event['allDay'] = true;
                }

                $events[] = $event;
            }

            respond(['success' => true, 'events' => $events, 'schedules' => $schedules]);
            break;

        case 'get_day_details':
            $date = $_GET['date'] ?? date('Y-m-d');
            $appointments = [];
            $schedules = [];
            $unavailable_reason = null;

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                respond(['success' => false, 'error' => 'Invalid date format']);
            }

            if ($my_user_type === 'doctor') {
                $stmt = $pdo->prepare("SELECT a.*, CONCAT(i.first_name, ' ', i.last_name) as patient_name, i.contact, i.address, i.email
                                       FROM tblappointment a
                                       JOIN tblinfo i ON i.user_id = a.user_id
                                       WHERE a.doctor = ? AND a.booking_date = ?
                                       ORDER BY a.id");
                $stmt->execute([$my_user_id, $date]);
                $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $dayNum = date('N', strtotime($date));
                $stmt = $pdo->prepare("SELECT * FROM tblschedule WHERE user_id = ? AND day = ?");
                $stmt->execute([$my_user_id, $dayNum]);
                $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // check if date falls within any no-appointment range
                $stmt = $pdo->prepare("SELECT reason FROM tblnoappointment WHERE doctor_id = ? AND date_start <= ? AND date_end >= ? LIMIT 1");
                $stmt->execute([$my_user_id, $date, $date]);
                $unavailable = $stmt->fetch(PDO::FETCH_ASSOC);
                $unavailable_reason = $unavailable['reason'] ?? null;
            } else {
                $sql = "SELECT a.*, CONCAT('Dr. ', di.first_name, ' ', di.last_name) as doctor_name, di.contact as doctor_contact, di.email as doctor_email, CONCAT(pi.first_name, ' ', pi.last_name) as patient_name, pi.contact as patient_contact
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

            // append status details
            foreach ($appointments as &$apt) {
                $status_details = get_status_details((int)$apt['status']);
                $apt['status_text'] = $status_details['text'];
                $apt['status_class'] = $status_details['class'];
            }
            unset($apt);

            respond([
                'success' => true,
                'appointments' => $appointments,
                'schedules' => $schedules,
                'unavailable_reason' => $unavailable_reason,
                'date' => $date
            ]);
            break;

        case 'cancel_appointment':
            if ($my_user_type !== 'doctor' && $my_user_type !== 'admin') {
                http_response_code(403);
                respond(['success' => false, 'error' => 'Only authorized users can cancel appointments']);
            }
            $appointmentId = $_POST['id'] ?? null;
            if (empty($appointmentId) || !is_numeric($appointmentId)) {
                http_response_code(400);
                respond(['success' => false, 'error' => 'Invalid Appointment ID']);
            }

            if ($my_user_type === 'doctor') {
                $stmt = $pdo->prepare("UPDATE tblappointment SET status = 0 WHERE id = ? AND doctor = ? AND status != 0");
                $stmt->execute([$appointmentId, $my_user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE tblappointment SET status = 0 WHERE id = ? AND status != 0");
                $stmt->execute([$appointmentId]);
            }

            if ($stmt->rowCount() > 0) {
                respond(['success' => true, 'message' => 'Appointment ID ' . intval($appointmentId) . ' cancelled successfully.']);
            } else {
                respond(['success' => false, 'message' => 'Appointment not found, already cancelled, or you are not authorized to cancel it.']);
            }
            break;

        case 'get_available_slots':
            $doctor_id = $_GET['doctor_id'] ?? '';
            $date = $_GET['date'] ?? date('Y-m-d');
            if (empty($doctor_id)) {
                http_response_code(400);
                respond(['success' => false, 'error' => 'Doctor ID required']);
            }
            $dayNum = date('N', strtotime($date));
            $stmt = $pdo->prepare("SELECT * FROM tblschedule WHERE user_id = ? AND day = ?");
            $stmt->execute([$doctor_id, $dayNum]);
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("SELECT id, reason FROM tblnoappointment WHERE doctor_id = ? AND date_start <= ? AND date_end >= ?");
            $stmt->execute([$doctor_id, $date, $date]);
            $isUnavailable = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($isUnavailable) {
                respond(['success' => true, 'available' => false, 'reason' => $isUnavailable['reason'] ?? 'Doctor unavailable']);
            }

            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tblappointment WHERE doctor = ? AND booking_date = ? AND status != 0");
            $stmt->execute([$doctor_id, $date]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            $slots = [];
            $totalMax = 0;
            foreach ($schedules as $schedule) {
                $totalMax += (int)$schedule['max_appointment'];
                $slots[] = [
                    'time' => substr($schedule['time'],0,5),
                    'time2' => $schedule['time2'] !== '00:00:00' ? substr($schedule['time2'],0,5) : null,
                    'max_appointments' => (int)$schedule['max_appointment']
                ];
            }
            $available = ($existing['count'] < $totalMax);
            $remaining = max(0, $totalMax - $existing['count']);

            respond([
                'success' => true,
                'available' => $available,
                'slots' => $slots,
                'booked' => (int)$existing['count'],
                'total_capacity' => $totalMax,
                'remaining' => $remaining
            ]);
            break;

        case 'book_appointment':
            if ($my_user_type !== 'user') {
                http_response_code(403);
                respond(['success' => false, 'error' => 'Only patients can book appointments']);
            }
            $doctor_id = $_POST['doctor_id'] ?? '';
            $date = $_POST['date'] ?? '';
            if (empty($doctor_id) || empty($date)) {
                http_response_code(400);
                respond(['success' => false, 'error' => 'Doctor ID and date required']);
            }

            // Instead of calling the API via file_get_contents, reuse logic inline for robustness
            // Reuse get_available_slots logic:
            $dayNum = date('N', strtotime($date));
            $stmt = $pdo->prepare("SELECT * FROM tblschedule WHERE user_id = ? AND day = ?");
            $stmt->execute([$doctor_id, $dayNum]);
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt = $pdo->prepare("SELECT id, reason FROM tblnoappointment WHERE doctor_id = ? AND date_start <= ? AND date_end >= ?");
            $stmt->execute([$doctor_id, $date, $date]);
            $isUnavailable = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($isUnavailable) respond(['success' => false, 'error' => $isUnavailable['reason'] ?? 'No slots available']);

            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tblappointment WHERE doctor = ? AND booking_date = ? AND status != 0");
            $stmt->execute([$doctor_id, $date]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            $totalMax = 0;
            foreach ($schedules as $s) $totalMax += (int)$s['max_appointment'];
            if ((int)$existing['count'] >= $totalMax) {
                respond(['success' => false, 'error' => 'No slots remaining for this date']);
            }

            $stmt = $pdo->prepare("INSERT INTO tblappointment (booking_date, user_id, doctor, status) VALUES (?, ?, ?, 2)");
            $stmt->execute([$date, $my_user_id, $doctor_id]);
            $appointmentId = $pdo->lastInsertId();

            respond(['success' => true, 'message' => 'Appointment booked successfully. It is currently pending confirmation.', 'appointment_id' => $appointmentId]);
            break;

        case 'export_ical':
            $start = $_GET['start'] ?? date('Y-m-d');
            $end = $_GET['end'] ?? date('Y-m-d');
            $appointments = [];

            if ($my_user_type === 'doctor') {
                $sql = "SELECT a.booking_date, a.booking_time, CONCAT(i.first_name, ' ', i.last_name) as title
                        FROM tblappointment a JOIN tblinfo i ON i.user_id = a.user_id
                        WHERE a.doctor = ? AND a.booking_date BETWEEN ? AND ? AND a.status = 1";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$my_user_id, $start, $end]);
                $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $filename = 'doctor_schedule_' . $my_user_id . '.ics';
                $cal_name = 'My Doctor Schedule';
                $title_prefix = 'Appointment with ';
            } elseif ($my_user_type === 'user') {
                $sql = "SELECT a.booking_date, a.booking_time, CONCAT('Dr. ', i.first_name, ' ', i.last_name) as title
                        FROM tblappointment a JOIN tblinfo i ON i.user_id = a.doctor
                        WHERE a.user_id = ? AND a.booking_date BETWEEN ? AND ? AND a.status = 1";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$my_user_id, $start, $end]);
                $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $filename = 'my_appointments_' . $my_user_id . '.ics';
                $cal_name = 'My Appointments';
                $title_prefix = 'Appointment with ';
            } else {
                respond(['success' => false, 'message' => 'Export not supported for Admin view']);
            }

            // Build ICS (basic)
            $ical = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//AppointmentEase//NONSGML v1.0//EN\r\nX-WR-CALNAME:{$cal_name}\r\n";
            foreach ($appointments as $apt) {
                $title = $title_prefix . ($apt['title'] ?? 'Appointment');
                if (!empty($apt['booking_time'])) {
                    // Use date + time; note: times are treated as floating (no TZ). Consider converting if needed.
                    $dtstart = new DateTime($apt['booking_date'] . ' ' . $apt['booking_time']);
                    $dtend = clone $dtstart;
                    $dtend->modify('+30 minutes'); // default duration
                    $ical .= "BEGIN:VEVENT\r\n";
                    $ical .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
                    $ical .= "UID:" . uniqid() . "@appoint-ease\r\n";
                    $ical .= "SUMMARY:" . addcslashes($title, "\r\n") . "\r\n";
                    $ical .= "DTSTART:" . $dtstart->format('Ymd\THis') . "\r\n";
                    $ical .= "DTEND:" . $dtend->format('Ymd\THis') . "\r\n";
                    $ical .= "END:VEVENT\r\n";
                } else {
                    // All-day event
                    $date_start = str_replace('-', '', $apt['booking_date']);
                    $date_end = str_replace('-', '', date('Y-m-d', strtotime($apt['booking_date'] . ' +1 day')));
                    $ical .= "BEGIN:VEVENT\r\n";
                    $ical .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
                    $ical .= "UID:" . uniqid() . "@appoint-ease\r\n";
                    $ical .= "SUMMARY:" . addcslashes($title, "\r\n") . "\r\n";
                    $ical .= "DTSTART;VALUE=DATE:" . $date_start . "\r\n";
                    $ical .= "DTEND;VALUE=DATE:" . $date_end . "\r\n";
                    $ical .= "END:VEVENT\r\n";
                }
            }
            $ical .= "END:VCALENDAR\r\n";

            respond(['success' => true, 'ical' => $ical, 'filename' => $filename]);
            break;

        default:
            http_response_code(400);
            respond(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    respond(['success' => false, 'error' => 'Server Error: ' . $e->getMessage()]);
}