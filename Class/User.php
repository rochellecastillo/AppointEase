<?php
require_once'Database.php';
Class User extends Database{
    public function login($un,$pw){
        $sql="select u.*,i.contact from tbluser u inner join tblinfo i on u.user_id=i.user_id where u.user_name=?";
        $stmt=$this->conn->prepare($sql);
        $stmt->execute([$un]);
        $data=$stmt->fetch(PDO::FETCH_ASSOC);
        if($data){
            //if(password_verify($pw,$data['password'])){
            if($pw==$data['password']){
                return $data;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
    public function displayuserinfo($userid){
        $sql="select i.*,u.user_name,u.password,u.user_type from tblinfo i inner join tbluser u on i.user_id =u.user_id where i.user_id=?";
        $stmt=$this->conn->prepare($sql);
        $stmt->execute([$userid]);
        $data=$stmt->fetch(PDO::FETCH_ASSOC);
        return $data;
    }
    public function adduser($ln,$fn,$mn,$specialization,$bdate,$gender,$address,$contact,$role,$image){
         try {
            $idnum = 'U'.date("ymd-") . mt_rand(0, 999);
            $pw=$ln.mt_rand(00000,99999);
            $this->conn->beginTransaction();

            $sql1 = "INSERT INTO tbluser (user_id, user_name, password, user_type, status) VALUES (?, ?, ?, ?, 1)";
            $sql2 = "INSERT INTO tblinfo (user_id, last_name, first_name, middle_name, specialization, bdate, gender, address, contact, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt1 = $this->conn->prepare($sql1);
            $stmt2 = $this->conn->prepare($sql2);
            $stmt2->execute([$idnum, $ln, $fn, $mn, $specialization, $bdate, $gender, $address, $contact, $image]);
            $stmt1->execute([$idnum, $idnum, $pw, $role]);
            $this->conn->commit();

            return [
                'success' => true,
                'user_id' => $idnum,
                'message' => 'User added successfully!',
                'icon'    => 'success'
            ];

        } catch (PDOException $e) {
            $this->conn->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'icon'    => 'error'
            ];
        }
    }
    public function updateuserinfo($userid,$ln,$fn,$mn,$bdate,$gender,$address,$contact){
        $sql="update tblinfo set last_name=?, first_name=?, middle_name=?, bdate=?, gender=?,address=?,contact=? where user_id=?";
        $stmt=$this->conn->prepare($sql);
        if($stmt->execute([$ln,$fn,$mn,$bdate,$gender,$address,$contact,$userid])){
            return ([
                'success'=>true,
                'message'=>'Record Successfully Updated!',
                'icon'=>'success'
            ]);
        }else{
            return([
                'success'=>false,
                'message'=>$this->conn->error,
                'icon'=>'error'
            ]);
        }

    }
    public function displayallusers($role){
        $sql="select i.*,u.status,u.user_name,u.password,u.user_type from tblinfo i inner join tbluser u on u.user_id=i.user_id where user_type=? order by i.last_name";
        $stmt=$this->conn->prepare($sql);
        $stmt->execute([$role]);
        $data=$stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }
    public function updatestatus($userid,$stat){
        $stat=$stat=='true' ? 1: 0;
        $sql="update tbluser set status=? where user_id=?";
        $stmt=$this->conn->prepare($sql);
        if($stmt->execute([$stat,$userid])){
            return([
                'success' => true,
                'message' => 'User status changed!',
                'icon'    => 'success'
            ]);
        }else{
            return([
                'success' => false,
                'message' => $this->conn->error,
                'icon'    => 'error'
            ]);
        }
    }
    public function changepassword($userid,$pw,$pw2,$pw3){
        $sql="select * from tbluser where user_id=?";
        $stmt=$this->conn->prepare($sql);
        $stmt->execute([$userid]);
        $data=$stmt->fetch(PDO::FETCH_ASSOC);
        if($data['password']==$pw){
            if($pw2==$pw3){
                $sql2="update tbluser set password=? where user_id=?";
                $stmt=$this->conn->prepare($sql2);
                if($stmt->execute([$pw2,$userid])){
                    return([
                        'success'=>true,
                        'message'=>'Password Successfully Changed!',
                        'icon'=>'success'
                    ]);
                }else{
                    return([
                        'success'=>false,
                        'message'=>$this->conn->error,
                        'icon'=>'error'
                    ]);
                }
            }else{
                return([
                    'success'=>false,
                    'message'=>'New Password did not match',
                    'icon'=>'warning'
                ]);
            }
        }else{
            return([
                    'succes'=>false,
                    'message'=>'Incorrect Password',
                    'icon'=>'warning'
                ]);
        }
    }
    public function addspecialization($specialization){
        $sql="insert into tblspecialization values(NULL,?)";
        $stmt=$this->conn->prepare($sql);
        if($stmt->execute([$specialization])){
            return([
                'success'=>true,
                'message'=>'Specialization Successfully Added!',
                'icon'=>'success'
            ]);
        }else{
            return([
                'success'=>false,
                'message'=>$this->conn->error,
                'icon'=>'error'
            ]);
        }
    }
    public function deletespecialization($id){
        $sql="delete from tblspecialization where id=?";
        $stmt=$this->conn->prepare($sql);
        if($stmt->execute([$id])){
            return([
                'success'=>true,
                'message'=>'Specialization Successfully Deleted!',
                'icon'=>'success'
            ]);
        }else{
            return([
                'success'=>false,
                'message'=>$this->conn->error,
                'icon'=>'error'
            ]);
        }
    }
    public function displayspecialization(){
        $sql="select * from tblspecialization order by specialization";
        $stmt=$this->conn->prepare($sql);
        $stmt->execute();
        $data=$stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }
    public function addschedule($userid,$day,$time1,$time2,$maxapt){
        $sql="insert into tblschedule values(NULL,?,?,?,?,?)";
        $stmt=$this->conn->prepare($sql);
        if($stmt->execute([$userid,$day,$time1,$time2,$maxapt])){
            return([
                'success'=>true,
                'message'=>'Schedule Successfully Deleted!',
                'icon'=>'success'
            ]);
        }else{
            return([
                'success'=>false,
                'message'=>$this->conn->error,
                'icon'=>'error'
            ]);
        }
    }
}
?>