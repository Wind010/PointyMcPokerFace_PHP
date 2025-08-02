<?php
session_start();
// session_unset();
// session_destroy();

$prefillSessionId = $_GET['session_id'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">

<script>
    // Auto-redirect to estimate.php if session ID is stored
    const storedSession = localStorage.getItem("pmpf_session_id");
    if (storedSession && window.location.pathname.endsWith("index.php")) {
        window.location.href = "estimate.php";
    }
</script>

<head>
    <meta charset="UTF-8">
    <title>PointyMcPokerFace</title>
    <style>
        body {
            font-family: sans-serif;
            text-align: center;
            margin-top: 50px;
        }

        input, button {
            padding: 10px;
            font-size: 16px;
            margin: 5px;
        }

        .container {
            max-width: 400px;
            margin: auto;
        }

        h1 {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>PointyMcPokerFace</h1>

    <!-- Start New Session -->
    <form action="session.php" method="post">
        <input type="hidden" name="action" value="create">
        <input type="text" name="name" placeholder="Your Name (Leader)" required>
        <button type="submit">Start New Session</button>
    </form>

    <hr>

    <!-- Join Session -->
    <form action="session.php" method="post">
        <input type="hidden" name="action" value="join">
        <input type="text" name="session_id" placeholder="Enter Session ID" value="<?= htmlspecialchars($prefillSessionId) ?>" required>
        <input type="text" name="name" placeholder="Your Name" required>
        <button type="submit">Join Session</button>
    </form>
</div>
</body>

<script>
    // Save session ID and leader flag after redirect
    const params = new URLSearchParams(window.location.search);
    const sessionId = params.get("session_id");
    const asLeader = params.get("leader");

    if (sessionId) {
        localStorage.setItem("pmpf_session_id", sessionId);
        if (asLeader) {
            localStorage.setItem("pmpf_is_leader", "1");
        }
    }
</script>

</html>
