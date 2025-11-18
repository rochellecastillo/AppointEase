<?php
header('Content-Type: application/json');
$id=$_GET['id'];
require_once'../Class/Appointment.php';
$a=new Appointment();
echo $a->cancel($id);
?>