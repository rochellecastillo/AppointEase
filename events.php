<?php
// events.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'db.php';

// Basic auth: ensure user is logged in
if (!isset($_SESSION['current_user']) || empty($_SESSION['current_user']['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$current = $_SESSION['current_user']; // expected keys: user_id, user_type ('doctor'|'client'|'admin')

// Helper to read JSON payload
$body = json_decode(file_get_contents('php://input'), true);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // FullCalendar will pass start & end to limit range
    $doctor = $_GET['doctor_id'] ?? null;
    $patient = $_GET['patient_id'] ?? null;
    $start = $_GET['start'] ?? null;
    $end = $_GET['end'] ?? null;

    $sql = "SELECT id, title, description, start_datetime, end_datetime, user_id as patient_id, doctor, status 
            FROM tblappointment WHERE 1=1";
    $params = [];

    if ($doctor) {
        $sql .= " AND doctor = :doctor";
        $params[':doctor'] = $doctor;
    }
    if ($patient) {
        $sql .= " AND user_id = :patient";
        $params[':patient'] = $patient;
    }

    if ($start && $end) {
        $sql .= " AND ( (start_datetime BETWEEN :start AND :end) OR (end_datetime BETWEEN :start AND :end) OR (start_datetime <= :start AND (end_datetime IS NULL OR end_datetime >= :end)) )";
        $params[':start'] = $start;
        $params[':end'] = $end;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $events = array_map(function($r){
        return [
            'id' => $r['id'],
            'title' => $r['title'] ?: "Appointment",
            'start' => $r['start_datetime'],
            'end' => $r['end_datetime'],
            'extendedProps' => [
                'description' => $r['description'],
                'patient_id' => $r['patient_id'],
                'doctor_id' => $r['doctor'],
                'status' => $r['status']
            ]
        ];
    }, $rows);

    echo json_encode($events);
    exit;
}

if ($method === 'POST') {
    // Create event
    if (!$body) { http_response_code(400); echo json_encode(['error'=>'Invalid JSON']); exit; }

    // Required: start_datetime, title; optional: end_datetime, doctor_id, patient_id
    $title = trim($body['title'] ?? '');
    $start = $body['start'] ?? null;
    $end = $body['end'] ?? null;
    $doctor_id = $body['doctor_id'] ?? null;
    $patient_id = $body['patient_id'] ?? null;
    $description = $body['description'] ?? null;
    $status = $body['status'] ?? 'Pending';

    if (!$title || !$start) { http_response_code(400); echo json_encode(['error'=>'Missing title or start']); exit; }

    // If user is patient (client), force patient_id = current session user
    if ($current['user_type'] === 'user' || $current['user_type'] === 'client') {
        $patient_id = $current['user_id'];
    }

    // If user is doctor and didn't pass doctor_id, set to current doctor
    if ($current['user_type'] === 'doctor' && empty($doctor_id)) {
        $doctor_id = $current['user_id'];
    }

    // Authorization: patients can create only for themselves; doctors can create only for themselves
    if ($patient_id && $current['user_type'] === 'doctor' && $doctor_id !== $current['user_id']) {
        http_response_code(403); echo json_encode(['error'=>'Forbidden']); exit;
    }

    // Insert into tblappointment. Keep booking_date in sync (date only)
    $booking_date = date('Y-m-d', strtotime($start));

    $sql = "INSERT INTO tblappointment (title, description, booking_date, start_datetime, end_datetime, user_id, doctor, status, created_at, updated_at)
            VALUES (:title, :description, :booking_date, :start, :end, :user_id, :doctor, :status, NOW(), NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':title' => $title,
        ':description' => $description,
        ':booking_date' => $booking_date,
        ':start' => $start,
        ':end' => $end,
        ':user_id' => $patient_id,
        ':doctor' => $doctor_id,
        ':status' => $status
    ]);

    $id = $pdo->lastInsertId();
    http_response_code(201);
    echo json_encode(['id' => $id]);
    exit;
}

if ($method === 'PUT') {
    // Update event
    if (!$body || empty($body['id'])) { http_response_code(400); echo json_encode(['error'=>'Missing id']); exit; }
    $id = (int)$body['id'];

    // Load existing event to check ownership
    $stmt = $pdo->prepare("SELECT id, user_id as patient_id, doctor FROM tblappointment WHERE id = :id");
    $stmt->execute([':id'=>$id]);
    $event = $stmt->fetch();
    if (!$event) { http_response_code(404); echo json_encode(['error'=>'Not found']); exit; }

    // Authorization: doctor can only edit their own events; patient only their own events; admin can edit all
    if ($current['user_type'] === 'doctor' && $event['doctor'] !== $current['user_id']) {
        http_response_code(403); echo json_encode(['error'=>'Forbidden']); exit;
    }
    if (($current['user_type'] === 'user' || $current['user_type'] === 'client') && $event['patient_id'] !== $current['user_id']) {
        http_response_code(403); echo json_encode(['error'=>'Forbidden']); exit;
    }

    // Accept updates for title, start, end, status, description, doctor (admins only)
    $title = $body['title'] ?? null;
    $start = $body['start'] ?? null;
    $end = $body['end'] ?? null;
    $status = $body['status'] ?? null;
    $description = $body['description'] ?? null;
    $doctor_id = $body['doctor_id'] ?? $event['doctor'];

    // If current user is doctor and tries to change doctor_id to another -> forbid
    if ($current['user_type'] === 'doctor' && $doctor_id !== $current['user_id']) {
        $doctor_id = $current['user_id'];
    }

    $booking_date = $start ? date('Y-m-d', strtotime($start)) : date('Y-m-d');

    $sql = "UPDATE tblappointment SET 
                title = COALESCE(:title, title),
                description = COALESCE(:description, description),
                start_datetime = COALESCE(:start, start_datetime),
                end_datetime = COALESCE(:end, end_datetime),
                booking_date = :booking_date,
                doctor = :doctor,
                status = COALESCE(:status, status),
                updated_at = NOW()
            WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':title' => $title,
        ':description' => $description,
        ':start' => $start,
        ':end' => $end,
        ':booking_date' => $booking_date,
        ':doctor' => $doctor_id,
        ':status' => $status,
        ':id' => $id
    ]);

    echo json_encode(['ok' => true]);
    exit;
}

if ($method === 'DELETE') {
    if (!$body || empty($body['id'])) { http_response_code(400); echo json_encode(['error'=>'Missing id']); exit; }
    $id = (int)$body['id'];

    // Check ownership
    $stmt = $pdo->prepare("SELECT id, user_id as patient_id, doctor FROM tblappointment WHERE id = :id");
    $stmt->execute([':id'=>$id]);
    $event = $stmt->fetch();
    if (!$event) { http_response_code(404); echo json_encode(['error'=>'Not found']); exit; }

    if ($current['user_type'] === 'doctor' && $event['doctor'] !== $current['user_id']) {
        http_response_code(403); echo json_encode(['error'=>'Forbidden']); exit;
    }
    if (($current['user_type'] === 'user' || $current['user_type'] === 'client') && $event['patient_id'] !== $current['user_id']) {
        http_response_code(403); echo json_encode(['error'=>'Forbidden']); exit;
    }

    $stmt = $pdo->prepare("DELETE FROM tblappointment WHERE id = :id");
    $stmt->execute([':id'=>$id]);
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error'=>'Method not allowed']);
