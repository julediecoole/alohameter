<?php
/* ============================================================================
   HANDLUNGSANWEISUNG (transform.php)
   0) Schau dir die Rohdaten genau an und plane exakt, wie du die Daten umwandeln möchtest (auf Papier)
   1) Binde extract.php ein und erhalte das Rohdaten-Array.
   2) Definiere Mapping Koordinaten → Anzeigename (z. B. Bern/Chur/Zürich).
   3) Konvertiere Einheiten (°C schon vorhanden, 1 Nachkommastelle)
   4) Leite eine einfache "condition" ab (optional, hier nicht genutzt)
   5) Baue ein kompaktes, flaches Array je Standort mit den Ziel-Feldern.
   6) Optional: Sortiere die Werte (z. B. nach Zeit), entferne irrelevante Felder.
   7) Validiere Pflichtfelder (bojen_id, wellenhoehe, wellenabstand, temperatur, wind, created_at).
   8) Kodieren: json_encode(..., JSON_PRETTY_PRINT) → JSON-String.
   9) GIB den JSON-String ZURÜCK (hier: echo statt return) – für Output-Buffering.
  10) Fehlerfälle werden automatisch weitergereicht (kein HTML/echo direkt vom Skript).
============================================================================ */
/* ============================================================================
   transform.php
   - Fehlende Werte → 404
   - Output-Buffering, um extract.php mit echo abzufangen
   - Bojen-Namen als Schlüssel behalten
=========================================================================== */

// 1) Rohdaten von extract.php holen (Output-Buffering)
ob_start();
include 'extract.php';
$jsonFromExtract = ob_get_clean();

// 2) JSON dekodieren
$data = json_decode($jsonFromExtract, true);
if (!is_array($data)) {
    // immer gültiges JSON zurückgeben, wenn extract nichts liefert
    echo json_encode([]);
    return;
}

// 3) Array für transformierte Daten vorbereiten
$transformedData = [];

// 4) Transformieren
foreach ($data as $bojeName => $values) {
    $entry = [
        'bojen_id'        => isset($values['bojen_id']) ? intval($values['bojen_id']) : 404,
        'wellenhoehe'     => isset($values['wellenhoehe']) ? floatval($values['wellenhoehe']) : 404,
        'wellenabstand'   => isset($values['wellenabstand']) ? floatval($values['wellenabstand']) : 404,
        'temperatur'      => isset($values['temperatur']) ? round(floatval($values['temperatur']),1) : 404,
        'wind'            => isset($values['wind']) ? floatval($values['wind']) : 404,
        'created_at'      => isset($values['created_at']) ? $values['created_at'] : date('Y-m-d H:i:s')
    ];

    // Bojen-Name als Schlüssel behalten
    $transformedData[$bojeName] = $entry;
}

// 5) JSON ausgeben
header('Content-Type: application/json; charset=utf-8');
echo json_encode($transformedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
