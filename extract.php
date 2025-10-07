<?php
// extract.php

/* ============================================================================
   HANDLUNGSANWEISUNG (extract.php)
   1) Lade Konfiguration/Constants (API-URL, Parameter, ggf. Zeitzone).
   2) Baue die Request-URL (Query-Params sauber via http_build_query).
   3) Initialisiere cURL (curl_init) mit der Ziel-URL.
   4) Setze cURL-Optionen (RETURNTRANSFER, TIMEOUT, HTTP-Header, FOLLOWLOCATION).
   5) Führe Request aus (curl_exec) und prüfe Transportfehler (curl_error).
   6) Prüfe HTTP-Status & Content-Type (JSON erwartet), sonst früh abbrechen.
   7) Dekodiere JSON robust (json_decode(..., true)).
   8) Normalisiere/prüfe Felder (defensive Defaults, Typen casten).
   9) Gib die Rohdaten als PHP-Array ZURÜCK (kein echo) für den Transform-Schritt.
  10) Fehlerfälle: Exception/Fehlerobjekt nach oben reichen (kein HTML ausgeben).
   ============================================================================ */

function fetchBuoyData($url) {
    $ch = curl_init ($url);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec ($ch);
    curl_close ($ch);
    print_r($response);
}
    return fetchBuoyData("https://surftruths.com/api/buoys/51000/readings.json"); //oahu
    return fetchBuoyData("https://surftruths.com/api/buoys/51001/readings.json"); //kauai
    return fetchBuoyData("https://surftruths.com/api/buoys/51002/readings.json"); //maui
    return fetchBuoyData("https://surftruths.com/api/buoys/51004/readings.json"); //big island
   