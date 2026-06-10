<?php
// Ensure this script relies on variables provided by the index.php router scope:
// $channelConfig (array) - matrix info
// $outputData (array) - response variable to populate

if (!isset($channelConfig['url'])) {
    return;
}

$url = $channelConfig['url'];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Strict 30 second limit

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false || $httpCode >= 400) {
    error_log("dummy_api parser failed: HTTP Code $httpCode, cURL Error: $curlError");
    // Graceful empty state
    $outputData = [];
    return;
}

try {
    $data = json_decode($response, true);

    if (!is_array($data)) {
        $outputData = [];
        return;
    }

    // Since this is a dummy API (jsonplaceholder), we'll mock the start/end time
    // logic based on the IDs returned just to demonstrate the array population logic

    $currentTime = time();

    foreach ($data as $index => $item) {
        // limit to first 10 for dummy example
        if ($index >= 10) break;

        // Mocking a start time and an end time (start time + 30 mins)
        $startTimestamp = $currentTime + ($index * 1800); // offset by 30 mins per item
        $endTimestamp = $startTimestamp + 1800; // 30 minutes duration

        $outputData[] = [
            'title' => trim((string)$item['title']),
            'start_time' => date('c', $startTimestamp),
            'end_time' => date('c', $endTimestamp)
        ];
    }
} catch (Exception $e) {
    // Log internal failure
    error_log("dummy_api parser exception: " . $e->getMessage());
    // If parsing fails, fall back to empty array
    $outputData = [];
}
