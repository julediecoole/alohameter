<?php

// Definition der Verbindungsparameter für die Datenbank
$host     = 'sd23ml.myd.infomaniak.com
';     // Hostserver, auf dem die DB läuft.
// «localhost» bedeutet: die selbe Serveradresse, auf dem auch die Seiten gespeichert sind

$dbname   = 'sd23ml_im3chur';   // Name der Datenbank
$username = 'sd23ml_Jule';   // Benutzername für die DB
$password = 'zifcIt-fijpu9-wurjoc';   // Passwort für die DB


// DSN (Datenquellenname) für PDO
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4"; // siehe https://en.wikipedia.org/wiki/Data_source_name

// Optionen für PDO
$options = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Aktiviert die Ausnahmebehandlung für Datenbankfehler
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Legt den Standard-Abrufmodus auf assoziatives Array fest
  PDO::ATTR_EMULATE_PREPARES   => false // Deaktiviert die Emulation vorbereiteter Anweisungen, für bessere Leistung
];
