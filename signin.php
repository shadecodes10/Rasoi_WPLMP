<?php
session_start();

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "restaurant_db";
$signin_error = ""; 

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['signup_btn'])) {

  $firstname = $_POST['firstname'];
  $lastname  = $_POST['lastname'];
  $email     = $_POST['email'];
  $password  = $_POST['password'];
  $phone_no  = $_POST['phone_no']; 

  $sql = "INSERT INTO USER (firstname, lastname, email, password, phone_no)
          VALUES ('$firstname', '$lastname', '$email', '$password', '$phone_no')";

  if ($conn->query($sql) === TRUE) {
    $_SESSION['user_id']  = $conn->insert_id;
    $_SESSION['username'] = $firstname;          
    $conn->close();
    header('Location: index.php');
    exit;
  } else {
    echo "Error: " . $conn->error;
  }
}

if (isset($_POST['signin_btn'])) {
  $signin_email = trim($_POST['signin_email']);
  $signin_pass  = $_POST['signin_password'];

  $stmt = $conn->prepare("SELECT uid, firstname, password FROM USER WHERE email = ? LIMIT 1");
  $stmt->bind_param("s", $signin_email);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if ($row['password'] === $signin_pass) {     
      $_SESSION['user_id']  = $row['uid'];
      $_SESSION['username'] = $row['firstname'];
      $conn->close();
      header('Location: index.php');
      exit;
    } else {
      $signin_error = "Incorrect password. Please try again.";
    }
  } else {
    $signin_error = "No account found with that email.";
  }
  $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — Rasoi</title>

  <link rel="stylesheet" href="style.css">

  <style>
    .signin-grid {
      min-height: calc(100vh - 64px);
      display: grid;
      grid-template-columns: 1fr 1fr;
    }

    .signin-left {
      background: var(--dark-mid);
      padding: 60px 52px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .left-tagline {
      font-family: 'Cinzel', serif;
      font-size: 11px;
      letter-spacing: 0.25em;
      color: var(--gold);
      text-transform: uppercase;
      margin-bottom: 12px;
    }

    .left-h {
      font-family: 'Cormorant Garamond', serif;
      font-size: 46px;
      font-weight: 300;
      color: var(--gold);
      line-height: 1.12;
      margin-bottom: 24px;
    }

    .left-p {
      font-size: 16px;
      color: rgba(255,255,255,0.5);
      line-height: 1.8;
      max-width: 320px;
    }

    .left-stats {
      display: grid;
      grid-template-columns: 1fr 1fr;
      margin-top: 48px;
      padding-top: 32px;
      border-top: 1px solid rgba(255,255,255,0.1);
    }

    .stat-num-left {
      font-family: 'Cormorant Garamond', serif;
      font-size: 24px;
      color: var(--gold);
      display: block;
      margin-bottom: 3px;
    }

    .stat-lbl-left {
      font-size: 13px;
      color: rgba(255,255,255,0.38);
    }

    .signin-right {
      background: var(--cream);
      padding: 60px 52px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .auth-tabs {
      display: flex;
      margin-bottom: 36px;
      border-bottom: 2px solid var(--cream-dark);
    }

    .auth-tab {
      padding: 12px 0;
      margin-right: 32px;
      font-family: 'Cinzel', serif;
      font-size: 11px;
      letter-spacing: 0.2em;
      text-transform: uppercase;
      color: var(--muted);
      cursor: pointer;
      border: none;
      border-bottom: 2px solid transparent;
      background: none;
      transition: all 0.2s;
    }

    .auth-tab.active {
      color: var(--spice);
      border-bottom-color: var(--spice);
    }

    .auth-panel { display: none; }

    .auth-panel.active {
      display: block;
      animation: fadeUp 0.3s ease;
    }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(8px); }
      to   { opacity: 1; transform: none; }
    }

    .auth-welcome {
      font-family: 'Cormorant Garamond', serif;
      font-size: 13px;
      font-style: italic;
      color: var(--spice);
      margin-bottom: 6px;
    }

    .auth-welcome::before { content: '— '; }

    .auth-headline {
      font-family: 'Cormorant Garamond', serif;
      font-size: 38px;
      font-weight: 400;
      color: var(--dark);
      margin-bottom: 32px;
      line-height: 1.15;
    }

    .auth-headline em { color: var(--spice); font-style: italic; }

    .auth-footer {
      margin-top: 16px;
      text-align: center;
      font-size: 13px;
      color: var(--muted);
    }

    .auth-footer a { color: var(--spice); text-decoration: none; cursor: pointer; }
  </style>
