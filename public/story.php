<?php
session_start();

$sessionId = $_GET['session_id'] ?? null;
$sid = session_id();

if (!$sessionId) exit;

$sessionFile = __DIR__ . "/data/sessions/$sessionId.json";
if (!file_exists($sessionFile)) exit;

$sessionData = json_decode(file_get_contents($sessionFile), true);
$isLeader = isset($sessionData['leader']) && $sessionData['leader'] === $sid;

echo htmlspecialchars($sessionData['story'] ?? '');
