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
    <?php include_once'Resources/include.php'?>
</head>
<body class="text-secondary">
    <?php include_once'Resources/navbar.php';?>
    <div class="container">
        
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