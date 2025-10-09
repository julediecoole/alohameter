<?php
// =============================
// extract.php
// =============================

// Schritt 1: Konfiguration
date_default_timezone_set('Europe/Zurich');

$buoys = [
    'oahu'       => "https://surftruths.com/api/buoys/51101/readings.json",
    'kauai'      => "https://surftruths.com/api/buoys/51001/readings.json",
    'maui'       => "https://surftruths.com/api/buoys/51002/readings.json",
    'big_island' => "https://surftruths.com/api/buoys/51004/readings.json"
];

// =============================
// Funktion zum Datenholen
// =============================
function fetchBuoyData($url) {
    // Schritt 3: cURL initialisieren
    $ch = curl_init($url);

    // Schritt 4: Optionen setzen
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['Accept: application/json']
    ]);

    // Schritt 5: Request ausführen
    $response = curl_exec($ch);

    // Transportfehler prüfen
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['error' => "cURL-Fehler bei $url: $error"];
    }

    // Schritt 6: HTTP-Status & Content-Type prüfen
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    if ($status !== 200 || stripos($contentType, 'application/json') === false) {
        return ['error' => "Unerwartete Antwort von $url (Status $status, Content-Type $contentType)"];
    }

    // Schritt 7: JSON dekodieren
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => "JSON-Dekodierung fehlgeschlagen bei $url: " . json_last_error_msg()];
    }

    // Schritt 8: Minimalprüfung (z. B. leere Antwort)
    if (!is_array($data) || count($data) === 0) {
        return ['error' => "Keine gültigen Daten erhalten bei $url"];
    }

    // Schritt 9: Array zurückgeben
    return $data;
}

// =============================
// Schritt 10: Alle Bojen abrufen
// =============================
$allData = [];
foreach ($buoys as $island => $url) {
    $allData[$island] = fetchBuoyData($url);
}

// Header für JSON-Ausgabe
header('Content-Type: application/json; charset=utf-8');

// Ausgabe mit print (schön formatiert)
print json_encode($allData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
