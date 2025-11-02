<?php 
session_start(); 
require_once'Class/Session.php';
new Session();
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
                <h5>My Appointments <i class="fa-solid fa-calendar-check"></i></h5>
                <hr>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-8">
                <div id="calendar"></div>
            </div>
            <div class="col-md-4 ps-3">
                <div class="row">
                    <h6 class="text-center">Apointment Details</h6>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <h6>Date: <span></span></h6>
                        
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <h6>Doctor</h6>
                        <div class="d-flex">
                            <div><img src="Resources/Images/default_profile.webp" height="60px;" alt="Image"></div>
                            <div class="ps-3">
                                <div><h3>Dr. Juan dela Cruz</h3></div>
                                <div style="margin-top:-0.7em;"><h5>Specialization</h5></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <button class="form-control btn btn-primary" name="btncancel">Cancel Appointment</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
        include_once'Resources/tawkto.php';
    ?>
</body>
</html>
<style>
    #login-container{
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }
</style>
<script>
    var eventlist=[
      {
        title: 'Mr Castro',
        start: '2025-11-20T10:00:00',
      },
      {
        title: 'Mr Johnson',
        start: '2025-11-21T10:00:00',
      },
      
    ];
</script>
<script src="Resources/calendar.js"></script>
<link rel="stylesheet" href="Resources/calendar.css">