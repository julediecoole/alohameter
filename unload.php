<?php
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

    // --- 1️⃣ Parameter: optional von Datepicker ---
    $bojen_id = isset($_GET['bojen_id']) ? intval($_GET['bojen_id']) : null;
    $from     = $_GET['from'] ?? null;
    $to       = $_GET['to'] ?? null;

    // --- 2️⃣ Standard: letzte 5 Tage inkl. heute ---
    $today = new DateTime();
    $default_from = (clone $today)->modify('-4 days')->format('Y-m-d 00:00:00'); // 4 Tage zurück
    $default_to   = $today->format('Y-m-d 23:59:59');

    $from = $from ?? $default_from;
    $to   = $to   ?? $default_to;

    // --- 3️⃣ SQL-Abfrage ---
    $sql = "SELECT m.id, m.bojen_id, b.namen, b.code,
                   m.wellenhoehe, m.wellenabstand, m.temperatur, m.wind, m.created_at
            FROM alohameter_messungen m
            JOIN alohameter_boje b ON m.bojen_id = b.id
            WHERE m.created_at BETWEEN :from AND :to";

    $params = [':from' => $from, ':to' => $to];

    if ($bojen_id !== null) {
        $sql .= " AND m.bojen_id = :bojen_id";
        $params[':bojen_id'] = $bojen_id;
    }

    $sql .= " ORDER BY m.created_at ASC"; // aufsteigend für Chart

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->execute();
    $results = $stmt->fetchAll();

    // --- 4️⃣ JSON-Ausgabe ---
    // Immer ein Array, auch wenn leer
    echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Interner Serverfehler.']);
    error_log($e->getMessage());
    exit;
}
