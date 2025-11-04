<?php 
session_start(); 
require_once'Class/Session.php';
require_once'Class/Appointment.php';
require_once'Class/User.php';
new Session();
$a=new Appointment();
$u=new User();
$data=$a->viewdoctors();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AppointEase-Client</title>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
 
    <?php include_once'Resources/include.php'?>
</head>
<body class="text-secondary">
    <?php
    $doctorid=$_GET['doctorid'];
    $row=$u->displayuserinfo($doctorid);
    $data=$a->displaydoctorschedule($doctorid);
    $name=$row['last_name'].', '.$row['first_name'];
    $specialization=$row['specialization'];
    $sched=[];
    foreach($data as $row){
        $sched[]=[
            'day'=>$row['day'],
            'time1'=>$row['time'],
            'time2'=>$row['time2'],
            'max_appointment'=>$row['max_appointment']
        ];
    }
    ?>
    <?php include_once'Resources/navbar.php';?>
    <div class="container">
        <div class="row mt-3">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header text-white bg-primary"><h3><i class="fa-solid fa-user-doctor"></i> Doctor</h3></div>
                    <div class="card-body mb-3">
                        <div class="d-flex">
                            <div><img src="Resources/Images/default_profile.webp" height="80" alt=""></div>
                            <div class="ms-3">
                                <div><h4><?=$name?></h4></div>
                                <div><h5><?=$specialization?></h5></div>
                            </div>
                        </div>    
                    
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                
                <div class="card">
                    <div class="card-header text-white bg-primary"><h3><i class="fa-solid fa-book"></i> Select Appointment Date</h3></div>
                    <div class="card-body mb-3">
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-3">
        </div>
            
    </div>


    
</body>
</html>
<style>
    .login-container{
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }
</style>
<script>
var sched=<?=json_encode($sched)?>;
var eventlist = sched.map(function(item) {
  return {
    title: 'Clinic Schedule',
    daysOfWeek: [ parseInt(item.day) ], 
    startTime: item.time1,
    endTime: item.time2,
    maxAppointment:item.max_appointment
  };
});
var max=eventlist[0].maxAppointment;
var count=eventlist.length-1;
var page="booking";
</script>
<script src="Resources/calendar.js"></script>
<link rel="stylesheet" href="Resources/calendar.css">