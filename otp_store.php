<?php
// otp_store.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
define('OTP_FILE', sys_get_temp_dir() . '/appoint_otp.json');


/** File read/write helpers **/
function read_otp_file(){
  if(!file_exists(OTP_FILE)) return [];
  $json = @file_get_contents(OTP_FILE);
  if(!$json) return [];
  $data = json_decode($json, true);
  return is_array($data) ? $data : [];
}

function write_otp_file($arr){
  @file_put_contents(OTP_FILE, json_encode($arr, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
}

/** OTP operations (session + file fallback) **/
function set_otp_for_user($user_id, $otp_code, $expires_timestamp){
  if(!isset($_SESSION['otp_store'])) $_SESSION['otp_store'] = [];
  $_SESSION['otp_store'][$user_id] = [
    'code' => $otp_code,
    'expires' => $expires_timestamp,
    'used' => false,
    'created' => time()
  ];
  $filedata = read_otp_file();
  $filedata[$user_id] = $_SESSION['otp_store'][$user_id];
  write_otp_file($filedata);
}

function get_otp_for_user($user_id){
  if(isset($_SESSION['otp_store'][$user_id])){
    return $_SESSION['otp_store'][$user_id];
  }
  $filedata = read_otp_file();
  return $filedata[$user_id] ?? null;
}

function mark_otp_used($user_id){
  if(isset($_SESSION['otp_store'][$user_id])){
    $_SESSION['otp_store'][$user_id]['used'] = true;
  }
  $filedata = read_otp_file();
  if(isset($filedata[$user_id])){
    $filedata[$user_id]['used'] = true;
    write_otp_file($filedata);
  }
}

function delete_otp_for_user($user_id){
  if(isset($_SESSION['otp_store'][$user_id])){
    unset($_SESSION['otp_store'][$user_id]);
  }
  $filedata = read_otp_file();
  if(isset($filedata[$user_id])){
    unset($filedata[$user_id]);
    write_otp_file($filedata);
  }
}
