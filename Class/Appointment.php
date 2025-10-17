<?php
require_once'Database.php';
Class Appointment extends Database{
    public function displayappointment($date){
        $sql="select * from tblappointment where bookingdate=?";
    }
}
?>