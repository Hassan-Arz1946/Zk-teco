<?php
date_default_timezone_set('Asia/Karachi');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$logsFile = 'logs.json';

// Load existing logs or start empty
$logs = file_exists($logsFile) ? json_decode(file_get_contents($logsFile), true) : [];

// ✅ Device handshake
if (strpos($uri, '/iclock/cdata') === 0 && $method === 'GET') {
    echo "OK\n";
    exit;
}

// ✅ Handle device push data
if (strpos($uri, '/iclock/cdata') === 0 && $method === 'POST') {
    $rawData = file_get_contents("php://input");
    error_log("📥 RAW PUSH: $rawData");

    $lines = explode("\n", trim($rawData));
    foreach ($lines as $line) {
        $parts = explode("\t", trim($line));
        if (count($parts) < 3 || stripos($parts[0], 'OPLOG') === 0) continue;

        $userId = $parts[0];
        $statusCode = $parts[2];

        $now = new DateTime('now', new DateTimeZone('Asia/Karachi'));
        $minutes = intval($now->format('H')) * 60 + intval($now->format('i'));

        // Define attendance status
        $status = 'Unknown';
        if ($statusCode === '0') $status = $minutes > 540 ? 'Check-In (Short)' : 'Check-In';       // after 9:00 AM
        if ($statusCode === '1') $status = $minutes < 1020 ? 'Check-Out (Short)' : 'Check-Out';   // before 5:00 PM

        $logs[] = [
            'userId' => $userId,
            'status' => $status,
            'time' => $now->format('H:i:s'),
            'date' => $now->format('Y-m-d')
        ];
        if (count($logs) > 50) array_shift($logs);
    }

    file_put_contents($logsFile, json_encode($logs));
    echo "OK\n";
    exit;
}

// ✅ API endpoint to get logs
if ($uri === '/api/logs') {
    header('Content-Type: application/json');
    echo json_encode(array_reverse($logs));
    exit;
}

// ✅ Attendance Dashboard page
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>ZKTeco Attendance Dashboard</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; margin: 0; }
    h2 { text-align: center; }
    table { width: 100%; border-collapse: collapse; background: #fff; box-shadow: 0 0 10px #ccc; }
    th, td { padding: 10px; border: 1px solid #ddd; text-align: center; }
    th { background: red; color: white; }
  </style>
</head>
<body>
  <h2>ZKTeco Live Attendance Logs</h2>
  <table>
    <thead><tr><th>User ID</th><th>Status</th><th>Time</th><th>Date</th></tr></thead>
    <tbody id="logTable"></tbody>
  </table>

  <script>
    async function fetchLogs() {
      const res = await fetch('/api/logs');
      const data = await res.json();
      const table = document.getElementById('logTable');
      table.innerHTML = '';
      data.forEach(log => {
        table.innerHTML += `
          <tr>
            <td>${log.userId}</td>
            <td>${log.status}</td>
            <td>${log.time}</td>
            <td>${log.date}</td>
          </tr>
        `;
      });
    }
    fetchLogs();
    setInterval(fetchLogs, 10000);
  </script>
</body>
</html>
