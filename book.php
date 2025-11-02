<?php 
session_start(); 
require_once'Class/Session.php';
require_once'Class/Appointment.php';
new Session();
$a=new Appointment();
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
    <?php include_once'Resources/navbar.php';?>
    <div class="container">
        <div class="row mt-3">
            <div class="col-md-12">
                <h3><i class="fa-solid fa-book"></i> Book an Appointment</h3>
            </div>
        </div>
        <div class="row mt-3">
            <?php
            foreach($data as $row){
                $name='Dr. '.$row['last_name'].', '.$row['first_name'];
                $specialization=$row['specialization'];
                $image=$row['image']=='' ? 'default_profile.webp' : $row['image']; 
            ?>
            <div class="col-md-3">
                <div class="card login-container">
                    <div class="card-header bg-primary">&nbsp;</div>
                    <div class="card-body">
                        <div class="p-2 text-center"><img src="Resources/Images/<?=$image?>" style="width:80%; aspect-ratio: 4 / 4;" alt="Profile"></div>
                        <div><h4 class="text-center"><?=$name?></h4></div>
                        <div><h6 class="text-center fst-italic text-primary" style="margin-top:-0.7em;"><?=$specialization?></h6></div>
                    </div>
                    <div class="card-footer text-end">
                        <button class="btn btn-sm btn-primary"><i class="fa-solid fa-plus"></i> Book</button>
                    </div>
                </div>
            </div>
            <?php
            }
            ?>
        </div>
    </div>

</body>
</html>
<style>
    .login-container{
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }
</style>

<script src="Resources/calendar.js"></script>
<link rel="stylesheet" href="Resources/calendar.css">