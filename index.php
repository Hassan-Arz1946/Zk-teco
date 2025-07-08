<?php
date_default_timezone_set('Asia/Karachi');

$logsFile = 'logs.json';
$logs = file_exists($logsFile) ? json_decode(file_get_contents($logsFile), true) : [];

$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Handle /iclock/cdata
if ($uri === '/iclock/cdata' && $method === 'POST') {
    $rawData = file_get_contents("php://input");
    $lines = explode("\n", trim($rawData));

    foreach ($lines as $line) {
        $parts = explode("\t", trim($line));
        if (count($parts) < 3 || stripos($parts[0], 'OPLOG') === 0) continue;

        $userId = $parts[0];
        $statusCode = $parts[2];
        $now = new DateTime('now', new DateTimeZone('Asia/Karachi'));

        $timeMin = (int)$now->format('H') * 60 + (int)$now->format('i');
        $status = 'Unknown';
        if ($statusCode === '0') $status = ($timeMin > 540) ? 'Check-In (Short)' : 'Check-In';        // after 9:00
        if ($statusCode === '1') $status = ($timeMin < 1020) ? 'Check-Out (Short)' : 'Check-Out';     // before 17:00

        $logs[] = [
            'userId' => $userId,
            'status' => $status,
            'time' => $now->format('H:i:s'),
            'date' => $now->format('Y-m-d')
        ];
        if (count($logs) > 50) array_shift($logs);
    }

    file_put_contents($logsFile, json_encode($logs));
    echo "OK";
    exit;
}

// Handle /api/logs
if ($uri === '/api/logs') {
    header('Content-Type: application/json');
    echo json_encode($logs);
    exit;
}

// Serve Dashboard
?>
<!DOCTYPE html>
<html>
<head>
  <title>ZKTeco Dashboard</title>
  <style>
    body { font-family: Arial; background: #f5f5f5; padding: 20px; }
    table { width: 100%; border-collapse: collapse; background: #fff; box-shadow: 0 0 10px #ccc; }
    th, td { padding: 10px; border: 1px solid #ddd; text-align: center; }
    th { background: red; color: white; }
  </style>
</head>
<body>
  <h2>ZKTeco Attendance Logs</h2>
  <table>
    <thead>
      <tr><th>User ID</th><th>Status</th><th>Time</th><th>Date</th></tr>
    </thead>
    <tbody id="logTable"></tbody>
  </table>

  <script>
    async function fetchLogs() {
      const res = await fetch('/api/logs');
      const data = await res.json();
      const table = document.getElementById('logTable');
      table.innerHTML = '';
      data.reverse().forEach(log => {
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
