<?php
date_default_timezone_set('Europe/Zurich');

require_once 'config.php'; // Verbindung zur DB

// Bojen + Open-Meteo URLs
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

// cURL-Funktion für JSON-Abfrage
function fetchBuoyData($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['Accept: application/json']
    ]);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    if (!$response || $status !== 200 || stripos($contentType,'application/json')===false) {
        return null;
    }

    $data = json_decode($response, true);
    return $data ?: null;
}

// Alle Daten sammeln
$allData = [];

foreach ($buoys as $id => $buoy) {
    // SurfTruths-Daten abrufen
    $data = fetchBuoyData($buoy['url']);
    if (!$data || !isset($data[0])) continue;

    $derErsteWert = $data[0];
    $entry = [
        'bojen_id'      => $id,
        'wellenhoehe'   => isset($derErsteWert['swht']) ? floatval($derErsteWert['swht']) : null,
        'wellenabstand' => isset($derErsteWert['swp']) ? floatval($derErsteWert['swp']) : null,
        'created_at'    => date('Y-m-d H:i:s')
    ];

    // Open-Meteo-Daten abrufen
    $meteoData = isset($buoy['meteo']) ? fetchBuoyData($buoy['meteo']) : null;
    if ($meteoData && isset($meteoData['current'])) {
        $entry['temperatur'] = isset($meteoData['current']['temperature_2m']) 
            ? floatval($meteoData['current']['temperature_2m']) 
            : null;
        $entry['wind'] = isset($meteoData['current']['wind_speed_10m']) 
            ? floatval($meteoData['current']['wind_speed_10m']) 
            : null;
    }

    $allData[$buoy['name']] = $entry;
}

// JSON-Ausgabe für Test / Frontend
header('Content-Type: application/json; charset=utf-8');
echo json_encode($allData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
