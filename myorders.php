<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}
$conn = new mysqli("localhost", "root", "", "restaurant_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$uid = (int)$_SESSION['user_id'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $oid    = (int)($_POST['oid'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = $conn->real_escape_string(trim($_POST['comment'] ?? ''));

    if ($oid > 0 && $rating >= 1 && $rating <= 5) {
        $check = $conn->query("SELECT rid FROM reviews WHERE oid = $oid AND uid = $uid LIMIT 1");
        if ($check && $check->num_rows === 0) {
            $bidq = $conn->query("SELECT bid FROM orders WHERE oid = $oid AND uid = $uid LIMIT 1");
            if ($bidq && $brow = $bidq->fetch_assoc()) {
                $bid = (int)$brow['bid'];
                $conn->query("INSERT INTO reviews (uid, bid, oid, rating, comment) VALUES ($uid, $bid, $oid, $rating, '$comment')");
                $msg = 'success';
            }
        } else {
            $msg = 'already';
        }
    }
    header("Location: myorders.php?msg=$msg");
    exit;
}

$orders = $conn->query("
    SELECT o.oid, o.oamount, o.ostatus, b.bname,
           (SELECT rid FROM reviews r WHERE r.oid = o.oid AND r.uid = o.uid LIMIT 1) AS reviewed
    FROM orders o
    LEFT JOIN branches b ON o.bid = b.bid
    WHERE o.uid = $uid
    ORDER BY o.oid DESC
");

$cartCount = array_sum($_SESSION['cart'] ?? []);
$flash     = $_GET['msg'] ?? '';
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Orders — Rasoi</title>
<link rel="stylesheet" href="style.css">
<style>
.orders-wrap { padding: 56px 48px 100px; max-width: 840px; margin: 0 auto; }
.orders-header { margin-bottom: 36px; }
.orders-header h1 { font-family: 'Cormorant Garamond', serif; font-size: 42px; font-weight: 400; color: var(--dark); }
.orders-header p { font-size: 16px; color: var(--muted); margin-top: 6px; }
.alert { padding: 14px 20px; font-size: 14px; margin-bottom: 24px; border-radius: 2px; }
.alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
.alert-info    { background: #d1ecf1; color: #0c5460; border-left: 4px solid #17a2b8; }
.order-card { background: #fff; border: 1px solid rgba(0,0,0,0.08); border-radius: 4px; padding: 24px 28px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; gap: 20px; transition: box-shadow 0.2s; }
.order-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
.order-card-left { flex: 1; }
.order-id { font-family: 'Cinzel', serif; font-size: 11px; letter-spacing: 0.2em; color: var(--muted); text-transform: uppercase; margin-bottom: 6px; }
.order-branch { font-family: 'Cormorant Garamond', serif; font-size: 22px; color: var(--dark); }
.order-amount { font-size: 15px; color: var(--muted); margin-top: 4px; }
.order-status { display: inline-block; padding: 4px 14px; border-radius: 20px; font-size: 12px; font-family: 'Cinzel', serif; letter-spacing: 0.12em; text-transform: uppercase; }
.status-preparing { background: #fff3e0; color: #a05a00; }
.status-delivered  { background: #e8f4ea; color: #2a6b35; }
.btn-feedback { background: var(--spice); color: #fff; border: none; padding: 10px 22px; font-family: 'Cinzel', serif; font-size: 10px; letter-spacing: 0.18em; text-transform: uppercase; cursor: pointer; border-radius: 2px; transition: background 0.2s; margin-top: 12px; display: inline-block; }
.btn-feedback:hover { background: #9e4422; }
.reviewed-note { font-size: 13px; color: var(--muted); font-style: italic; margin-top: 10px; }
.empty-state { text-align: center; padding: 80px 40px; }
.empty-state-icon { font-size: 56px; opacity: 0.3; margin-bottom: 16px; }
.empty-state h2 { font-family: 'Cormorant Garamond', serif; font-size: 30px; color: var(--dark); margin-bottom: 8px; }
.empty-state p { color: var(--muted); margin-bottom: 24px; }

/* Modal */
.modal-bg { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 200; align-items: center; justify-content: center; backdrop-filter: blur(3px); }
.modal-bg.open { display: flex; }
.modal-box { background: var(--cream); border-radius: 6px; padding: 40px; width: 480px; max-width: 95vw; animation: slideUp 0.25s ease; }
@keyframes slideUp { from { opacity:0; transform: translateY(20px); } to { opacity:1; transform: none; } }
.modal-title { font-family: 'Cormorant Garamond', serif; font-size: 28px; color: var(--dark); margin-bottom: 6px; }
.modal-sub { font-size: 14px; color: var(--muted); margin-bottom: 28px; }
.star-row { display: flex; gap: 8px; margin-bottom: 24px; }
.star-btn { font-size: 32px; background: none; border: none; cursor: pointer; opacity: 0.3; transition: opacity 0.15s, transform 0.15s; }
.star-btn.active, .star-btn:hover { opacity: 1; transform: scale(1.15); }
.modal-actions { display: flex; gap: 10px; margin-top: 24px; justify-content: flex-end; }
.btn-cancel-modal { padding: 10px 20px; background: var(--cream-dark); color: var(--muted); border: none; border-radius: 2px; cursor: pointer; font-family: 'Cinzel', serif; font-size: 10px; letter-spacing: 0.14em; text-transform: uppercase; }
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
    <?php if (isset($_SESSION['username'])): ?>
      <div class="nav-auth-user">
        <a href="profile.php" class="nav-welcome" style="text-decoration:none;">
          <?= htmlspecialchars($_SESSION['username']) ?>
        </a>
        <a href="logout.php" class="nav-signout">Sign Out</a>
      </div>
    <?php else: ?>
      <a href="signin.php" class="nav-auth">Sign-in / Sign-up</a>
    <?php endif; ?>
  </div>
</nav>

<div class="page-wrap">
  <div class="page-content">
    <div class="orders-wrap">

      <div class="orders-header">
        <p class="section-eyebrow" style="margin-bottom:10px">Order History</p>
        <h1>My Orders</h1>
        <p>Track your orders and share your experience</p>
      </div>

      <?php if ($flash === 'success'): ?>
        <div class="alert alert-success">&#10003; Thank you for your feedback! Your review has been saved.</div>
      <?php elseif ($flash === 'already'): ?>
        <div class="alert alert-info">You have already reviewed this order.</div>
      <?php endif; ?>

      <?php if ($orders && $orders->num_rows > 0):
        while ($o = $orders->fetch_assoc()):
          $delivered = ($o['ostatus'] === 'delivered');
          $reviewed  = !empty($o['reviewed']);
      ?>
        <div class="order-card">
          <div class="order-card-left">
            <div class="order-id">Order #<?= (int)$o['oid'] ?></div>
            <div class="order-branch"><?= htmlspecialchars($o['bname'] ?? 'Rasoi Branch') ?></div>
            <div class="order-amount">Total: &#8377;<?= number_format((float)$o['oamount'], 2) ?></div>
            <?php if ($delivered && !$reviewed): ?>
              <button class="btn-feedback" onclick="openFeedback(<?= (int)$o['oid'] ?>, '<?= htmlspecialchars($o['bname'] ?? 'Rasoi', ENT_QUOTES) ?>')">
                ★ Leave Feedback
              </button>
            <?php elseif ($reviewed): ?>
              <div class="reviewed-note">★ Review submitted — thank you!</div>
            <?php endif; ?>
          </div>
          <div style="text-align:right;">
            <span class="order-status <?= $delivered ? 'status-delivered' : 'status-preparing' ?>">
              <?= $delivered ? '✓ Delivered' : '⏳ Preparing' ?>
            </span>
          </div>
        </div>
      <?php endwhile;
      else: ?>
        <div class="empty-state">
          <div class="empty-state-icon">🛒</div>
          <h2>No orders yet</h2>
          <p>Place your first order and it will appear here.</p>
          <a href="order.php" class="btn-primary">Order Now →</a>
        </div>
      <?php endif; ?>

    </div>
  </div>

  <footer>
    <span class="footer-logo">Rasoi</span>
    <span class="footer-copy">© 2026 Rasoi. All rights reserved.</span>
    <a href="locations.php" class="footer-contact">Contact</a>
  </footer>
</div>

<!-- Feedback Modal -->
<div class="modal-bg" id="feedback-modal">
  <div class="modal-box">
    <div class="modal-title">Rate Your Experience</div>
    <div class="modal-sub" id="modal-branch-name">How was your order?</div>
    <form method="POST" action="myorders.php">
      <input type="hidden" name="submit_review" value="1">
      <input type="hidden" name="oid" id="modal-oid" value="">
      <input type="hidden" name="rating" id="modal-rating" value="0">

      <div class="star-row" id="star-row">
        <button type="button" class="star-btn" data-val="1" onclick="setRating(1)">★</button>
        <button type="button" class="star-btn" data-val="2" onclick="setRating(2)">★</button>
        <button type="button" class="star-btn" data-val="3" onclick="setRating(3)">★</button>
        <button type="button" class="star-btn" data-val="4" onclick="setRating(4)">★</button>
        <button type="button" class="star-btn" data-val="5" onclick="setRating(5)">★</button>
      </div>

      <div class="form-group">
        <label>Leave a Comment (optional)</label>
        <textarea name="comment" rows="3" placeholder="Tell us about your experience…"></textarea>
      </div>

      <div class="modal-actions">
        <button type="button" class="btn-cancel-modal" onclick="closeFeedback()">Cancel</button>
        <button type="submit" class="btn-submit" style="width:auto;padding:10px 28px;margin-top:0;">Submit Review</button>
      </div>
    </form>
  </div>
</div>

<script>
function openFeedback(oid, branch) {
  document.getElementById('modal-oid').value = oid;
  document.getElementById('modal-branch-name').textContent = 'How was your order from ' + branch + '?';
  document.getElementById('modal-rating').value = 0;
  document.querySelectorAll('.star-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('feedback-modal').classList.add('open');
}
function closeFeedback() {
  document.getElementById('feedback-modal').classList.remove('open');
}
function setRating(val) {
  document.getElementById('modal-rating').value = val;
  document.querySelectorAll('.star-btn').forEach(b => {
    b.classList.toggle('active', parseInt(b.dataset.val) <= val);
  });
}
document.getElementById('feedback-modal').addEventListener('click', function(e) {
  if (e.target === this) closeFeedback();
});
</script>
</body>
</html>
