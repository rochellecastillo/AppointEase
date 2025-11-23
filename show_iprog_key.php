<?php
require 'iprog_sms.php'; // uses IPROG_API_TOKEN constant

$key = defined('IPROG_API_TOKEN') ? IPROG_API_TOKEN : '';
echo "<h3>IPROG API TOKEN DIAGNOSTICS</h3>";
echo "Masked: " . htmlspecialchars(substr($key,0,6) . str_repeat('*', max(0, strlen($key)-12)) . substr($key,-6)) . "<br>";
echo "Length: " . strlen($key) . "<br>";

echo "<h4>Hex dump (first 80 chars)</h4><pre>";
$len = min(strlen($key), 80);
for($i=0;$i<$len;$i++){
    echo sprintf("%02x ", ord($key[$i]));
}
echo "</pre>";

echo "<h4>Printable or ord()</h4><pre>";
for($i=0;$i<$len;$i++){
    $c = $key[$i];
    if(ord($c) >= 32 && ord($c) <= 126){
        echo htmlspecialchars($c);
    } else {
        echo "[ord:" . ord($c) . "]";
    }
}
echo "</pre>";
