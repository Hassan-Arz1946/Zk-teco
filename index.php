<?php
date_default_timezone_set('Asia/Karachi');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$logsFile = 'logs.json';

// Load existing logs from file
$logs = file_exists($logsFile) ? json_decode(file_get_contents($logsFile), true) : [];

// ✅ ZKTeco GET handshake
if (strpos($uri, '/iclock/cdata') === 0 && $method === 'GET') {
    echo "OK\n";
    exit;
}

// ✅ ZKTeco POST data push
if (strpos($uri, '/iclock/cdata') === 0 && $method === 'POST') {
    $rawData = file_get_contents("php://input");
    file_put_contents('php://stderr', "📥 RAW PUSH: $rawData\n", FILE_APPEND);

    $lines = explode("\n", trim($rawData));
    foreach ($lines as $line) {
        $parts = explode("\t", trim($line));
        if (count($parts) < 3 || stripos($parts[0], 'OPLOG') === 0) continue;

        $userId = $parts[0];
        $statusCode = $parts[2];

        $now = new DateTime('now', new DateTimeZone('Asia/Karachi'));
        $minutes = intval($now->format('H')) * 60 + intval($now->format('i'));

        $status = 'Unknown';
        if ($statusCode === '0') $status = $minutes > 540 ? 'Check-In (Short)' : 'Check-In';
        if ($statusCode === '1') $status = $minutes < 1020 ? 'Check-Out (Short)' : 'Check-Out';

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

// ✅ API: return logs in JSON
if ($uri === '/api/logs') {
    header('Content-Type: application/json');
    echo json_encode(array_reverse($logs));
    exit;
}

// ✅ Default route: render dashboard
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>ZKTeco Attendance Dashboard</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
    table { width: 100%; border-collapse: collapse; background: #fff; box-shadow: 0 0 10px #ccc; }
    th, td { padding: 10px; border: 1px solid #ddd; text-align: center; }
    th { background: red; color: white; }
  </style>
</head>
<body>
  <h2>ZKTeco Attendance Logs</h2>
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
        table.innerHTML += `<tr>
          <td>${log.userId}</td>
          <td>${log.status}</td>
          <td>${log.time}</td>
          <td>${log.date}</td>
        </tr>`;
      });
    }
    fetchLogs();
    setInterval(fetchLogs, 10000);
  </script>
</body>
</html>
