<?php

// Force script execution safety layer
set_time_limit(120);

// Enforce standard JSON response format
header('Content-Type: application/json; charset=utf-8');

// Input Sniffing
if (!isset($_GET['channel']) || empty($_GET['channel'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing channel parameter']);
    exit;
}

$channelKey = $_GET['channel'];

// Load the matrix registry
$registryPath = __DIR__ . '/config/channels.php';
if (!file_exists($registryPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Missing channels registry']);
    exit;
}
$channelsConfig = include $registryPath;

// Include Models
$modelPath = __DIR__ . '/models/Program.php';
if (file_exists($modelPath)) {
    include_once $modelPath;
}

// Map Verification
if (!isset($channelsConfig[$channelKey])) {
    http_response_code(404);
    echo json_encode(['error' => 'Channel not found in registry']);
    exit;
}

$channelConfig = $channelsConfig[$channelKey];
$parserFileName = basename($channelConfig['parser']); // sanitize
$parserPath = __DIR__ . '/parsers/' . $parserFileName;

// Parser Verification
if (!file_exists($parserPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Parser file missing']);
    exit;
}

// Global variable definition
$outputData = [];

// Try to execute isolated parser scope
try {
    include_once $parserPath;
} catch (Throwable $e) {
    // If the parser throws something unexpected despite requirements
    http_response_code(200); // 200 OK with empty array as per empty state grace
    $outputData = []; // Fallback empty array
}

// Ensure the final structure adheres to rules
if (!is_array($outputData)) {
    $outputData = [];
}

// Standardized Output Serialization
// The outputData elements are expected to be instances of the Program model
// which implements JsonSerializable for guaranteed formatting.
echo json_encode($outputData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
