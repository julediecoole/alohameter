<?php
/* ============================================================================
   HANDLUNGSANWEISUNG (unload.php)
   1) Setze Header: Content-Type: application/json; charset=utf-8.
   2) Binde 001_config.php (PDO-Config) ein.
   3) Lies optionale Request-Parameter (z. B. location, limit, from/to) und validiere.
   4) Baue SELECT mit PREPARED STATEMENT (WHERE/ORDER BY/LIMIT je nach Parametern).
   5) Binde Parameter sicher (execute([...]) oder bindValue()).
   6) Hole Datensätze (fetchAll) – optional gruppieren/umformen fürs Frontend.
   7) Antworte IMMER als JSON (json_encode) – auch bei leeren Treffern ([]) .
   8) Setze sinnvolle HTTP-Statuscodes (400 für Bad Request, 404 bei 0 Treffern (Detail), 200 ok).
   9) Fehlerfall: 500 + { "error": "..." } (keine internen Details leaken).
  10) Keine HTML-Ausgabe; keine var_dump in Prod.
   ============================================================================ */


//require_once '../config.php'; // Stellen Sie sicher, dass dies auf Ihre tatsächliche Konfigurationsdatei verweist

//header('Content-Type: application/json');


declare(strict_types=1);

// Content-Type für JSON setzen
header('Content-Type: application/json; charset=utf-8');

// Datenbankkonfiguration einbinden
require_once 'config.php'; 

try {
    // PDO initialisieren, falls config nur Zugangsdaten liefert
    if (!isset($pdo)) {
        $pdo = new PDO($dsn, $username, $password, $options);
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // 1) Parameter lesen und validieren
    $bojen_id = isset($_GET['bojen_id']) ? intval($_GET['bojen_id']) : null;
    $limit     = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
    $from      = $_GET['from'] ?? null;
    $to        = $_GET['to'] ?? null;

    // Grenzen für Sicherheit
    if ($limit < 1 || $limit > 500) {
        http_response_code(400);
        echo json_encode(['error' => 'Ungültiger Limit-Wert (1–500 erlaubt).']);
        exit;
    }

    // 2) Basis-SQL
    $sql = "SELECT id, bojen_id, wellenhoehe, wellenabstand, temperatur, wind, created_at
            FROM alohameter_messungen WHERE 1=1";

    $params = [];

    // 3) Dynamisch WHERE-Bedingungen anhängen
    if ($bojen_id !== null) {
        $sql .= " AND bojen_id = :bojen_id";
        $params[':bojen_id'] = $bojen_id;
    }
    if ($from !== null) {
        $sql .= " AND created_at >= :from";
        $params[':from'] = $from;
    }
    if ($to !== null) {
        $sql .= " AND created_at <= :to";
        $params[':to'] = $to;
    }

    $sql .= " ORDER BY created_at DESC LIMIT :limit";

    // 4) Prepared Statement
    $stmt = $pdo->prepare($sql);

    // limit muss als INT gebunden werden
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

    // 5) Ausführen und Ergebnisse abrufen
    $stmt->execute();
    $results = $stmt->fetchAll();

    // 6) Antwort je nach Ergebnis
    if (!$results) {
        http_response_code(404);
        echo json_encode([]);
        exit;
    }

    http_response_code(200);
    echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Datenbankfehler beim Abrufen der Messdaten.']);
    error_log($e->getMessage()); // Log für Admin, nicht an den Client senden
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Interner Serverfehler.']);
    error_log($e->getMessage());
    exit;
}