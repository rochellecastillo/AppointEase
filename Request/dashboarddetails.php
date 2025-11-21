<?php
include_once'../Class/Appointment.php';
include_once'../Class/User.php';
$a=new Appointment();
$u=new User();
$choice=$_POST['choice'];
if($choice=='totalappointment'){
    $data=$a->displayallappointments('all');
    $row=[];
    foreach($data as $d){
        $row[]=$d;
    }
    echo json_encode($data);
}else if($choice=='approved'){
    $data=$a->displayallappointments('approved');
    $row=[];
    foreach($data as $d){
        $row[]=$d;
    }
    echo json_encode($data);
}else if($choice=='totaldoctor'){
    $data=$u->displayallusers('user');
    $row=[];
    foreach($data as $d){
        $row[]=$d;
    }
    echo json_encode($data);
}else if($choice=='totalpatient'){
    $data=$u->displayallusers('client');
    $row=[];
    foreach($data as $d){
        $row[]=$d;
    }
    echo json_encode($data);
}
?>