<?php
session_start();

$sessionId = $_GET['session_id'] ?? $_SESSION['session_id'] ?? null;
$sid = session_id();
$isLeader = false;


if (!$sessionId) {
    header("Location: index.php");
    exit;
}

$sessionFile = __DIR__ . "/data/sessions/$sessionId.json";
if (!file_exists($sessionFile)) {
    echo "Session not found.";
    exit;
}

$sessionData = json_decode(file_get_contents($sessionFile), true);
if (isset($sessionData['leader']) && $sessionData['leader'] === $sid) {
    $isLeader = true;
}
// Check if this user is registered in this session
elseif (!isset($sessionData['participants'][$sid])) {
    // Not a known participant — redirect to join
    header("Location: index.php?session_id=" . urlencode($sessionId));
    exit;
}

// Handle estimate submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['estimate'])) {
    $value = $_POST['estimate'];
    if (isset($sessionData['participants'][$sid])) {
        $sessionData['participants'][$sid]['estimate'] = $value;
        file_put_contents($sessionFile, json_encode($sessionData, JSON_PRETTY_PRINT));
        header("Location: estimate.php?session_id=$sessionId");
        exit;
    }
}

// Handle reveal (leader only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reveal']) && $isLeader) {
    $sessionData['revealed'] = true;
    file_put_contents($sessionFile, json_encode($sessionData, JSON_PRETTY_PRINT));
    header("Location: estimate.php");
    exit;
}

// Handle vote reset (leader only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset']) && $isLeader) {
    foreach ($sessionData['participants'] as &$participant) {
        $participant['estimate'] = null;
    }
    unset($participant); // break reference
    $sessionData['revealed'] = false;
    file_put_contents($sessionFile, json_encode($sessionData, JSON_PRETTY_PRINT));
    header("Location: estimate.php?session_id=$sessionId");
    exit;
}

// Handle story update (leader only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['story']) && $isLeader) {
    $story = substr(trim($_POST['story']), 0, 250); // Enforce 250 char limit
    $sessionData['story'] = $story;
    file_put_contents($sessionFile, json_encode($sessionData, JSON_PRETTY_PRINT));
    header("Location: estimate.php?session_id=$sessionId");
    exit;
}

// Handle story clear (leader only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_story']) && $isLeader) {
    $sessionData['story'] = '';
    file_put_contents($sessionFile, json_encode($sessionData, JSON_PRETTY_PRINT));
    header("Location: estimate.php?session_id=$sessionId");
    exit;
}

// Handle next estimate (leader only) - resets estimates and clears story
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['next_estimate']) && $isLeader) {
    foreach ($sessionData['participants'] as &$participant) {
        $participant['estimate'] = null;
    }
    unset($participant); // break reference
    $sessionData['revealed'] = false;
    $sessionData['story'] = '';
    file_put_contents($sessionFile, json_encode($sessionData, JSON_PRETTY_PRINT));
    header("Location: estimate.php?session_id=$sessionId");
    exit;
}

// Calculate estimates and average
function calculateAverage($participants) {
    $estimates = array_column($participants, 'estimate');
    $numericestimates = array_filter($estimates, fn($v) => is_numeric($v));
    if (count($numericestimates) === 0) return null;
    return array_sum($numericestimates) / count($numericestimates);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PointyMcPokerFace - Estimate Session</title>
    <link rel="stylesheet" href="css/theme.css">
</head>
<body>

<h1>Planning Poker</h1>
<div class="session-id">
    Session ID: <strong><?= htmlspecialchars($sessionId) ?></strong><br>
    Share Link: <a href="index.php?session_id=<?= urlencode($sessionId) ?>">Join this session</a>
</div>

<div class="story-section">
    <h3>Story</h3>
    <?php if ($isLeader): ?>
        <form method="POST">
            <textarea 
                name="story" 
                maxlength="250" 
                placeholder="Enter story description (max 250 characters)"
            ><?= htmlspecialchars($sessionData['story'] ?? '') ?></textarea>
            <div class="story-actions">
                <button type="submit">Update Story</button>
                <button type="submit" name="clear_story" value="1">Clear</button>
            </div>
        </form>
    <?php else: ?>
        <div id="story-display">
            <?= htmlspecialchars($sessionData['story'] ?? '') ?>
        </div>
    <?php endif; ?>
</div>

<form method="POST">
    <div class="estimate-buttons">
        <?php foreach (['1', '2', '3', '5', '8', '13', '?'] as $val): ?>
            <button type="submit" name="estimate" value="<?= $val ?>"><?= $val ?></button>
        <?php endforeach; ?>
    </div>
</form>

<?php
$currentUserId = session_id();
?>

<div class="participants">
    <h2>Participants</h2>
    <table>
        <thead>
        <tr>
            <th>Name</th>
            <th>Role</th>
            <th>Estimate</th>
        </tr>
        </thead>
        <tbody id="participants-body">
    </table>
</div>


<?php if ($isLeader): ?>
    <?php
    // Compute average excluding "?" or non-numeric votes
    $estimate = array_column($sessionData['participants'], 'estimate');
    $numericVotes = array_filter($estimate, fn($v) => is_numeric($v));
    $avg = count($numericVotes) ? round(array_sum($numericVotes) / count($numericVotes), 2) : null;
    ?>
    <?php if ($sessionData['revealed']): ?>
        <?php if ($avg !== null): ?>
            <p class="average-estimate"><strong>Average Estimate:</strong> <?= $avg ?></p>
        <?php else: ?>
            <p class="average-estimate"><strong>Average Estimate:</strong> N/A</p>
        <?php endif; ?>
    <?php endif; ?>

    <div class="reveal">
        <form method="POST">
            <button type="submit" name="reveal" value="1">Reveal Estimates</button>
            <button type="submit" name="reset" value="1">Reset Estimations</button>
            <button type="submit" name="next_estimate" value="1">Next Estimate</button>
        </form>
    </div>
<?php endif; ?>


<form action="index.php" method="get" class="leave-session">
    <button type="submit" onclick="localStorage.clear()">Leave Session</button>
</form>

</body>

<script>
    refreshParticipants();
    <?php if (!$isLeader): ?>
    refreshStory();
    <?php endif; ?>

    function refreshParticipants() {
        fetch("participants.php?session_id=<?= urlencode($sessionId) ?>")
            .then(response => response.text())
            .then(html => {
                document.getElementById("participants-body").innerHTML = html;
            });
    }

    <?php if (!$isLeader): ?>
    function refreshStory() {
        fetch("story.php?session_id=<?= urlencode($sessionId) ?>")
            .then(response => response.text())
            .then(text => {
                document.getElementById("story-display").textContent = text;
            });
    }
    <?php endif; ?>

    // Refresh every 5 seconds
    setInterval(function() {
        refreshParticipants();
        <?php if (!$isLeader): ?>
        refreshStory();
        <?php endif; ?>
    }, 5000);
</script>
</html>
