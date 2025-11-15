<?php 
session_start(); 
require_once'Class/Session.php';
new Session();

// Assuming you have a database connection class
require_once'Class/Database.php';
$db = new Database();

// Get doctor's user_id from session
$doctor_id = $_SESSION['user_id'] ?? '';

// Fetch doctor's information
$doctor_info = $db->query("SELECT * FROM tblinfo WHERE user_id = ?", [$doctor_id]);

// Fetch today's appointments
$today = date('Y-m-d');
$today_appointments = $db->query("
    SELECT a.*, i.first_name, i.last_name, i.contact, i.address 
    FROM tblappointment a 
    JOIN tblinfo i ON a.user_id = i.user_id 
    WHERE a.doctor = ? AND a.booking_date = ? 
    ORDER BY a.booking_date ASC
", [$doctor_id, $today]);

// Fetch upcoming appointments
$upcoming_appointments = $db->query("
    SELECT a.*, i.first_name, i.last_name, i.contact 
    FROM tblappointment a 
    JOIN tblinfo i ON a.user_id = i.user_id 
    WHERE a.doctor = ? AND a.booking_date > ? 
    ORDER BY a.booking_date ASC 
    LIMIT 10
", [$doctor_id, $today]);

// Fetch doctor's schedule
$schedule = $db->query("SELECT * FROM tblschedule WHERE user_id = ?", [$doctor_id]);

// Count statistics
$total_today = count($today_appointments);
$total_upcoming = $db->query("SELECT COUNT(*) as count FROM tblappointment WHERE doctor = ? AND booking_date > ?", [$doctor_id, $today])[0]['count'] ?? 0;
$total_completed = $db->query("SELECT COUNT(*) as count FROM tblappointment WHERE doctor = ? AND booking_date < ? AND status = 1", [$doctor_id, $today])[0]['count'] ?? 0;

$days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AppointEase - Doctor Dashboard</title>
    <?php include_once'Resources/include.php'?>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
</head>
<body class="text-secondary bg-light">
    <?php include_once'Resources/usernavbar.php';?>
    
    <div class="container-fluid">
        <!-- Header Section -->
        <div class="row mt-4 mb-3">
            <div class="col-md-6">
                <h2 class="fw-bold">Welcome, Dr. <?php echo htmlspecialchars($doctor_info[0]['first_name'] ?? ''); ?> <?php echo htmlspecialchars($doctor_info[0]['last_name'] ?? ''); ?></h2>
                <p class="text-muted"><?php echo htmlspecialchars($doctor_info[0]['specialization'] ?? 'General Practitioner'); ?></p>
            </div>
            <div class="col-md-6 text-end">
                <h3 id="time" class="text-primary fw-bold"></h3>
                <p class="text-muted" id="date"></p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Today's Appointments</h6>
                                <h2 class="fw-bold text-primary"><?php echo $total_today; ?></h2>
                            </div>
                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                <i class="fa-solid fa-calendar-day fa-2x text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Upcoming Appointments</h6>
                                <h2 class="fw-bold text-success"><?php echo $total_upcoming; ?></h2>
                            </div>
                            <div class="bg-success bg-opacity-10 p-3 rounded">
                                <i class="fa-solid fa-calendar-plus fa-2x text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Completed</h6>
                                <h2 class="fw-bold text-info"><?php echo $total_completed; ?></h2>
                            </div>
                            <div class="bg-info bg-opacity-10 p-3 rounded">
                                <i class="fa-solid fa-calendar-check fa-2x text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row">
            <!-- Today's Appointments -->
            <div class="col-md-8">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-0 pt-3">
                        <h5 class="fw-bold"><i class="fa-solid fa-calendar-day text-primary"></i> Today's Appointments</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($today_appointments)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fa-solid fa-calendar-xmark fa-3x mb-3"></i>
                                <p>No appointments scheduled for today</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Patient Name</th>
                                            <th>Contact</th>
                                            <th>Address</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($today_appointments as $apt): ?>
                                        <tr>
                                            <td class="fw-bold"><?php echo htmlspecialchars($apt['first_name'] . ' ' . $apt['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($apt['contact']); ?></td>
                                            <td><?php echo htmlspecialchars($apt['address']); ?></td>
                                            <td>
                                                <?php if ($apt['status'] == 0): ?>
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                <?php elseif ($apt['status'] == 1): ?>
                                                    <span class="badge bg-success">Completed</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Cancelled</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary view-details" data-id="<?php echo $apt['id']; ?>">
                                                    <i class="fa-solid fa-eye"></i> View
                                                </button>
                                                <?php if ($apt['status'] == 0): ?>
                                                <button class="btn btn-sm btn-outline-success complete-apt" data-id="<?php echo $apt['id']; ?>">
                                                    <i class="fa-solid fa-check"></i> Complete
                                                </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Calendar View -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-0 pt-3">
                        <h5 class="fw-bold"><i class="fa-solid fa-calendar text-primary"></i> Appointment Calendar</h5>
                    </div>
                    <div class="card-body">
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- My Schedule -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fa-solid fa-clock"></i> My Schedule</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($schedule)): ?>
                            <p class="text-muted text-center">No schedule set</p>
                        <?php else: ?>
                            <?php foreach ($schedule as $sched): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded">
                                <div>
                                    <h6 class="mb-1 fw-bold"><?php echo $days[$sched['day']]; ?></h6>
                                    <small class="text-muted">
                                        <?php echo date('g:i A', strtotime($sched['time'])); ?> - 
                                        <?php echo date('g:i A', strtotime($sched['time2'])); ?>
                                    </small>
                                    <br>
                                    <small class="text-primary">Max: <?php echo $sched['max_appointment']; ?> patients</small>
                                </div>
                                <i class="fa-solid fa-user-doctor fa-2x text-primary"></i>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Upcoming Appointments -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="fa-solid fa-calendar-plus"></i> Upcoming Appointments</h6>
                    </div>
                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                        <?php if (empty($upcoming_appointments)): ?>
                            <p class="text-muted text-center">No upcoming appointments</p>
                        <?php else: ?>
                            <?php foreach ($upcoming_appointments as $apt): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3 p-2 border-bottom">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($apt['first_name'] . ' ' . $apt['last_name']); ?></h6>
                                    <small class="text-muted">
                                        <i class="fa-solid fa-calendar"></i> 
                                        <?php echo date('M d, Y', strtotime($apt['booking_date'])); ?>
                                    </small>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fa-solid fa-phone"></i> 
                                        <?php echo htmlspecialchars($apt['contact']); ?>
                                    </small>
                                </div>
                                <span class="badge bg-warning text-dark">Pending</span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include_once'Resources/tawkto.php'; ?>
</body>
</html>

<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .card {
        border-radius: 10px;
        transition: transform 0.2s;
    }
    .card:hover {
        transform: translateY(-2px);
    }
    #calendar {
        max-width: 100%;
        margin: 0 auto;
    }
    .fc-event {
        cursor: pointer;
    }
    .table th {
        font-weight: 600;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
</style>

<script>
$(document).ready(function(){
    // Update time and date
    function updateTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit',
            second: '2-digit'
        });
        const dateString = now.toLocaleDateString('en-US', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
        $("#time").text(timeString);
        $("#date").text(dateString);
    }
    updateTime();
    setInterval(updateTime, 1000);

    // Initialize FullCalendar
    var calendarEl = document.getElementById('calendar');
    
    // Fetch appointments for calendar
    $.ajax({
        url: 'get_doctor_appointments.php',
        method: 'GET',
        success: function(response) {
            var events = JSON.parse(response);
            
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: events,
                eventClick: function(info) {
                    Swal.fire({
                        title: info.event.title,
                        html: `
                            <p><strong>Date:</strong> ${info.event.start.toLocaleDateString()}</p>
                            <p><strong>Patient ID:</strong> ${info.event.extendedProps.user_id}</p>
                        `,
                        icon: 'info'
                    });
                },
                eventColor: '#0d6efd'
            });
            
            calendar.render();
        }
    });

    // View appointment details
    $('.view-details').click(function() {
        var aptId = $(this).data('id');
        // Add AJAX call to fetch and display appointment details
        Swal.fire({
            title: 'Loading...',
            text: 'Fetching appointment details',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Add your AJAX call here
    });

    // Complete appointment
    $('.complete-apt').click(function() {
        var aptId = $(this).data('id');
        Swal.fire({
            title: 'Complete Appointment?',
            text: "Mark this appointment as completed",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, complete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Add AJAX call to update appointment status
                $.ajax({
                    url: 'API/complete_appointment.php',
                    method: 'POST',
                    data: { id: aptId },
                    success: function(response) {
                        Swal.fire(
                            'Completed!',
                            'Appointment has been marked as completed.',
                            'success'
                        ).then(() => {
                            location.reload();
                        });
                    }
                });
            }
        });
    });
});
</script>