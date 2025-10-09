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
<?php
/* ============================================================================
   load.php – Lädt transformierte Messdaten in die Datenbank
   ============================================================================
   Anpassung für transform.php mit echo + Output-Buffering
=========================================================================== */

require_once 'config.php'; // stellt $pdo bereit

// 1) Transformierte Daten holen
ob_start();
include 'transform.php'; // transform.php macht echo json_encode(...)
$jsonData = ob_get_clean();

// 2) JSON dekodieren
$dataArray = json_decode($jsonData, true);
if (!is_array($dataArray)) {
    die("Fehler: transform.php hat kein gültiges JSON zurückgegeben.");
}

try {
    // 3) PDO konfigurieren
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // 4) Transaktion starten
    $pdo->beginTransaction();

    // 5) SQL-Statement vorbereiten
    $sql = "
        INSERT INTO alohameter_messungen
            (bojen_id, wellenhoehe, wellenabstand, wassertemperatur, wind, created_at)
        VALUES
            (:bojen_id, :wellenhoehe, :wellenabstand, :wassertemperatur, :wind, :created_at)
    ";
    $stmt = $pdo->prepare($sql);

    // 6) Datensätze einfügen
    foreach ($dataArray as $item) {
        $stmt->execute([
            ':bojen_id'       => $item['bojen_id'] ?? 404,
            ':wellenhoehe'    => $item['wellenhoehe'] ?? 404,
            ':wellenabstand'  => $item['wellenabstand'] ?? 404,
            ':wassertemperatur'=> $item['wassertemperatur'] ?? 404,
            ':wind'           => $item['wind'] ?? 404,
            ':created_at'     => $item['created_at'] ?? date('Y-m-d H:i:s')
        ]);
    }

    // 7) Commit
    $pdo->commit();
    echo "Messdaten erfolgreich eingefügt.";

} catch (PDOException $e) {
    // Rollback bei Fehler
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("Fehler beim Laden der Daten. Bitte später erneut versuchen.");
}
