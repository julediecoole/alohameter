<?php
date_default_timezone_set('Europe/Zurich');

require_once 'config.php'; // Verbindung zur DB

$buoys = [
    1 => ['name' => 'oahu',       'url' => "https://surftruths.com/api/buoys/51101/readings.json"],
    2 => ['name' => 'kauai',      'url' => "https://surftruths.com/api/buoys/51001/readings.json"],
    3 => ['name' => 'maui',       'url' => "https://surftruths.com/api/buoys/51002/readings.json"],
    4 => ['name' => 'big_island', 'url' => "https://surftruths.com/api/buoys/51004/readings.json"]
];

// Funktion: cURL holen + JSON dekodieren
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

// Alle Bojen-Daten sammeln & transformieren
$allData = [];

foreach ($buoys as $id => $buoy) {
    $data = fetchBuoyData($buoy['url']);

    echo "<pre>";
//print_r($data);
echo "</pre>";
    if (!$data || !isset($data[0])) continue; // keine Daten

    $derErsteWert = $data[0]; // aktuellster Eintrag
    $entry = [
        'bojen_id'       => $id,
        'wellenhoehe'         => isset($derErsteWert['swht']) ? floatval($derErsteWert['swht']) : null,
        'wellenabstand' => isset($derErsteWert['swp']) ? floatval($derErsteWert['swp']) : null,
        'created_at'     => date('Y-m-d H:i:s')
        // 'wind'           => isset($derErsteWert['??']) ? intval($derErsteWert['??']) : null,
        // 'temperatur'           => isset($derErsteWert['??']) ? intval($derErsteWert['??']) : null,
    ];

    $allData[$buoy['name']] = $entry;
}

// JSON-Ausgabe für Test / Frontend
header('Content-Type: application/json; charset=utf-8');
echo json_encode($allData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
