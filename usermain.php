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
    <title>AppointEase-Doctor</title>
    <?php include_once'Resources/include.php'?>
</head>
<body class="text-secondary">
    <?php include_once'Resources/usernavbar.php';?>
    <div class="container">
        <div class="row mt-3 justify-content-end">
            <div class="col-md-3 text-end">
                <h3 id="time"></h3>
            </div>
        </div>
    </div>
</body>
</html>
<style>
    #login-container{
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }
</style>
<script>
    $(document).ready(function(){
        let tm=$("#time");
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString(); // Example: 10:25:30 AM
            tm.text(timeString);
        }
        updateTime();
        setInterval(updateTime,1000);
    });
</script>