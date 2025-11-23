<?php
// schedule_api.php - JSON API for schedule operations
header('Content-Type: application/json');
require 'helpers.php';
require_admin();

$input = json_decode(file_get_contents('php://input'), true);
if(!$input) { echo json_encode(['success'=>false,'msg'=>'No input']); exit; }

$action = $input['action'] ?? '';
$csrf = $input['csrf_token'] ?? '';
if(!validate_csrf($csrf)){ echo json_encode(['success'=>false,'msg'=>'Invalid CSRF']); exit; }

try{
    if($action === 'add'){
        $user_id = $input['user_id'] ?? '';
        $day = intval($input['day'] ?? 0);
        $time = trim($input['time'] ?? '');
        if(!$user_id || !preg_match('/^\d{2}:\d{2}$/',$time)) throw new Exception('Invalid input');
        $stmt = $pdo->prepare("INSERT INTO tblschedule (user_id, day, time) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $day, $time]);
        echo json_encode(['success'=>true,'data'=>['id'=>$pdo->lastInsertId()]]);
        exit;
    } elseif($action === 'delete'){
        $id = intval($input['id'] ?? 0);
        if(!$id) throw new Exception('Invalid id');
        $stmt = $pdo->prepare("DELETE FROM tblschedule WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success'=>true]);
        exit;
    } elseif($action === 'update'){
        $id = intval($input['id'] ?? 0);
        $day = intval($input['day'] ?? 0);
        $time = trim($input['time'] ?? '');
        if(!$id || !preg_match('/^\d{2}:\d{2}$/',$time)) throw new Exception('Invalid input');
        $stmt = $pdo->prepare("UPDATE tblschedule SET day = ?, time = ? WHERE id = ?");
        $stmt->execute([$day, $time, $id]);
        echo json_encode(['success'=>true]);
        exit;
    } else {
        echo json_encode(['success'=>false,'msg'=>'Unknown action']);
        exit;
    }
}catch(Exception $e){
    echo json_encode(['success'=>false,'msg'=>$e->getMessage()]);
    exit;
}
