<?php
session_start();

$action = $_POST['action'] ?? null;
$sessionId = $_POST['session_id'] ?? null;
$name = trim($_POST['name'] ?? '');

if (!$name) {
    echo "Name is required.";
    exit;
}

$dataDir = __DIR__ . '/data/sessions';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

function redirectToEstimatePage($sessionId, $isLeader) {
    $_SESSION['session_id'] = $sessionId;
    $_SESSION['is_leader'] = $isLeader;
    header("Location: estimate.php?session_id=$sessionId");
    exit;
}

$sid = session_id();

if ($action === 'create') {
    $newId = "PMPF-" . strtoupper(base_convert(mt_rand(1000000000, PHP_INT_MAX), 10, 36));
    $sessionFile = "$dataDir/$newId.json";

    $sessionData = [
        'id' => $newId,
        'leader' => $sid,
        'participants' => [
            $sid => [
                'name' => $name,
                'vote' => null
            ]
        ],
        'revealed' => false,
        'story' => ''
    ];

    file_put_contents($sessionFile, json_encode($sessionData, JSON_PRETTY_PRINT));
    redirectToEstimatePage($newId, true);

} elseif ($action === 'join' && $sessionId) {
    $sessionFile = "$dataDir/$sessionId.json";

    if (!file_exists($sessionFile)) {
        echo "Invalid session ID.";
        exit;
    }

    $sessionData = json_decode(file_get_contents($sessionFile), true);

    if (!isset($sessionData['participants'][$sid])) {
        $sessionData['participants'][$sid] = ['name' => $name, 'vote' => null];
        file_put_contents($sessionFile, json_encode($sessionData, JSON_PRETTY_PRINT));
    }

    redirectToEstimatePage($sessionId, false);

} else {
    echo "Invalid request.";
    exit;
}
