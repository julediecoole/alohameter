<?php
// extract.php

function fetchBuoyData() {
    $table = "https://surftruths.com/api/buoys.json"; 
    $ch = curl_init ($url);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec ($ch);
    curl_close ($ch);
    print_r($response);



?>