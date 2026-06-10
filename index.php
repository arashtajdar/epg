<?php

// Force script execution safety layer
set_time_limit(120);

// Check requested format and channel from URI or GET
$format = $_GET['format'] ?? 'json';
$channelKey = $_GET['channel'] ?? '';

$uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($uriPath, '/'));
if (count($pathParts) >= 2 && end($pathParts) === 'html') {
    $format = 'html';
    if (empty($channelKey)) {
        $channelKey = $pathParts[0];
    }
}

// Input Sniffing
if (empty($channelKey)) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(400);
    echo json_encode(['error' => 'Missing channel parameter']);
    exit;
}

// Load the matrix registry
$registryPath = __DIR__ . '/config/channels.php';
if (!file_exists($registryPath)) {
    header('Content-Type: application/json; charset=utf-8');
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
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(404);
    echo json_encode(['error' => 'Channel not found in registry']);
    exit;
}

$channelConfig = $channelsConfig[$channelKey];
$parserFileName = basename($channelConfig['parser']); // sanitize
$parserPath = __DIR__ . '/parsers/' . $parserFileName;

// Parser Verification
if (!file_exists($parserPath)) {
    header('Content-Type: application/json; charset=utf-8');
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

if ($format === 'html') {
    header('Content-Type: text/html; charset=utf-8');
    
    // Group by day
    $byDay = [];
    foreach ($outputData as $prog) {
        $data = $prog->jsonSerialize();
        $time = strtotime($data['start_time']);
        $day = date('Y-m-d', $time);
        if (!isset($byDay[$day])) {
            $byDay[$day] = [];
        }
        $byDay[$day][] = $data;
    }
    ksort($byDay);
    
    echo "<!DOCTYPE html>\n<html>\n<head>\n<title>EPG - {$channelKey}</title>\n";
    echo "<style>
        body { font-family: sans-serif; margin: 20px; }
        .tabs { display: flex; border-bottom: 1px solid #ccc; margin-bottom: 20px; flex-wrap: wrap; }
        .tab { padding: 10px 20px; cursor: pointer; border: 1px solid transparent; border-bottom: none; }
        .tab.active { background-color: #f0f0f0; border-color: #ccc; border-radius: 5px 5px 0 0; font-weight: bold; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>\n";
    echo "</head>\n<body>\n";
    echo "<h1>EPG for {$channelKey}</h1>\n";
    
    if (empty($byDay)) {
        echo "<p>No programs found.</p>\n";
    } else {
        echo "<div class='tabs'>\n";
        $first = true;
        foreach ($byDay as $day => $programs) {
            $activeClass = $first ? ' active' : '';
            echo "<div class='tab{$activeClass}' onclick='showTab(\"{$day}\")'>{$day}</div>\n";
            $first = false;
        }
        echo "</div>\n";
        
        $first = true;
        foreach ($byDay as $day => $programs) {
            $activeClass = $first ? ' active' : '';
            echo "<div id='tab-{$day}' class='tab-content{$activeClass}'>\n";
            echo "<table>\n";
            echo "<tr><th>Start Time</th><th>End Time</th><th>Title</th></tr>\n";
            foreach ($programs as $prog) {
                $start = date('H:i', strtotime($prog['start_time']));
                $end = date('H:i', strtotime($prog['end_time']));
                $title = htmlspecialchars($prog['title']);
                echo "<tr><td>{$start}</td><td>{$end}</td><td>{$title}</td></tr>\n";
            }
            echo "</table>\n";
            echo "</div>\n";
            $first = false;
        }
    }
    
    echo "<script>
        function showTab(day) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab').forEach(el => el.classList.remove('active'));
            document.getElementById('tab-' + day).classList.add('active');
            event.target.classList.add('active');
        }
    </script>\n";
    echo "</body>\n</html>";
} else {
    // Standardized Output Serialization
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($outputData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
