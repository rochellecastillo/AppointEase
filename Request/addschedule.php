<?php
require_once'../Class/User.php';
$u=new User();
$userid= $_POST['userid'];
$day= $_POST['day'];
$time1= $_POST['time1'];
$time2= $_POST['time2'];
$maxapt= $_POST['maxapt'];
echo json_encode($u->addschedule($userid,$day,$time1,$time2,$maxapt));
?>
