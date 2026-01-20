<?php
session_start();

$sessionId = $_GET['session_id'] ?? null;
$sid = session_id();

if (!$sessionId) exit;

$sessionFile = __DIR__ . "/data/sessions/$sessionId.json";
if (!file_exists($sessionFile)) exit;

$sessionData = json_decode(file_get_contents($sessionFile), true);
$currentUserId = $sid;

foreach ($sessionData['participants'] as $id => $data):
    $isCurrent = $id === $currentUserId;
    $isSessionLeader = $id === $sessionData['leader'];
    $name = htmlspecialchars($data['name'] ?? 'Unknown');
    $estimate = $data['estimate'] ?? null;
?>
    <tr class="<?= $isCurrent ? 'current-user' : '' ?>">
        <td>
            <?= $name ?> <?= $isCurrent ? "(You)" : "" ?>
        </td>
        <td style="text-align: center;">
            <?= $isSessionLeader ? 'Leader' : 'Participant' ?>
        </td>
        <td style="text-align: center;">
            <?php if ($sessionData['revealed']): ?>
                <?= $estimate ?? 'No estimate' ?>
            <?php else: ?>
                <?= $estimate !== null ? '✅ estimated' : '❌ Not estimated' ?>
            <?php endif; ?>
        </td>
    </tr>
<?php endforeach; ?>
