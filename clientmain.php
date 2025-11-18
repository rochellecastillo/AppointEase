<?php 
session_start(); 
require_once 'Class/Session.php';
require_once 'Class/Database.php';
new Session();

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'] ?? '';

// Fetch user's appointments
$stmt = $conn->prepare("
    SELECT 
        a.id,
        a.booking_date,
        a.status,
        di.first_name AS doctor_first_name,
        di.last_name AS doctor_last_name,
        di.specialization,
        di.image AS doctor_image,
        s.time AS start_time,
        s.time2 AS end_time
    FROM tblappointment a
    LEFT JOIN tblinfo di ON a.doctor = di.user_id
    LEFT JOIN (
        SELECT user_id, time, time2
        FROM tblschedule
        GROUP BY user_id
    ) s ON a.doctor = s.user_id
    WHERE a.user_id = :user_id
    ORDER BY a.booking_date DESC;

");
$stmt->execute(['user_id' => $userId]);
$appointments = $stmt->fetchAll();

// Get upcoming appointment count
$upcomingCount = 0;
foreach ($appointments as $apt) {
    if (strtotime($apt['booking_date']) >= strtotime('today') && $apt['status'] == 0) {
        $upcomingCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - AppointEase</title>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <?php include_once 'Resources/include.php'?>
    <style>
        .appointment-card {
            border-radius: 10px;
            padding: 20px;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .stat-badge {
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            color: white;
            margin-bottom: 15px;
        }
        .doctor-profile {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            border: 2px solid #e9ecef;
        }
        .doctor-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .calendar-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .fc {
            border-radius: 8px;
        }
        .appointment-details {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 20px;
        }
        .no-appointment {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        .appointment-list {
            max-height: 400px;
            overflow-y: auto;
        }
        .appointment-item {
            border-left: 4px solid #007bff;
            padding: 15px;
            margin-bottom: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .appointment-item:hover {
            transform: translateX(5px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .appointment-item.past {
            opacity: 0.6;
            border-left-color: #6c757d;
        }
    </style>
</head>
<body class="bg-light text-secondary">
    <?php include_once 'Resources/navbar.php';?>
    
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h4><i class="fa-solid fa-calendar-check me-2"></i>My Appointments</h4>
                <p class="text-muted">Manage and track your medical appointments</p>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="stat-badge bg-primary">
                    <h2><?= count($appointments) ?></h2>
                    <small>Total Appointments</small>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stat-badge bg-warning">
                    <h2><?= $upcomingCount ?></h2>
                    <small>Upcoming</small>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stat-badge bg-success">
                    <h2><?= count(array_filter($appointments, fn($a) => $a['status'] == 1)) ?></h2>
                    <small>Completed</small>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row">
            <!-- Calendar -->
            <div class="col-lg-8 mb-4">
                <div class="calendar-container">
                    <div id="calendar"></div>
                </div>

                <!-- Appointments List -->
                
            </div>

            <!-- Appointment Details Sidebar -->
            <div class="col-lg-4">
                <div class="appointment-details" id="appointmentDetails">
                    <h6 class="text-center mb-4">
                        <i class="fa-solid fa-info-circle me-2"></i>Appointment Details
                    </h6>
                    
                    <div id="detailsContent">
                        <div class="no-appointment">
                            <i class="fa-solid fa-hand-pointer fa-2x mb-3 text-muted"></i>
                            <p>Select an appointment to view details</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include_once 'Resources/tawkto.php'; ?>

    <script>
        // Prepare appointment data for calendar
        var eventlist = <?= json_encode(array_map(function($apt) {
            return [
                'id' => $apt['id'],
                'title' => 'Dr. ' . $apt['doctor_last_name'],
                'doctor' => 'Dr. ' . $apt['doctor_last_name'].', '.$apt['doctor_first_name'],
                'specialization' =>$apt['specialization'],
                'start' => $apt['booking_date'],
                'time'=>$apt['start_time'].'-'.$apt['end_time'],
                'status'=>$apt['status'],
                'backgroundColor' => $apt['status'] == 0 ? '#ffc107' : ($apt['status'] == 1 ? '#28a745' : '#dc3545'),
                'extendedProps' => $apt
            ];
        }, $appointments)) ?>;
        
        var page = 'client';

        function showAppointmentDetails(appointment) {
            const detailsHtml = `
                <div class="text-center mb-3">
                    <h6 class="text-primary">${new Date(appointment.booking_date).toLocaleDateString('en-US', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    })}</h6>
                    ${appointment.start_time ? `<small class="text-muted">${appointment.start_time} - ${appointment.end_time}</small>` : ''}
                </div>
                
                <hr>
                
                <div class="doctor-profile mb-3">
                    <div class="d-flex align-items-center">
                        <img src="${appointment.doctor_image || 'Resources/Images/default_profile.webp'}" 
                             class="doctor-image me-3" 
                             alt="Doctor">
                        <div>
                            <h5 class="mb-1">Dr. ${appointment.doctor_first_name} ${appointment.doctor_last_name}</h5>
                            <p class="text-muted mb-0">
                                <i class="fa-solid fa-stethoscope me-1"></i>
                                ${appointment.specialization || 'General Practitioner'}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <strong>Status:</strong>
                    ${appointment.status == 0 ? '<span class="status-badge status-pending">Pending</span>' : 
                      appointment.status == 1 ? '<span class="status-badge status-approved">Approved</span>' : 
                      '<span class="status-badge status-cancelled">Cancelled</span>'}
                </div>

                ${appointment.status == 0 ? `
                    <button class="btn btn-danger w-100 mb-2" onclick="cancelAppointment(${appointment.id})">
                        <i class="fa-solid fa-times me-2"></i>Cancel Appointment
                    </button>
                    <button class="btn btn-outline-primary w-100" onclick="rescheduleAppointment(${appointment.id})">
                        <i class="fa-solid fa-calendar-alt me-2"></i>Reschedule
                    </button>
                ` : ''}
            `;
            
            document.getElementById('detailsContent').innerHTML = detailsHtml;
        }

        function cancelAppointment(id) {
            if (confirm('Are you sure you want to cancel this appointment?')) {
                // Add your cancel appointment logic here
                console.log('Cancelling appointment:', id);
            }
        }

        function rescheduleAppointment(id) {
            // Add your reschedule logic here
            console.log('Rescheduling appointment:', id);
        }
    </script>
    <script src="Resources/calendar.js"></script>
    <link rel="stylesheet" href="Resources/calendar.css">
</body>
</html>