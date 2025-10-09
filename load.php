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

// (1) Konfiguration & Transformationsdaten einbinden
require_once '001_config.php';    // Stellt $pdo bereit
require_once 'transform.php';     // Liefert z. B. $transform_json

// (2) JSON → Array konvertieren
$dataArray = json_decode($transform_json, true);
if (!is_array($dataArray)) {
    die("Fehler: transform.php hat kein gültiges JSON zurückgegeben.");
}

try {
    // (3) PDO konfigurieren
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // (4) Transaktion starten
    $pdo->beginTransaction();

    // (5) SQL-Statement vorbereiten
    $sql = "
        INSERT INTO alohameter_messungen 
            (bojen_id, wind, wellen, lufttemperatur, wassertemperatur)
        VALUES 
            (:bojen_id, :wind, :wellen, :lufttemperatur, :wassertemperatur)
    ";
    $stmt = $pdo->prepare($sql);

    // (6) Datensätze einfügen
    foreach ($dataArray as $item) {
        // Nur vollständige Einträge einfügen
        if (
            !isset($item['bojen_id']) ||
            !isset($item['wind']) ||
            !isset($item['wellen']) ||
            !isset($item['lufttemperatur']) ||
            !isset($item['wassertemperatur'])
        ) {
            continue;
        }

        $stmt->execute([
            ':bojen_id'         => $item['bojen_id'],
            ':wind'             => $item['wind'],
            ':wellen'           => $item['wellen'],
            ':lufttemperatur'   => $item['lufttemperatur'],
            ':wassertemperatur' => $item['wassertemperatur']
        ]);
    }

    // (7) Commit und Erfolgsmeldung
    $pdo->commit();
    echo "Messdaten erfolgreich eingefügt.";

} catch (PDOException $e) {
    // (8) Rollback & Fehlermeldung
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("Fehler beim Laden der Daten. Bitte später erneut versuchen.");
}
