<?php
require_once'Database.php';
Class Appointment extends Database{
    public function displayappointment($date){
        $sql="select * from tblappointment where bookingdate=?";
    }
    public function viewdoctors(){
        $sql="select i.* from tblinfo i inner join tbluser u on i.user_id=u.user_id where u.user_type='user' and u.status=1";
        $stmt=$this->conn->prepare($sql);
        $stmt->execute();
        $data=$stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }
}
?>