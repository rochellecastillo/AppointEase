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
    $data2=$a->displayappointment($doctorid);
    $name=$row['last_name'].', '.$row['first_name'];
    $specialization=$row['specialization'];
    $sched=[];
    $sched2=[];
    $userid=$_SESSION['user_id'];
    foreach($data as $row){
        $sched[]=[
            'day'=>$row['day'],
            'time1'=>$row['time'],
            'time2'=>$row['time2'],
            'max_appointment'=>$row['max_appointment']
        ];
    }
    foreach($data2 as $row){
        $sched2[]=[
            'day'=>$row['booking_date']
        ];
    }
 
    if(isset($_POST['btnbook'])){
        $date= $_POST['btnbook'];
        $res=$a->saveappointment($doctorid,$userid,$date);
        $res = json_decode($res, true);
        echo'
            <script>
                Swal.fire({
                    title: "Booking",
                    text: "'.$res['message'].'",
                    icon: "'.$res['icon'].'"
                });
            </script>
        ';
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

    <div class="modal" id="appointmentmodal">
        <div class="modal-dialog">
            <div class="modal-content">

            <!-- Modal Header -->
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title">Book Appointment</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- Modal body -->
            <div class="modal-body">
                <div class="container">
                    <div class="row">
                        <div class="col-md-12 d-flex">
                            <div><img src="Resources/Images/default_profile.webp" alt="profile" height="50px"></div>
                            <div>
                                <div><h5><?=$name?></h5></div>
                                <div style="margin-top:-0.5em;"><?=$specialization?></div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <h6>Appointment Date: <span class="fw-bold" id="aptdate"></span></h6>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal footer -->
            <div class="modal-footer">
                <form method="POST">
                    <button type="submit" class="btn btn-primary" name="btnbook" id="btnbook"><i class="fa-solid fa-floppy-disk"></i> Save Appointment</button>
                </form>
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal"><i class="fa-solid fa-square-xmark"></i> Close</button>
            </div>

            </div>
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
var sched = <?= json_encode($sched) ?>;
var sched2 = <?= json_encode($sched2) ?>;

// Generate actual events for the next 120 days
var eventlist = [];
var bookinglist = [];
var today = new Date();
var endDate = new Date();
endDate.setDate(today.getDate() + 120); // next 120 days

sched.forEach(item => {
    var current = new Date(today);
    while (current <= endDate) {
        if (current.getDay() === parseInt(item.day)) {
            let dateStr = current.toISOString().split('T')[0];
            eventlist.push({
                title: 'Clinic Schedule',
                start: dateStr + 'T' + item.time1,
                end: dateStr + 'T' + item.time2,
                count:5,
                extendedProps: {
                    maxAppointment: item.max_appointment
                },
                allDay: false
            });
        }
        current.setDate(current.getDate() + 1);
    }
});
sched2.forEach(item=>{
    bookinglist.push({
        title:'Booking',
        start: item.booking_date, 
        end: item.booking_date 
    });
});

var max = eventlist.length > 0 ? eventlist[0].extendedProps.maxAppointment : 0;
var count = eventlist.length;
var page = "booking";

console.log(eventlist);
</script>

<script src="Resources/calendar.js"></script>
<link rel="stylesheet" href="Resources/calendar.css">