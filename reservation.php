<?php
session_start();

// FIX: Check session at page load — not buried inside POST block
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

// DB connection (W3Schools style)
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "restaurant_db";

$success_msg = "";
$error_msg   = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        $error_msg = "Connection failed: " . $conn->connect_error;
    } else {

        $uid   = $_SESSION['user_id'];
        $bid   = (int)$_POST["location"]; 
        $rdate = $_POST["rdate"];
        $rtime = $_POST["rtime"];
        $count = (int)$_POST["guests"];    

        $stmt = $conn->prepare(
            "INSERT INTO reservation (uid, bid, rdate, rtime, count) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("iissi", $uid, $bid, $rdate, $rtime, $count);

        if ($stmt->execute()) {
            $success_msg = "Your reservation has been confirmed! We look forward to welcoming you.";
        } else {
            $error_msg = "Something went wrong: " . $stmt->error;
        }

        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Rasoi — Reserve a Table</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        /* SAME CSS — unchanged */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --cream:#EDE9E1; --warm:#E5DFD5; --border:#D4CEBE;
            --dark:#1E1A16; --muted:#7A7268; --rust:#B5451B;
            --rust-dk:#923916; --input-bg:#E8E3D9;
        }
        body {
            background: var(--cream);
            color: var(--dark);
            font-family: 'Jost', sans-serif;
            font-weight: 300;
            min-height: 100vh;
        }
        nav {
            display:flex; align-items:center; justify-content:space-between;
            padding:0 48px; height:56px; border-bottom:1px solid var(--border);
            background:var(--cream);
        }
        .nav-logo {
            font-family:'Cormorant Garamond', serif;
            font-size:1.35rem; color:var(--rust); font-style:italic;
            text-decoration:none;
        }
        .nav-links { display:flex; gap:36px; list-style:none; }
        .nav-links a {
            font-size:.78rem; letter-spacing:.12em; text-transform:uppercase;
            color:var(--dark); text-decoration:none; opacity:.75;
        }
        .nav-links a:hover { opacity:1; }
        .nav-right { display:flex; align-items:center; gap:20px; }
        .nav-cart {
            font-size:.7rem; letter-spacing:.15em; border:1px solid var(--dark);
            padding:5px 14px; text-transform:uppercase; text-decoration:none;
            color:var(--dark); opacity:.7;
        }
        .nav-auth { font-size:.78rem; color:var(--dark); text-decoration:none; opacity:.7; }

        main { max-width:780px; margin:64px auto 96px; padding:0 24px; }

        .eyebrow {
            font-size:.7rem; letter-spacing:.25em; text-transform:uppercase;
            color:var(--muted); margin-bottom:14px;
        }
        h1 {
            font-family:'Cormorant Garamond', serif;
            font-size:3.2rem; font-weight:300; line-height:1.1;
        }
        h1 em { font-style:italic; color:var(--rust); display:block; }
        .subtitle { margin-top:18px; font-size:.9rem; color:var(--muted); margin-bottom:42px; }

        .alert { padding:14px 20px; font-size:.85rem; margin-bottom:28px; }
        .alert-success { background:#d4edda; color:#155724; border-left:3px solid #28a745; }
        .alert-error { background:#f8d7da; color:#721c24; border-left:3px solid #dc3545; }

        .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px 28px; }
        .col-span-2 { grid-column: span 2; }
        .field { display:flex; flex-direction:column; gap:7px; }
        label {
            font-size:.67rem; letter-spacing:.2em; text-transform:uppercase;
            color:var(--muted);
        }
        input, select {
            background:var(--input-bg); border:1px solid transparent;
            border-bottom-color:var(--border); padding:12px 14px;
            font-family:'Jost', sans-serif; font-size:.9rem;
            font-weight:300; color:var(--dark); outline:none;
        }
        input:focus, select:focus { border-color:var(--rust); background:#E2DDD2; }

        .btn-submit {
            width:100%; background:var(--rust); color:#fff; border:none;
            padding:17px 32px; font-family:'Jost', sans-serif;
            font-size:.75rem; font-weight:500; letter-spacing:.22em;
            text-transform:uppercase; cursor:pointer;
        }
        .btn-submit:hover { background:var(--rust-dk); }
        .nav-auth-user { display:flex; align-items:center; gap:14px; }
        .nav-welcome { font-size:.85rem; color:var(--rust); letter-spacing:.03em; white-space:nowrap; }
        .nav-signout {
            background:none; border:1px solid var(--rust);
            padding:5px 14px; font-size:.7rem; letter-spacing:.15em;
            text-transform:uppercase; color:var(--rust);
            cursor:pointer; transition:all 0.2s; text-decoration:none; white-space:nowrap;
        }
        .nav-signout:hover { background:var(--rust); color:#fff; }
    </style>
</head>
<body>

<nav>
    <a class="nav-logo" href="index.php">Rasoi</a>
    <ul class="nav-links">
        <li><a href="menu.php">Menu</a></li>
        <li><a href="order.php">Order Online</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="locations.php">Locations</a></li>
    </ul>
    <div class="nav-right">
        <a class="nav-cart" href="order.php">Cart</a>
        <?php if (isset($_SESSION['username'])): ?>
            <div class="nav-auth-user">
                <span class="nav-welcome">Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
                <a href="logout.php" class="nav-signout">Sign Out</a>
            </div>
        <?php else: ?>
            <a class="nav-auth" href="signin.php">Sign-in / Sign-up</a>
        <?php endif; ?>
    </div>
</nav>

<main>
    <p class="eyebrow">Dine With Us</p>
    <h1>A Table Awaits<em>You</em></h1>
    <p class="subtitle">Book your dining experience at Rasoi.</p>

    <?php if ($success_msg): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error_msg) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>">

        <!-- ❌ REMOVED hidden uid -->

        <div class="form-grid">

            <div class="field">
                <label>First Name</label>
                <input type="text" name="fname" required>
            </div>

            <div class="field">
                <label>Last Name</label>
                <input type="text" name="lname" required>
            </div>

            <div class="field">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>

            <div class="field">
                <label>Date</label>
                <input type="date" name="rdate" required>
            </div>

            <div class="field">
                <label>Time</label>
                <select name="rtime" required>
                    <?php
                    $start = strtotime("12:00");
                    $end   = strtotime("22:00");
                    for ($t = $start; $t <= $end; $t += 1800) {
                        $val = date("H:i:s", $t);
                        echo "<option value=\"$val\">$val</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="field">
                <label>Guests</label>
                <select name="guests">
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?= $i ?>"><?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="field">
                <label>Location</label>
                <select name="location">
                    <option value="1">Mumbai — Bandra W</option>
                    <option value="2">New Delhi - Connaught Place</option>
                    <option value="3">Bangalore - Indiranagar</option>
                    <option value="4">Pune — Koregaon Park</option>
                </select>
            </div>

            <div class="field col-span-2">
                <button type="submit" class="btn-submit">Confirm Reservation →</button>
            </div>

        </div>
    </form>
</main>

</body>
</html>