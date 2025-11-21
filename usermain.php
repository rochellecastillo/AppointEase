<?php 
session_start(); 
require_once'Class/Session.php';
require_once'Class/Database.php';
require_once'Class/Appointment.php';
new Session();

// Get doctor's user_id from session
$doctor_id = $_SESSION['user_id'] ?? '';

class DoctorDashboard extends Database {
    
    // Get doctor's appointments for today
    public function getTodayAppointments($doctor_id) {
        $query = "SELECT a.*, i.first_name, i.last_name, i.middle_name, i.contact, i.address, i.gender, i.bdate
                  FROM tblappointment a
                  INNER JOIN tblinfo i ON a.user_id = i.user_id
                  WHERE a.doctor = :doctor_id 
                  AND a.booking_date = CURDATE()
                  ORDER BY a.booking_date ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':doctor_id', $doctor_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get upcoming appointments
    public function getUpcomingAppointments($doctor_id) {
        $query = "SELECT a.*, i.first_name, i.last_name, i.middle_name, i.contact, i.address, i.gender, i.bdate
                  FROM tblappointment a
                  INNER JOIN tblinfo i ON a.user_id = i.user_id
                  WHERE a.doctor = :doctor_id 
                  AND a.booking_date > CURDATE()
                  ORDER BY a.booking_date ASC
                  LIMIT 10";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':doctor_id', $doctor_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get doctor's schedule
    public function getDoctorSchedule($doctor_id) {
        $query = "SELECT * FROM tblschedule WHERE user_id = :doctor_id ORDER BY day ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':doctor_id', $doctor_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get statistics
    public function getStatistics($doctor_id) {
        $stats = [];
        
        // Today's appointments
        $query = "SELECT COUNT(*) as count FROM tblappointment 
                  WHERE doctor = :doctor_id AND booking_date = CURDATE()";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':doctor_id', $doctor_id);
        $stmt->execute();
        $stats['today'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // This week's appointments
        $query = "SELECT COUNT(*) as count FROM tblappointment 
                  WHERE doctor = :doctor_id 
                  AND YEARWEEK(booking_date, 1) = YEARWEEK(CURDATE(), 1)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':doctor_id', $doctor_id);
        $stmt->execute();
        $stats['week'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // This month's appointments
        $query = "SELECT COUNT(*) as count FROM tblappointment 
                  WHERE doctor = :doctor_id 
                  AND MONTH(booking_date) = MONTH(CURDATE())
                  AND YEAR(booking_date) = YEAR(CURDATE())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':doctor_id', $doctor_id);
        $stmt->execute();
        $stats['month'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Total appointments
        $query = "SELECT COUNT(*) as count FROM tblappointment WHERE doctor = :doctor_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':doctor_id', $doctor_id);
        $stmt->execute();
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        return $stats;
    }
}



$daysOfWeek = ['', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AppointEase - Doctor Dashboard</title>
    <?php include_once'Resources/include.php'?>
</head>
<body class="text-secondary bg-light">
    <?php 
    $a=new Appointment();
    include_once'Resources/navbar.php';?>
    <?php
    if(isset($_POST['btnsave'])){
        $id=$_POST['bookingid'];
        $status=$_POST['bookstatus'];
        $res=json_decode($a->updatestatus($id,$status),true);
        echo'
        <script>
            Swal.fire({
                title: "Booking Status",
                text: "'.$res['message'].'",
                icon: "'.$res['icon'].'"
            });
        </script>
        ';
    }
    $dashboard = new DoctorDashboard();
    $todayAppointments = $dashboard->getTodayAppointments($doctor_id);
    $upcomingAppointments = $dashboard->getUpcomingAppointments($doctor_id);
    $schedule = $dashboard->getDoctorSchedule($doctor_id);
    $stats = $dashboard->getStatistics($doctor_id);
    ?>
    <div class="container-fluid py-4">
        <!-- Header with Time -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h2 class="mb-0">Doctor Dashboard</h2>
                <p class="text-muted">Welcome back, Dr. <?php echo $_SESSION['first_name'] ?? 'Doctor'; ?></p>
            </div>
            <div class="col-md-6 text-end">
                <h3 id="time" class="text-primary"></h3>
                <p class="text-muted mb-0"><?php echo date('l, F j, Y'); ?></p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Today's Appointments</h6>
                                <h2 class="mb-0 text-primary"><?php echo $stats['today']; ?></h2>
                            </div>
                            <div class="text-primary" style="font-size: 2.5rem;">
                                <i class="fa-solid fa-calendar-day"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">This Week</h6>
                                <h2 class="mb-0 text-success"><?php echo $stats['week']; ?></h2>
                            </div>
                            <div class="text-success" style="font-size: 2.5rem;">
                                <i class="fa-solid fa-calendar-week"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">This Month</h6>
                                <h2 class="mb-0 text-info"><?php echo $stats['month']; ?></h2>
                            </div>
                            <div class="text-info" style="font-size: 2.5rem;">
                                <i class="fa-solid fa-calendar-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Total Appointments</h6>
                                <h2 class="mb-0 text-warning"><?php echo $stats['total']; ?></h2>
                            </div>
                            <div class="text-warning" style="font-size: 2.5rem;">
                                <i class="fa-solid fa-clipboard-list"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row">
            <!-- Today's Appointments -->
            <div class="col-md-8 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0"><i class="fa-solid fa-user-clock"></i> Today's Appointments</h5>
                    </div>
                    <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                        <?php if (empty($todayAppointments)): ?>
                            <div class="text-center text-muted py-5">
                                <i class="fa-solid fa-calendar-xmark fa-3x mb-3"></i>
                                <p>No appointments scheduled for today</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Patient</th>
                                            <th>Gender</th>
                                            <th>Contact</th>
                                            <th>Address</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($todayAppointments as $apt):?>     
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($apt['first_name'] . ' ' . $apt['last_name']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($apt['user_id']); ?></small>
                                            </td>
                                            <td><?php echo ucfirst($apt['gender']); ?></td>
                                            <td><?php echo htmlspecialchars($apt['contact']); ?></td>
                                            <td><small><?php echo htmlspecialchars($apt['address']); ?></small></td>
                                            <td>
                                                <?php if ($apt['status'] == 0): ?>
                                                    <span class="badge bg-warning">Confirmed</span>
                                                <?php elseif ($apt['status'] == 1): ?>
                                                    <span class="badge bg-success">Completed</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Cancelled</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button 
                                                    class="btn btn-sm btn-primary btnselect" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#updatemodal"
                                                    data-id="<?= $apt['id']; ?>"
                                                    data-fname="<?= $apt['first_name']; ?>"
                                                    data-lname="<?= $apt['last_name']; ?>"
                                                    data-address="<?= htmlspecialchars($apt['address']); ?>"
                                                    data-contact="<?= $apt['contact']; ?>">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Upcoming Appointments -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0"><i class="fa-solid fa-calendar-plus"></i> Upcoming Appointments</h5>
                    </div>
                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                        <?php if (empty($upcomingAppointments)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fa-solid fa-calendar-xmark fa-2x mb-2"></i>
                                <p>No upcoming appointments</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($upcomingAppointments as $apt): ?>
                                <div class="list-group-item border-0 border-bottom">
                                    <div class="d-flex w-100 justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($apt['first_name'] . ' ' . $apt['last_name']); ?></h6>
                                            <p class="mb-1 text-muted">
                                                <i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($apt['contact']); ?>
                                            </p>
                                            <small class="text-muted">
                                                <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($apt['address']); ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-info"><?php echo date('M d, Y', strtotime($apt['booking_date'])); ?></span>
                                            <br>
                                            <?php if ($apt['status'] == 0): ?>
                                                <span class="badge bg-warning mt-1">Confirmed</span>
                                            <?php elseif ($apt['status'] == 1): ?>
                                                <span class="badge bg-success mt-1">Completed</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- My Schedule -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0"><i class="fa-solid fa-clock"></i> My Schedule</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($schedule)): ?>
                            <div class="text-center text-muted py-3">
                                <i class="fa-solid fa-calendar-xmark fa-2x mb-2"></i>
                                <p>No schedule set</p>
                                <a href="schedule.php" class="btn btn-sm btn-primary">Set Schedule</a>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($schedule as $sched): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo $daysOfWeek[$sched['day']]; ?></h6>
                                            <p class="mb-0 text-muted">
                                                <i class="fa-solid fa-clock"></i> 
                                                <?php echo date('g:i A', strtotime($sched['time'])); ?> - 
                                                <?php echo date('g:i A', strtotime($sched['time2'])); ?>
                                            </p>
                                            <small class="text-muted">
                                                Max: <?php echo $sched['max_appointment']; ?> patients
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <!--div class="mt-3">
                                <a href="schedule.php" class="btn btn-sm btn-outline-primary w-100">
                                    <i class="fa-solid fa-pen"></i> Edit Schedule
                                </a>
                            </div-->
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <!--div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0"><i class="fa-solid fa-bolt"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="appointments.php" class="btn btn-outline-primary">
                                <i class="fa-solid fa-calendar"></i> View All Appointments
                            </a>
                            <a href="schedule.php" class="btn btn-outline-success">
                                <i class="fa-solid fa-clock"></i> Manage Schedule
                            </a>
                            <a href="no_appointment.php" class="btn btn-outline-warning">
                                <i class="fa-solid fa-calendar-xmark"></i> Set Unavailable Dates
                            </a>
                            <a href="profile.php" class="btn btn-outline-info">
                                <i class="fa-solid fa-user"></i> Edit Profile
                            </a>
                        </div>
                    </div>
                </div-->
            </div>
        </div>
    </div>

    
<!-- The Modal -->
<div class="modal" id="updatemodal">
  <div class="modal-dialog">
    <div class="modal-content">

      <!-- Modal Header -->
      <div class="modal-header bg-primary text-white">
        <h4 class="modal-title"><i class="fa-solid fa-book"></i> Booking Status</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
       <form method="POST">                              
      <!-- Modal body -->
        <div class="modal-body">
            <input type="hidden" id="bookingid" name="bookingid">
            <h4 id="clientname">Juan dela Cruz</h4>
            <div id="clientcontact" style="margin-top:-0.5em">09999999999</div>
            <div id="clientaddress" style="margin-top:-0.5em">Rosario, Batangas</div>
            <div class="row"><hr></div>
            <div class="row">
                <div class="col-md-5">
                    <label for="bookstatus">Status</label>
                    <select class="form-control" name="bookstatus" id="bookstatus" required>
                        <option value="" selected readonly>-</option>
                        <option value="1">Completed</option>
                        <option value="2">Cancelled</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Modal footer -->
        <div class="modal-footer">
            <button type="submit" name="btnsave" class="btn btn-success"><i class="fa-solid fa-floppy-disk"></i> Apply</button>
            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
        </div>
      </form>   

    </div>
  </div>
</div>
</body>
</html>

<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .card {
        transition: transform 0.2s;
    }
    
    .card:hover {
        transform: translateY(-2px);
    }
    
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    .list-group-item {
        transition: background-color 0.2s;
    }
    
    .list-group-item:hover {
        background-color: #f8f9fa;
    }
    
    .badge {
        padding: 0.35em 0.65em;
    }
    
    /* Custom scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
    }
    
    ::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    
    ::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
</style>

<script>
    $(document).ready(function(){
        // Update time display
        let tm = $("#time");
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit',
                hour12: true 
            });
            tm.text(timeString);
        }   
        updateTime();
        setInterval(updateTime, 1000);
    });

    function viewPatient(appointmentId) {
        // Navigate to patient details page or show modal
        window.location.href = 'appointment_details.php?id=' + appointmentId;
    }

    $(".btnselect").click(function(){
        $("#bookingid").val($(this).data("id"));
        $("#clientname").text($(this).data("fname")+" "+$(this).data("lname"));
        $("#clientcontact").text($(this).data("contact"));
        $("#clientaddress").text($(this).data("address"));
    });
</script>