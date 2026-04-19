<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}
$conn = new mysqli("localhost", "root", "", "restaurant_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$uid  = (int)$_SESSION['user_id'];
$msg  = '';
$err  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $firstname = $conn->real_escape_string(trim($_POST['firstname'] ?? ''));
        $lastname  = $conn->real_escape_string(trim($_POST['lastname']  ?? ''));
        $phone     = $conn->real_escape_string(trim($_POST['phone_no']  ?? ''));
        if ($firstname) {
            $conn->query("UPDATE user SET firstname='$firstname', lastname='$lastname', phone_no='$phone' WHERE uid=$uid");
            $_SESSION['username'] = $firstname;
            $msg = 'Profile updated successfully.';
        } else {
            $err = 'First name is required.';
        }
    }

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $newpass = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $chk = $conn->query("SELECT password FROM user WHERE uid=$uid LIMIT 1");
        if ($chk && $row = $chk->fetch_assoc()) {
            if ($row['password'] !== $current) {
                $err = 'Current password is incorrect.';
            } elseif (strlen($newpass) < 4) {
                $err = 'New password must be at least 4 characters.';
            } elseif ($newpass !== $confirm) {
                $err = 'Passwords do not match.';
            } else {
                $np = $conn->real_escape_string($newpass);
                $conn->query("UPDATE user SET password='$np' WHERE uid=$uid");
                $msg = 'Password changed successfully.';
            }
        }
    }
}

$user = null;
$res = $conn->query("SELECT firstname, lastname, email, phone_no FROM user WHERE uid=$uid LIMIT 1");
if ($res) $user = $res->fetch_assoc();

$order_count      = 0;
$reservation_count = 0;
$review_count     = 0;

$oc = $conn->query("SELECT COUNT(*) as c FROM orders WHERE uid=$uid");
if ($oc && $r = $oc->fetch_assoc()) $order_count = (int)$r['c'];
$rc = $conn->query("SELECT COUNT(*) as c FROM reviews WHERE uid=$uid");
if ($rc && $r = $rc->fetch_assoc()) $review_count = (int)$r['c'];
$rv = $conn->query("SELECT COUNT(*) as c FROM reservation WHERE uid=$uid");
if ($rv && $r = $rv->fetch_assoc()) $reservation_count = (int)$r['c'];

