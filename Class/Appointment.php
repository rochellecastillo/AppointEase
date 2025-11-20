<?php
require_once'Database.php';
Class Appointment extends Database{
    public function displayappointment($doctorid){
        $sql="select * from tblappointment where doctor=?";
        $stmt=$this->conn->prepare($sql);
        $stmt->execute([$doctorid]);
        $data=$stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }
    public function viewdoctors(){
        $sql="select i.* from tblinfo i inner join tbluser u on i.user_id=u.user_id where u.user_type='user' and u.status=1";
        $stmt=$this->conn->prepare($sql);
        $stmt->execute();
        $data=$stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }
    public function displaydoctorschedule($doctorid){
        $sql="select * from tblschedule where user_id=?";
        $stmt=$this->conn->prepare($sql);
        $stmt->execute([$doctorid]);
        $data=$stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }
    public function saveappointment($doctorid,$clientid,$date){
        $sql="select * from tblappointment where booking_date=? and user_id=? and doctor=?";
        $stmt=$this->conn->prepare($sql);
        $stmt->execute([$date,$clientid,$doctorid]);
        $data=$stmt->fetch(PDO::FETCH_ASSOC);
        if(!$data){
            $sql="insert into tblappointment values(NULL,?,?,?,0)";
            $stmt=$this->conn->prepare($sql);
            if($stmt->execute([$date,$clientid,$doctorid])){
                return json_encode([
                    'success'=>true,
                    'message'=>'Booking Successful',
                    'icon'=>'success'

                ]);
            }else{
                return json_encode([
                    'success'=>false,
                    'message'=>$this->conn->error,
                    'icon'=>'danger'
                ]);
            }
        }else{
            return json_encode([
                'success'=>false,
                'message'=>'Booking Already Exist',
                'icon'=>'warning'
            ]);
        }
       
    }
    public function cancel($id){
        $sql="update tblappointment set Status=2 where id=?";
        $stmt=$this->conn->prepare($sql);
        if($stmt->execute([$id])){
            return json_encode([
                'success'=>true,
                'message'=>'Booking Cancelled',
                'icon'=>'success'

            ]);
        }else{
            return json_encode([
                'success'=>false,
                'message'=>$this->conn->error,
                'icon'=>'danger'
            ]);
        }
    }
    public function updatestatus($id,$status){
        $sql="update tblappointment set Status=? where id=?";
        $stmt=$this->conn->prepare($sql);
        if($stmt->execute([$status,$id])){
            return json_encode([
                'success'=>true,
                'message'=>'Appointment Status Changed',
                'icon'=>'success'

            ]);
        }else{
            return json_encode([
                'success'=>false,
                'message'=>$this->conn->error,
                'icon'=>'danger'
            ]);
        }
    }
}
?>