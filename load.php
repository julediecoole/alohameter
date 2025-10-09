<?php
/* ============================================================================
   load.php – Lädt transformierte Messdaten in die Datenbank
   ============================================================================
   Ablauf:
   1) Binde 001_config.php (PDO-Konfiguration) ein.
   2) Binde transform.php ein → erhalte transformierte JSON-Daten.
   3) Dekodiere das JSON in ein Array.
   4) Stelle PDO-Attribute ein (ERRMODE_EXCEPTION, FETCH_ASSOC).
   5) Starte Transaktion (Performance & Sicherheit).
   6) Führe INSERT-Statement für jede Zeile aus.
   7) Commit & Erfolgsmeldung.
   8) Fehlerbehandlung mit Rollback (ohne Stacktrace).
   ============================================================================ */
/* ============================================================================
   load.php – Lädt transformierte Messdaten in die Datenbank
   ============================================================================
   Anpassung für transform.php mit echo + Output-Buffering
=========================================================================== */

require_once 'config.php'; // stellt $pdo bereit

// 1) Transformierte Daten holen
 // transform.php macht echo json_encode(...)
$jsonData = include('transform.php');

// 2) JSON dekodieren
$dataArray = json_decode($jsonData, true);
if (!is_array($dataArray)) {
    die("Fehler: transform.php hat kein gültiges JSON zurückgegeben.");
}

try {
    // 3) PDO konfigurieren
    $pdo = new PDO($dsn, $username, $password, $options);

    
    // 5) SQL-Statement vorbereiten
    $sql = "INSERT INTO alohameter_messungen (bojen_id, wellenhoehe, wellenabstand, temperatur, wind) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);

    // 6) Datensätze einfügen
   foreach ($dataArray as $item) {
    $stmt->execute([
        $item['bojen_id'] ?? 404,
        $item['wellenhoehe'] ?? 404,
        $item['wellenabstand'] ?? 404,
        $item['temperatur'] ?? 404,
        $item['wind'] ?? 404
    ]);
}

// CREATE TABLE ⁠ alohameter_messungen ⁠ (
//   ⁠ id ⁠ int(11) UNSIGNED NOT NULL,
//   ⁠ bojen_id ⁠ int(11) NOT NULL,
//   ⁠ wellenhoehe ⁠ float NOT NULL,
//   ⁠ wellenabstand ⁠ float NOT NULL,
//   ⁠ temperatur ⁠ decimal(10,7) NOT NULL,
//   ⁠ wind ⁠ int(11) NOT NULL,
//   ⁠ created_at ⁠ timestamp NOT NULL DEFAULT current_timestamp()
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

    // 7) Commit
    echo "Messdaten erfolgreich eingefügt.";

} catch (PDOException $e) {
    die("Verbindung zur Datenbank konnte nicht hergestellt werden: " . $e->getMessage());
}

