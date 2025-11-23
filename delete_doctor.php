<?php
// delete_doctor.php
require 'helpers.php';
require_admin();

if($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: manage_schedule.php'); exit; }
$csrf = $_POST['csrf_token'] ?? '';
if(!validate_csrf($csrf)){ die('Invalid CSRF'); }
$user_id = $_POST['user_id'] ?? '';
if(!$user_id) die('Missing user');

try{
    $pdo->beginTransaction();
    // delete schedules
    $stmt = $pdo->prepare("DELETE FROM tblschedule WHERE user_id = ?");
    $stmt->execute([$user_id]);
    // mark user as deleted (soft)
    $stmt = $pdo->prepare("UPDATE tbluser SET status = 0, user_type = 'deleted' WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $pdo->commit();
    header('Location: manage_schedule.php');
    exit;
}catch(Exception $e){
    $pdo->rollBack();
    die('Error: ' . $e->getMessage());
}