</head>

<body>

  <nav>
    <a href="index.php" class="nav-logo">Rasoi</a>

    <ul class="nav-links">
      <li><a href="menu.html">Menu</a></li>
      <li><a href="order.html">Order Online</a></li>
      <li><a href="about.html">About</a></li>
      <li><a href="locations.html">Locations</a></li>
    </ul>

    <div class="nav-right">
      <a href="order.html" class="nav-cart-btn">
        Cart <span id="cart-badge" class="cart-badge" style="display:none">0</span>
      </a>
      <?php if (isset($_SESSION['username'])): ?>
        <div class="nav-auth-user">
          <span class="nav-welcome">Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
          <a href="logout.php" class="nav-signout">Sign Out</a>
        </div>
      <?php else: ?>
        <a href="signin.php" class="nav-auth active">Sign-in / Sign-up</a>
      <?php endif; ?>
    </div>
  </nav>

  <div class="page-wrap" style="padding-top:64px">

    <div class="signin-grid">

      <div class="signin-left">
        <div class="left-tagline">Rasoi ~ of the world</div>

        <div class="left-h">A Table Awaits<br>You Everyday</div>

        <div class="left-p">
          Sign in to manage your reservations, explore personalised menus,
          and unlock member-only dining experiences.
        </div>

        <div class="left-stats">
          <div>
            <span class="stat-num-left">15</span>
            <span class="stat-lbl-left">Years Of Craft</span>
          </div>
          <div>
            <span class="stat-num-left">50k+</span>
            <span class="stat-lbl-left">Happy Guests</span>
          </div>
        </div>
      </div>

      <div class="signin-right">

        <div class="auth-tabs">
          <button class="auth-tab active" onclick="switchAuth('signin',this)">Sign In</button>
          <button class="auth-tab" onclick="switchAuth('create',this)">Create Account</button>
        </div>

        <!-- SIGN IN FORM -->
        <form method="POST">
          <div id="auth-signin" class="auth-panel active">
            <p class="auth-welcome">welcome back</p>

            <h2 class="auth-headline">Sign in to<br><em>Rasoi</em></h2>

            <div class="form-group">
              <label>Email Address</label>
              <input type="email" name="signin_email" required>
            </div>

            <div class="form-group">
              <label>Password</label>
              <input type="password" name="signin_password" required>
            </div>

            <?php if ($signin_error): ?>
              <p style="color:#B5451B; font-size:13px; margin-bottom:14px;"><?= htmlspecialchars($signin_error) ?></p>
            <?php endif; ?>

            <button type="submit" class="btn-submit" name="signin_btn">Sign In →</button>

            <p class="auth-footer"><a>Forgot your password?</a></p>
          </div>
        </form>

        <!-- CREATE ACCOUNT FORM -->
        <form method="POST">
          <div id="auth-create" class="auth-panel">
            <p class="auth-welcome">join us</p>

            <h2 class="auth-headline">Create your<br><em>Rasoi</em> account</h2>

            <div class="form-row">
              <div class="form-group">
                <label>First Name</label>
                <input type="text" name="firstname" required>
              </div>
              <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="lastname" required>
              </div>
            </div>

            <div class="form-group">
              <label>Email Address</label>
              <input type="email" name="email" required>
            </div>

            <!-- PHONE NUMBER — stored in user.phone_no (varchar 15) -->
            <div class="form-group">
              <label>Phone Number</label>
              <input type="tel" name="phone_no" placeholder="+91 98765 43210"
                     maxlength="15" required>
            </div>

            <div class="form-group">
              <label>Password</label>
              <input type="password" name="password" required>
            </div>

            <button type="submit" class="btn-submit" name="signup_btn">Create Account →</button>
          </div>
        </form>

      </div>
    </div>

    <footer>
      <span class="footer-logo">Rasoi</span>
      <span class="footer-copy">© 2026 Rasoi. All rights reserved.</span>
      <a href="locations.html" class="footer-contact">Contact</a>
    </footer>

  </div>

  <script src="cart.js"></script>

  <script>
    function switchAuth(id, btn) {
      document.querySelectorAll('.auth-tab').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.auth-panel').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById('auth-' + id).classList.add('active');
    }
  </script>

</body>
</html>