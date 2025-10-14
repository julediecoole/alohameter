<?php
declare(strict_types=1);

// Content-Type für JSON setzen
header('Content-Type: application/json; charset=utf-8');

// Datenbankkonfiguration laden
require_once 'config.php'; 

try {
    // 🔹 Verbindung initialisieren (falls $pdo in config.php nicht direkt erstellt wurde)
    if (!isset($pdo)) {
        $pdo = new PDO($dsn, $username, $password, $options);
    }

    // 🔹 PDO-Attribute für saubere Fehlerbehandlung
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // ================================
    // 1️⃣ Parameter aus URL übernehmen
    // ================================
    $bojen_id = isset($_GET['bojen_id']) ? intval($_GET['bojen_id']) : null;
    $from     = $_GET['from'] ?? null;
    $to       = $_GET['to'] ?? null;

    // =====================================
    // 2️⃣ Fallback: wenn kein Datum gesetzt
    //    → Standard = letzte 5 Tage
    // =====================================
    $today = new DateTime();
    $default_from = (clone $today)->modify('-4 days')->format('Y-m-d 00:00:00'); 
    $default_to   = $today->format('Y-m-d 23:59:59');

    // Falls kein Datum übergeben wurde → Standardzeitraum
    $from = $from ? urldecode($from) : $default_from;
    $to   = $to   ? urldecode($to)   : $default_to;

    // =====================================
    // 3️⃣ Format prüfen (z. B. „2025-10-10 00:00:00“)
    //    Damit keine falschen Eingaben SQL brechen
    // =====================================
    $dateRegex = '/^\d{4}-\d{2}-\d{2}(?: \d{2}:\d{2}:\d{2})?$/';
    if (!preg_match($dateRegex, $from) || !preg_match($dateRegex, $to)) {
        throw new Exception("Ungültiges Datumsformat übergeben.");
    }

    // =====================================
    // 4️⃣ SQL-Abfrage vorbereiten
    // =====================================
    $sql = "SELECT 
                m.id, 
                m.bojen_id, 
                b.namen, 
                b.code,
                m.wellenhoehe, 
                m.wellenabstand, 
                m.temperatur, 
                m.wind, 
                m.created_at
            FROM alohameter_messungen m
            JOIN alohameter_boje b ON m.bojen_id = b.id
            WHERE m.created_at BETWEEN :from AND :to";

    $params = [
        ':from' => $from,
        ':to'   => $to
    ];

    if ($bojen_id !== null) {
        $sql .= " AND m.bojen_id = :bojen_id";
        $params[':bojen_id'] = $bojen_id;
    }

    $sql .= " ORDER BY m.created_at ASC";

    // =====================================
    // 5️⃣ Abfrage ausführen
    // =====================================
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->execute();
    $results = $stmt->fetchAll();

    // =====================================
    // 6️⃣ JSON-Ausgabe
    // =====================================
    echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    // 🔴 Datenbankfehler
    http_response_code(500);
    echo json_encode(['error' => 'Datenbankfehler: ' . $e->getMessage()]);
    exit;

} catch (Exception $e) {
    // 🔴 Allgemeiner Fehler
    http_response_code(400);
    echo json_encode(['error' => 'Fehler: ' . $e->getMessage()]);
    exit;
}
