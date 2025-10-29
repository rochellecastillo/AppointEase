<?php
require_once'../Class/User.php';
$u=new User();
$userid=$_POST['userid'];
$pw=$_POST['cpw'];
$pw2=$_POST['cpw2'];
$pw3=$_POST['cpw3'];
echo json_encode($u->changepassword($userid,$pw,$pw2,$pw3));
?>