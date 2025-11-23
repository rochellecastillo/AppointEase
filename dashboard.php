<?php
// dashboard.php
session_start();
if(!isset($_SESSION['user_id'])){
  header('Location: login.php'); exit;
}
?>
<!doctype html>
<html>
<head><title>Dashboard</title></head>
<body>
  <h2>Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?></h2>
  <p>Role: <?= htmlspecialchars($_SESSION['user_type']) ?></p>
  <p>
    <a href="book_form.php">Book Appointment</a> |
    <a href="my_appointments.php">My Appointments</a> |
    <a href="logout.php">Logout</a>
  </p>
</body>
</html>
