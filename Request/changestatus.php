<?php
$userid=$_POST['userid'];
$stat=$_POST['stat'];
require_once'../Class/User.php';
$u=new User();
    echo json_encode($u->updatestatus($userid,$stat));
?>