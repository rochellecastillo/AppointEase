<?php
Class SMS{
    protected $apikey;
    protected $sendername;
    public function __construct($number,$message){
        $this->apikey='7e99004e15a68a16f7b19f123cd985ff';
        $this->sendername='AppointEase';
        $ch=curl_init();
        $data=array(
            'apikey'=>$this->apikey,
            'number'=>$number,
            'message'=>$message,
            'sendername'=>$this->sendername
        );
        curl_setopt($ch, CURLOPT_URL,'https://api.semaphore.co/api/v4/messages');
        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($data));
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        $response = curl_exec($ch);
        curl_close($ch);
        //echo $response;

    }
}
?>