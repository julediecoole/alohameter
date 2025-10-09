<?php
date_default_timezone_set('Europe/Zurich');
require_once 'config.php'; // DB-Verbindung

//verschiedene bojen
$buoys = [
    1 => [
        'name' => 'oahu',
        'url' => "https://surftruths.com/api/buoys/51101/readings.json",
        'meteo' => "https://api.open-meteo.com/v1/forecast?latitude=24.359&longitude=-162.081&current=temperature_2m,wind_speed_10m&timezone=auto&wind_speed_unit=kn"
    ],
    2 => [
        'name' => 'kauai',      
        'url' => "https://surftruths.com/api/buoys/51001/readings.json",
        'meteo' => "https://api.open-meteo.com/v1/forecast?latitude=24.451&longitude=-162.008&current=temperature_2m,wind_speed_10m&timezone=auto&wind_speed_unit=kn"
    ],
    3 => [
        'name' => 'maui',       
        'url' => "https://surftruths.com/api/buoys/51002/readings.json",
        'meteo' => "https://api.open-meteo.com/v1/forecast?latitude=17.042&longitude=-157.746&current=temperature_2m,wind_speed_10m&timezone=auto&wind_speed_unit=kn"
    ],
    4 => [
        'name' => 'big_island', 
        'url' => "https://surftruths.com/api/buoys/51004/readings.json",
        'meteo' => "https://api.open-meteo.com/v1/forecast?latitude=17.538&longitude=-152.23&current=temperature_2m,wind_speed_10m&timezone=auto&wind_speed_unit=kn"
    ],
];
//curl
function fetchData($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['Accept: application/json']
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);
    return $data ?: null;
}
//alle daten
$allData = [];

foreach ($buoys as $id => $buoy) {
    $surfData = fetchData($buoy['url']);
    if (!$surfData || !isset($surfData[0])) continue;
    $latest = $surfData[0];

//bojendaten
    $entry = [
        'bojen_id' => $id,
        'wellenhoehe' => isset($latest['swht']) ? floatval($latest['swht']) : null,
        'wellenabstand' => isset($latest['swp']) ? floatval($latest['swp']) : null,
        'created_at' => date('Y-m-d H:i:s')
    ];
//meteodaten 
    $meteoData = fetchData($buoy['meteo']);
    if ($meteoData && isset($meteoData['current'])) {
        $entry['temperatur'] = isset($meteoData['current']['temperature_2m']) ? floatval($meteoData['current']['temperature_2m']) : null;
        $entry['wind'] = isset($meteoData['current']['wind_speed_10m']) ? floatval($meteoData['current']['wind_speed_10m']) : null;
    } else {
        $entry['temperatur'] = null;
        $entry['wind'] = null;
    }

    $allData[$buoy['name']] = $entry;
}

// **echo**

return json_encode($allData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);


