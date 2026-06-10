<?php
// Ensure this script relies on variables provided by the index.php router scope:
// $channelConfig (array) - matrix info
// $outputData (array) - response variable to populate

if (!isset($channelConfig['url'])) {
    return;
}

$url = $channelConfig['url'];

// Masked User-Agent to bypass standard bot detection
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36');
curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 second limit

$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($html === false || $httpCode >= 400) {
    // Log the error internally as required
    error_log("iran_international parser failed: HTTP Code $httpCode, cURL Error: $curlError");
    // Graceful empty state on connection error or bad HTTP code
    $outputData = [];
    return;
}

// Ensure proper internal error handling boundary
try {
    // The target website (Iran Intl) uses Next.js app router which injects the state as a stringified payload inside a <script> tag.
    // Hashed CSS selectors are too fragile. The payload contains exact timestamps and durations.
    // Example format in the JS script tag:
    // \"broadcastTime\":\"2026-06-10T00:30:00Z\",\"duration\":\"00:45:00:00\" ... \"title\":\"خبر\"

    preg_match_all('/\\\\"broadcastTime\\\\":\\\\"([^\\\\]+)\\\\".*?\\\\"duration\\\\":\\\\"([^\\\\]+)\\\\".*?\\\\"title\\\\":\\\\"([^\\\\]+)\\\\"/', $html, $items);

    if (empty($items[0])) {
        // Fallback: try parsing actual DOM if the script structure changes.
        // But since hashed CSS modules change on every build, we will log a warning and return empty.
        error_log("iran_international parser warning: Could not find JSON payload in Next.js script tags.");
        $outputData = [];
        return;
    }

    $processedIds = []; // Prevent duplicates which occur frequently in the initial Next.js state

    for ($i = 0; $i < count($items[0]); $i++) {
        $rawTime = $items[1][$i]; // e.g. 2026-06-10T00:30:00Z
        $rawDuration = $items[2][$i]; // e.g. 00:45:00:00
        $rawTitle = trim(stripslashes($items[3][$i])); // e.g. خبر

        // De-duplicate based on start time
        if (isset($processedIds[$rawTime])) {
            continue;
        }
        $processedIds[$rawTime] = true;

        // Parse start timestamp (ISO 8601 parsing handles Z timezone natively)
        $startTimestamp = strtotime($rawTime);
        if ($startTimestamp === false) {
            continue;
        }

        // Parse duration parts (HH:MM:SS:FF)
        $durParts = explode(':', $rawDuration);
        $hours = isset($durParts[0]) ? (int)$durParts[0] : 0;
        $minutes = isset($durParts[1]) ? (int)$durParts[1] : 0;
        $seconds = isset($durParts[2]) ? (int)$durParts[2] : 0;

        $totalDurSeconds = ($hours * 3600) + ($minutes * 60) + $seconds;

        $endTimestamp = $startTimestamp + $totalDurSeconds;

        $outputData[] = [
            'title' => $rawTitle,
            'start_time' => date('c', $startTimestamp),
            'end_time' => date('c', $endTimestamp)
        ];
    }
} catch (Exception $e) {
    // Log internal failure
    error_log("iran_international parser exception: " . $e->getMessage());
    // Fall back to empty array
    $outputData = [];
}
