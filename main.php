<?php 
session_start(); 
require_once 'Class/Session.php';
require_once 'Class/Database.php';
new Session();

// Fetch dashboard statistics
$db = new Database();
$conn = $db->getConnection();

// Get total appointments
$stmt = $conn->query("SELECT COUNT(*) as total FROM tblappointment");
$totalAppointments = $stmt->fetch()['total'];

// Get pending appointments
$stmt = $conn->query("SELECT COUNT(*) as total FROM tblappointment WHERE status = 0");
$pendingAppointments = $stmt->fetch()['total'];

// Get total doctors
$stmt = $conn->query("SELECT COUNT(*) as total FROM tbluser WHERE user_type = 'user'");
$totalDoctors = $stmt->fetch()['total'];

// Get total patients
$stmt = $conn->query("SELECT COUNT(*) as total FROM tbluser WHERE user_type = 'client'");
$totalPatients = $stmt->fetch()['total'];

// Get recent appointments with details
$stmt = $conn->prepare("
    SELECT 
        a.id, 
        a.booking_date, 
        a.status,
        CONCAT(ci.first_name, ' ', ci.last_name) as client_name,
        CONCAT(di.first_name, ' ', di.last_name) as doctor_name,
        di.specialization
    FROM tblappointment a
    JOIN tblinfo ci ON a.user_id = ci.user_id
    JOIN tblinfo di ON a.doctor = di.user_id
    ORDER BY a.booking_date DESC
    LIMIT 10
");
$stmt->execute();
$recentAppointments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - AppointEase</title>
    <?php include_once 'Resources/include.php'?>
    <style>
        .stat-card {
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-label {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .calendar-container {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .table-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .badge-pending {
            background-color: #ffc107;
            color: #000;
        }
        .badge-approved {
            background-color: #28a745;
        }
        .badge-cancelled {
            background-color: #dc3545;
        }
    </style>
</head>
<body class="bg-light text-secondary">
    <?php include_once 'Resources/navbar.php';?>
    
    <div class="container-fluid py-4">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stat-card bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Total Appointments</div>
                            <div class="stat-number"><?= $totalAppointments ?></div>
                        </div>
                        <div class="stat-icon">
                            <i class="fa-solid fa-calendar-check"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card bg-warning text-dark">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Pending</div>
                            <div class="stat-number"><?= $pendingAppointments ?></div>
                        </div>
                        <div class="stat-icon">
                            <i class="fa-solid fa-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card bg-success text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Total Doctors</div>
                            <div class="stat-number"><?= $totalDoctors ?></div>
                        </div>
                        <div class="stat-icon">
                            <i class="fa-solid fa-user-doctor"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card bg-info text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Total Patients</div>
                            <div class="stat-number"><?= $totalPatients ?></div>
                        </div>
                        <div class="stat-icon">
                            <i class="fa-solid fa-users"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row">
            <!-- Calendar -->
            <div class="col-lg-8 mb-4">
                <div class="calendar-container bg-white p-3">
                    <h5 class="mb-3"><i class="fa-solid fa-calendar me-2"></i>Appointment Calendar</h5>
                    <iframe 
                        class="w-100" 
                        src="https://calendar.google.com/calendar/embed?src=sorahaiiro1%40gmail.com&ctz=Asia%2FManila" 
                        style="border: 0" 
                        height="500" 
                        frameborder="0" 
                        scrolling="no">
                    </iframe>
                </div>
            </div>

            <!-- Recent Appointments -->
            <div class="col-lg-4 mb-4">
                <div class="table-container">
                    <h5 class="mb-3"><i class="fa-solid fa-list me-2"></i>Recent Appointments</h5>
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-hover">
                            <thead class="sticky-top bg-white">
                                <tr>
                                    <th>Date</th>
                                    <th>Patient</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentAppointments as $apt): ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($apt['booking_date'])) ?></td>
                                    <td>
                                        <small><?= htmlspecialchars($apt['client_name']) ?></small><br>
                                        <small class="text-muted"><?= htmlspecialchars($apt['doctor_name']) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($apt['status'] == 0): ?>
                                            <span class="badge badge-pending">Pending</span>
                                        <?php elseif ($apt['status'] == 1): ?>
                                            <span class="badge badge-approved">Approved</span>
                                        <?php else: ?>
                                            <span class="badge badge-cancelled">Cancelled</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row">
            <div class="col-12">
                <div class="table-container">
                    <h5 class="mb-3"><i class="fa-solid fa-bolt me-2"></i>Quick Actions</h5>
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <button class="btn btn-primary w-100">
                                <i class="fa-solid fa-user-plus me-2"></i>Add Doctor
                            </button>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button class="btn btn-success w-100">
                                <i class="fa-solid fa-calendar-plus me-2"></i>New Appointment
                            </button>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button class="btn btn-info w-100">
                                <i class="fa-solid fa-file-export me-2"></i>Export Report
                            </button>
                        </div>
                        <div class="col-md-3 mb-2">
                            <button class="btn btn-secondary w-100">
                                <i class="fa-solid fa-gear me-2"></i>Settings
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include_once 'Resources/tawkto.php'; ?>
</body>
</html>