$cartCount = array_sum($_SESSION['cart'] ?? []);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile — Rasoi</title>
<link rel="stylesheet" href="style.css">
<style>
.profile-wrap { padding: 56px 48px 100px; max-width: 760px; margin: 0 auto; }
.profile-header { display: flex; align-items: center; gap: 28px; margin-bottom: 48px; }
.profile-avatar { width: 80px; height: 80px; border-radius: 50%; background: var(--spice); display: flex; align-items: center; justify-content: center; font-family: 'Cormorant Garamond', serif; font-size: 36px; color: #fff; flex-shrink: 0; }
.profile-name { font-family: 'Cormorant Garamond', serif; font-size: 38px; color: var(--dark); }
.profile-email { font-size: 15px; color: var(--muted); margin-top: 4px; }
.stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 48px; }
.stat-box { background: var(--cream-dark); padding: 20px 18px; border-radius: 2px; text-align: center; }
.stat-box-val { font-family: 'Cormorant Garamond', serif; font-size: 32px; color: var(--spice); }
.stat-box-lbl { font-family: 'Cinzel', serif; font-size: 10px; letter-spacing: 0.18em; text-transform: uppercase; color: var(--muted); margin-top: 4px; }
.profile-section { background: #fff; border: 1px solid rgba(0,0,0,0.08); border-radius: 4px; padding: 32px; margin-bottom: 24px; }
.profile-section-title { font-family: 'Cormorant Garamond', serif; font-size: 22px; color: var(--dark); margin-bottom: 24px; padding-bottom: 14px; border-bottom: 1px solid var(--border); }
.alert { padding: 13px 18px; font-size: 14px; border-radius: 2px; margin-bottom: 22px; }
.alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
.alert-error   { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
.btn-save-profile { background: var(--spice); color: #fff; border: none; padding: 12px 28px; font-family: 'Cinzel', serif; font-size: 10px; letter-spacing: 0.18em; text-transform: uppercase; cursor: pointer; border-radius: 2px; transition: background 0.2s; }
.btn-save-profile:hover { background: #9e4422; }
</style>
</head>
<body>
<nav>
  <a href="index.php" class="nav-logo">Rasoi</a>
  <ul class="nav-links">
    <li><a href="menu.php">Menu</a></li>
    <li><a href="order.php">Order Online</a></li>
    <li><a href="about.php">About</a></li>
    <li><a href="locations.php">Locations</a></li>
  </ul>
  <div class="nav-right">
    <a href="cart.php" class="nav-cart-btn">
      🛒 Cart
      <?php if ($cartCount > 0): ?>
        <span class="cart-badge"><?= $cartCount ?></span>
      <?php endif; ?>
    </a>
    <div class="nav-auth-user">
      <a href="profile.php" class="nav-welcome" style="text-decoration:none;">
        <?= htmlspecialchars($_SESSION['username']) ?>
      </a>
      <a href="logout.php" class="nav-signout">Sign Out</a>
    </div>
  </div>
</nav>

<div class="page-wrap">
  <div class="page-content">
    <div class="profile-wrap">

      <?php if ($user): ?>
      <div class="profile-header">
        <div class="profile-avatar"><?= strtoupper(substr($user['firstname'], 0, 1)) ?></div>
        <div>
          <div class="profile-name"><?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?></div>
          <div class="profile-email"><?= htmlspecialchars($user['email']) ?></div>
        </div>
      </div>

      <div class="stats-row">
        <div class="stat-box">
          <div class="stat-box-val"><?= $order_count ?></div>
          <div class="stat-box-lbl">Orders Placed</div>
        </div>
        <div class="stat-box">
          <div class="stat-box-val"><?= $reservation_count ?></div>
          <div class="stat-box-lbl">Reservations</div>
        </div>
        <div class="stat-box">
          <div class="stat-box-val"><?= $review_count ?></div>
          <div class="stat-box-lbl">Reviews Given</div>
        </div>
      </div>

      <?php if ($msg): ?>
        <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>
      <?php if ($err): ?>
        <div class="alert alert-error"><?= htmlspecialchars($err) ?></div>
      <?php endif; ?>

      <div class="profile-section">
        <div class="profile-section-title">Personal Information</div>
        <form method="POST">
          <input type="hidden" name="action" value="update_profile">
          <div class="form-row">
            <div class="form-group">
              <label>First Name</label>
              <input type="text" name="firstname" value="<?= htmlspecialchars($user['firstname']) ?>" required>
            </div>
            <div class="form-group">
              <label>Last Name</label>
              <input type="text" name="lastname" value="<?= htmlspecialchars($user['lastname']) ?>">
            </div>
          </div>
          <div class="form-group">
            <label>Email Address</label>
            <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled style="opacity:0.6;cursor:not-allowed;">
          </div>
          <div class="form-group">
            <label>Phone Number</label>
            <input type="tel" name="phone_no" value="<?= htmlspecialchars($user['phone_no'] ?? '') ?>" maxlength="15">
          </div>
          <button type="submit" class="btn-save-profile">Save Changes</button>
        </form>
      </div>

      <div class="profile-section">
        <div class="profile-section-title">Change Password</div>
        <form method="POST">
          <input type="hidden" name="action" value="change_password">
          <div class="form-group">
            <label>Current Password</label>
            <input type="password" name="current_password" required>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>New Password</label>
              <input type="password" name="new_password" required>
            </div>
            <div class="form-group">
              <label>Confirm New Password</label>
              <input type="password" name="confirm_password" required>
            </div>
          </div>
          <button type="submit" class="btn-save-profile">Change Password</button>
        </form>
      </div>

      <div style="margin-top: 32px; text-align: center;">
        <a href="myorders.php" style="color:var(--spice); font-family:'Cinzel',serif; font-size:11px; letter-spacing:0.18em; text-transform:uppercase; text-decoration:none;">
          ← View My Orders
        </a>
      </div>

      <?php else: ?>
        <p style="color:var(--muted); text-align:center; padding: 60px 0;">Could not load profile data.</p>
      <?php endif; ?>

    </div>
  </div>

  <footer>
    <span class="footer-logo">Rasoi</span>
    <span class="footer-copy">© 2026 Rasoi. All rights reserved.</span>
    <a href="locations.php" class="footer-contact">Contact</a>
  </footer>
</div>
</body>
</html>
