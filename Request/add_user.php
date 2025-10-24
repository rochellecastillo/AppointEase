<?php
require_once'../Class/User.php';
$u=new User();
    $ln=$_POST['last_name'];
    $fn=$_POST['first_name'];
    $mn=$_POST['middle_name'];
    $bdate=$_POST['bdate'];
    $gender=$_POST['gender'];
    $contact=$_POST['contact'];
    $address=$_POST['address'];
    echo json_encode($u->adduser($ln,$fn,$mn,$bdate,$gender,$address,$contact,'user'));
?>