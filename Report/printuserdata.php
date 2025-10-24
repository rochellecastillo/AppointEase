<?php
require('../Resources/fpdf184/fpdf.php');
require_once'../Class/User.php';
$u=new User();
$userid=$_GET['userid'];
$row=$u->displayuserinfo($userid);
if($row){
    if($row['middle_name']==''){
        $name=$row['last_name'].', '.$row['first_name'];
    }else{
        $name=$row['last_name'].', '.$row['first_name'].' - '.$row['middle_name'];
    }
    $address=$row['address'];
    $gender=$row['gender'];
    $bdate=$row['bdate'];
    $contact=$row['contact'];
    $userid=$_GET['userid'];
    $username=$row['user_name'];
    $password=$row['password'];
}

$pdf = new FPDF();
$pdf->setMargins(20,20,20);
$pdf->AddPage();
$pdf->Image('../Resources/Images/logo.png',70,13,20);
$pdf->SetFont('Times', 'B', 15);
$pdf->Cell(0, 5, 'AppointEase', 0, 1, 'C');
$pdf->SetFont('Arial', 'I', 8);
$pdf->Ln(5);
$pdf->Cell(0, 5, 'Online Scheduling and Reservation System fox Untalan General  Hospital Services
', 0, 1, 'C');
$pdf->Ln(10);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 5, 'User Information', 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Ln(10);

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(25, 5, 'Name:', 0, 0, 'L');
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 5, $name, 0, 1, 'L');

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(25, 5, 'Address:', 0, 0, 'L');
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 5, $address, 0, 1, 'L');

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(25, 5, 'Gender:', 0, 0, 'L');
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 5, $gender, 0, 1, 'L');

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(25, 5, 'Birth Date:', 0, 0, 'L');
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 5, $bdate, 0, 1, 'L');

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(25, 5, 'Contact No.:', 0, 0, 'L');
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 5, $contact, 0, 1, 'L');


$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 5, 'User Credentials', 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Ln(10);

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(25, 5, 'User ID:', 0, 0, 'L');
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 5, $userid, 0, 1, 'L');

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(25, 5, 'Username:', 0, 0, 'L');
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 5, $username, 0, 1, 'L');

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(25, 5, 'Password:', 0, 0, 'L');
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 5, $password, 0, 1, 'L');

$pdf->Output();
?>