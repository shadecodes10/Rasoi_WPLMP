<?php
session_start();

$conn = new mysqli("localhost", "root", "", "restaurant_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$branches = [];
$bres = $conn->query("SELECT bid, bname FROM BRANCHES ORDER BY bname");
while ($b = $bres->fetch_assoc()) $branches[] = $b;

$prices = [];
$pres = $conn->query("SELECT dname, dprice FROM dishes");
while ($row = $pres->fetch_assoc()) $prices[$row['dname']] = (float)$row['dprice'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $dname = trim($_POST['dname']);
    $qty   = max(1, (int)$_POST['qty']);
    if (isset($prices[$dname])) {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        $_SESSION['cart'][$dname] = isset($_SESSION['cart'][$dname])
            ? $_SESSION['cart'][$dname] + $qty
            : $qty;
    }
    header("Location: cart.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    foreach ($_POST['item_qty'] as $dname => $qty) {
        $qty = (int)$qty;
        if ($qty <= 0) unset($_SESSION['cart'][$dname]);
        elseif (isset($prices[$dname])) $_SESSION['cart'][$dname] = $qty;
    }
    header("Location: cart.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_cart'])) {
    $_SESSION['cart'] = [];
    header("Location: cart.php");
    exit;
}

$pending_oid = $_SESSION['pending_oid'] ?? 0;
$pending_amt = $_SESSION['pending_amt'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (!isset($_SESSION['user_id'])) { header("Location: signin.php"); exit; }
    $uid   = (int)$_SESSION['user_id'];
    $bid   = (int)$_POST['bid'];
    $total = 0;
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $dname => $qty) {
            $qty = (int)$qty;
            if ($qty > 0 && isset($prices[$dname])) $total += $prices[$dname] * $qty;
        }
    }
    if ($total <= 0) {
        $error_msg = "Your cart is empty.";
    } elseif ($bid <= 0) {
        $error_msg = "Please select a branch.";
    } else {
        $stmt = $conn->prepare("INSERT INTO orders (uid, bid, oamount) VALUES (?, ?, ?)");
        $stmt->bind_param("iid", $uid, $bid, $total);
        if ($stmt->execute()) {
            $new_oid = $conn->insert_id;
            $_SESSION['cart']        = [];
            $_SESSION['pending_oid'] = $new_oid;
            $_SESSION['pending_amt'] = $total;
            $pending_oid = $new_oid;
            $pending_amt = $total;
        } else {
            $error_msg = "Something went wrong. Please try again.";
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    if (!isset($_SESSION['user_id'])) { header("Location: signin.php"); exit; }
    $oid    = (int)($_POST['pay_oid'] ?? 0);
    $amount = (float)($_POST['pay_amount'] ?? 0);
    $method = $conn->real_escape_string($_POST['pay_method'] ?? 'Cash');
    if ($oid > 0 && $amount > 0) {
        $conn->query("INSERT INTO payments (oid, amount, method) VALUES ($oid, $amount, '$method')");
        unset($_SESSION['pending_oid'], $_SESSION['pending_amt']);
        header("Location: myorders.php");
        exit;
    }
}

$conn->close();

$cart      = $_SESSION['cart'] ?? [];
$cartTotal = 0;
foreach ($cart as $dname => $qty) {
    if (isset($prices[$dname])) $cartTotal += $prices[$dname] * $qty;
}
$cartCount = array_sum($cart);

$error_msg   = $error_msg ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Your Cart — Rasoi</title>
<link rel="stylesheet" href="style.css">
<style>
.cart-wrap { padding: 56px 48px 120px; max-width: 780px; margin: 0 auto; }
.cart-header { margin-bottom: 40px; }
.cart-header h1 { font-family: 'Cormorant Garamond', serif; font-size: 42px; font-weight: 400; color: var(--dark); }
.cart-header p { font-size: 16px; color: var(--muted); margin-top: 6px; }
.alert { padding: 14px 20px; font-size: 14px; margin-bottom: 28px; border-radius: 2px; }
.alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
.alert-error   { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
.cart-empty { text-align: center; padding: 80px 40px; color: var(--muted); }
.cart-empty-icon { font-size: 64px; margin-bottom: 20px; opacity: 0.35; }
.cart-empty h2 { font-family: 'Cormorant Garamond', serif; font-size: 28px; font-weight: 400; color: var(--dark); margin-bottom: 10px; }
.cart-empty p { font-size: 15px; margin-bottom: 28px; }
.cart-table { width: 100%; border-collapse: collapse; margin-bottom: 28px; }
.cart-table th { font-family: 'Cinzel', serif; font-size: 9px; letter-spacing: 0.22em; text-transform: uppercase; color: var(--muted); padding: 10px 0; text-align: left; border-bottom: 1px solid rgba(181,82,43,0.25); }
.cart-table th.th-right { text-align: right; }
.cart-table td { padding: 16px 0; border-bottom: 1px solid var(--border); vertical-align: middle; }
.cart-item-name { font-family: 'EB Garamond', serif; font-size: 18px; color: var(--text); }
.cart-item-price { font-family: 'Cormorant Garamond', serif; font-size: 17px; color: var(--muted); text-align: right; }
.cart-item-subtotal { font-family: 'Cormorant Garamond', serif; font-size: 18px; color: var(--spice); text-align: right; }
.cart-qty-wrap { display: flex; align-items: center; border: 1px solid var(--spice); overflow: hidden; width: fit-content; }
.cart-qty-btn { width: 30px; height: 32px; background: none; border: none; color: var(--spice); font-size: 18px; cursor: pointer; transition: background 0.15s; }
.cart-qty-btn:hover { background: var(--spice); color: #fff; }
.cart-qty-input { width: 44px; height: 32px; border: none; border-left: 1px solid var(--spice); border-right: 1px solid var(--spice); background: var(--cream-dark); text-align: center; font-family: 'Cormorant Garamond', serif; font-size: 16px; color: var(--dark); -moz-appearance: textfield; outline: none; }
.cart-qty-input::-webkit-inner-spin-button, .cart-qty-input::-webkit-outer-spin-button { -webkit-appearance: none; }
.btn-remove { background: none; border: none; color: var(--muted); font-size: 18px; cursor: pointer; transition: color 0.2s; padding: 4px 8px; }
.btn-remove:hover { color: #dc3545; }
.cart-summary { background: var(--cream-dark); padding: 28px 32px; margin-top: 8px; }
.summary-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid rgba(0,0,0,0.07); }
.summary-row:last-child { border-bottom: none; }
.summary-label { font-family: 'Cinzel', serif; font-size: 10px; letter-spacing: 0.18em; text-transform: uppercase; color: var(--muted); }
.summary-value { font-family: 'Cormorant Garamond', serif; font-size: 18px; color: var(--text); }
.summary-total-label { font-family: 'Cinzel', serif; font-size: 12px; letter-spacing: 0.2em; text-transform: uppercase; color: var(--dark); font-weight: 500; }
.summary-total-value { font-family: 'Cormorant Garamond', serif; font-size: 28px; color: var(--spice); }
.branch-section { margin-top: 24px; }
.branch-section label { display: block; font-family: 'Cinzel', serif; font-size: 10px; letter-spacing: 0.2em; color: var(--muted); text-transform: uppercase; margin-bottom: 8px; }
.branch-section select { width: 100%; background: var(--cream); border: 1px solid rgba(181,82,43,0.35); color: var(--text); padding: 12px 16px; font-family: 'EB Garamond', serif; font-size: 16px; outline: none; cursor: pointer; appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23b5522b' stroke-width='1.5' fill='none'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 16px center; padding-right: 40px; }
.cart-actions { display: flex; gap: 12px; margin-top: 24px; flex-wrap: wrap; }
.btn-place-order { flex: 1; background: var(--spice); color: #fff; border: none; padding: 15px 32px; font-family: 'Cinzel', serif; font-size: 11px; letter-spacing: 0.22em; text-transform: uppercase; cursor: pointer; transition: background 0.2s; }
.btn-place-order:hover { background: #9e4422; }
.btn-clear-cart { background: transparent; color: var(--muted); border: 1px solid var(--border); padding: 15px 24px; font-family: 'Cinzel', serif; font-size: 10px; letter-spacing: 0.16em; text-transform: uppercase; cursor: pointer; transition: all 0.2s; }
.btn-clear-cart:hover { border-color: #dc3545; color: #dc3545; }
.btn-continue-shopping { display: inline-block; background: transparent; color: var(--spice); border: 1px solid var(--spice); padding: 12px 24px; font-family: 'Cinzel', serif; font-size: 10px; letter-spacing: 0.18em; text-transform: uppercase; text-decoration: none; transition: all 0.2s; }
.btn-continue-shopping:hover { background: var(--spice); color: #fff; }
.login-notice { background: var(--cream-dark); border-left: 4px solid var(--spice); padding: 16px 20px; font-size: 15px; color: var(--muted); margin-top: 20px; }
.login-notice a { color: var(--spice); font-weight: 500; text-decoration: none; }

/* Payment Step */
.payment-box { background: #fff; border: 1px solid rgba(0,0,0,0.08); border-radius: 4px; padding: 36px; margin-top: 8px; }
.payment-title { font-family: 'Cormorant Garamond', serif; font-size: 28px; color: var(--dark); margin-bottom: 6px; }
.payment-sub { font-size: 15px; color: var(--muted); margin-bottom: 30px; }
.payment-methods { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 28px; }
.pay-option { position: relative; }
.pay-option input[type="radio"] { position: absolute; opacity: 0; }
.pay-option label { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 20px 12px; border: 2px solid var(--border); border-radius: 4px; cursor: pointer; transition: all 0.2s; font-family: 'Cinzel', serif; font-size: 10px; letter-spacing: 0.14em; text-transform: uppercase; color: var(--muted); }
.pay-option label:hover { border-color: var(--spice); color: var(--spice); }
.pay-option input[type="radio"]:checked + label { border-color: var(--spice); color: var(--spice); background: rgba(181,82,43,0.05); }
.pay-icon { font-size: 28px; }
.pay-amount-display { background: var(--cream-dark); padding: 18px 22px; border-radius: 2px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.pay-amount-label { font-family: 'Cinzel', serif; font-size: 10px; letter-spacing: 0.2em; text-transform: uppercase; color: var(--muted); }
.pay-amount-value { font-family: 'Cormorant Garamond', serif; font-size: 28px; color: var(--spice); }
.btn-confirm-pay { width: 100%; background: var(--spice); color: #fff; border: none; padding: 15px; font-family: 'Cinzel', serif; font-size: 11px; letter-spacing: 0.22em; text-transform: uppercase; cursor: pointer; transition: background 0.2s; }
.btn-confirm-pay:hover { background: #9e4422; }
.step-indicator { display: flex; align-items: center; gap: 10px; margin-bottom: 36px; font-family: 'Cinzel', serif; font-size: 10px; letter-spacing: 0.16em; text-transform: uppercase; color: var(--muted); }
.step-dot { width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 600; }
.step-dot.done { background: var(--spice); color: #fff; }
.step-dot.active { background: var(--dark); color: #fff; }
.step-dot.pending { background: var(--cream-dark); color: var(--muted); }
.step-line { flex: 1; height: 1px; background: var(--border); }
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
    <a href="cart.php" class="nav-cart-btn active">
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
    <div class="cart-wrap">

      <?php if ($pending_oid > 0): ?>

        <!-- ── STEP 2: PAYMENT ── -->
        <div class="cart-header">
          <p class="section-eyebrow" style="margin-bottom:10px">Checkout</p>
          <h1>Choose Payment</h1>
          <p>Order #<?= (int)$pending_oid ?> has been placed. Complete your payment below.</p>
        </div>

        <div class="step-indicator">
          <span class="step-dot done">✓</span>
          <span>Order Placed</span>
          <div class="step-line"></div>
          <span class="step-dot active">2</span>
          <span>Payment</span>
          <div class="step-line"></div>
          <span class="step-dot pending">3</span>
          <span>Done</span>
        </div>

        <div class="payment-box">
          <div class="payment-title">Payment Details</div>
          <div class="payment-sub">Select how you'd like to pay for your order</div>

          <form method="POST" action="cart.php">
            <input type="hidden" name="confirm_payment" value="1">
            <input type="hidden" name="pay_oid"    value="<?= (int)$pending_oid ?>">
            <input type="hidden" name="pay_amount" value="<?= (float)$pending_amt ?>">

            <div class="payment-methods">
              <div class="pay-option">
                <input type="radio" name="pay_method" id="pay-cash" value="Cash" checked>
                <label for="pay-cash"><span class="pay-icon">💵</span>Cash on Delivery</label>
              </div>
              <div class="pay-option">
                <input type="radio" name="pay_method" id="pay-upi" value="UPI">
                <label for="pay-upi"><span class="pay-icon">📱</span>UPI</label>
              </div>
              <div class="pay-option">
                <input type="radio" name="pay_method" id="pay-card" value="Card">
                <label for="pay-card"><span class="pay-icon">💳</span>Card</label>
              </div>
            </div>

            <div class="pay-amount-display">
              <span class="pay-amount-label">Total to Pay</span>
              <span class="pay-amount-value">&#8377;<?= number_format((float)$pending_amt, 2) ?></span>
            </div>

            <button type="submit" class="btn-confirm-pay">Confirm Payment →</button>
          </form>
        </div>

      <?php else: ?>

        <!-- ── STEP 1: CART ── -->
        <div class="cart-header">
          <p class="section-eyebrow" style="margin-bottom:10px">Review &amp; Checkout</p>
          <h1>Your Cart</h1>
          <p>
            <?php if ($cartCount > 0): ?>
              <?= $cartCount ?> item<?= $cartCount !== 1 ? 's' : '' ?> · Ready to order
            <?php else: ?>
              Your cart is empty
            <?php endif; ?>
          </p>
        </div>

        <?php if ($error_msg): ?>
          <div class="alert alert-error"><?= htmlspecialchars($error_msg) ?></div>
        <?php endif; ?>

        <?php if (empty($cart)): ?>
          <div class="cart-empty">
            <div class="cart-empty-icon">🛒</div>
            <h2>Nothing here yet</h2>
            <p>Browse our menu and add your favourite dishes.</p>
            <a href="order.php" class="btn-continue-shopping">Browse Menu →</a>
          </div>

        <?php else: ?>
          <div class="step-indicator">
            <span class="step-dot active">1</span>
            <span>Review Cart</span>
            <div class="step-line"></div>
            <span class="step-dot pending">2</span>
            <span>Payment</span>
            <div class="step-line"></div>
            <span class="step-dot pending">3</span>
            <span>Done</span>
          </div>

          <form method="POST" action="cart.php" id="cart-form">
            <table class="cart-table">
              <thead>
                <tr>
                  <th>Dish</th>
                  <th style="text-align:center">Qty</th>
                  <th class="th-right">Price</th>
                  <th class="th-right">Subtotal</th>
                  <th></th>
                </tr>
              </thead>
              <tbody id="cart-body">
                <?php foreach ($cart as $dname => $qty): ?>
                  <?php $unit = $prices[$dname] ?? 0; ?>
                  <tr data-price="<?= $unit ?>">
                    <td class="cart-item-name"><?= htmlspecialchars($dname) ?></td>
                    <td style="text-align:center">
                      <div class="cart-qty-wrap" style="margin:0 auto;">
                        <button type="button" class="cart-qty-btn" onclick="changeCartQty(this,-1)">−</button>
                        <input class="cart-qty-input" type="number"
                          name="item_qty[<?= htmlspecialchars($dname) ?>]"
                          value="<?= (int)$qty ?>" min="0" max="20"
                          oninput="recalcCart()">
                        <button type="button" class="cart-qty-btn" onclick="changeCartQty(this,1)">+</button>
                      </div>
                    </td>
                    <td class="cart-item-price">₹<?= number_format($unit, 0) ?></td>
                    <td class="cart-item-subtotal">₹<?= number_format($unit * $qty, 0) ?></td>
                    <td>
                      <button type="button" class="btn-remove" onclick="removeItem(this)">✕</button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <input type="hidden" name="update_cart" value="1">
            <button type="submit" style="display:none" id="auto-save"></button>
          </form>

          <form method="POST" action="cart.php" id="checkout-form">
            <div class="cart-summary">
              <div class="summary-row">
                <span class="summary-label">Items</span>
                <span class="summary-value" id="cart-item-count"><?= $cartCount ?></span>
              </div>
              <div class="summary-row">
                <span class="summary-label">Subtotal</span>
                <span class="summary-value" id="cart-subtotal">₹<?= number_format($cartTotal, 0) ?></span>
              </div>
              <div class="summary-row">
                <span class="summary-total-label">Total</span>
                <span class="summary-total-value" id="cart-total">₹<?= number_format($cartTotal, 0) ?></span>
              </div>

              <div class="branch-section">
                <label for="bid">Select Branch</label>
                <select name="bid" id="bid" required>
                  <option value="" disabled selected>— Choose your nearest Rasoi —</option>
                  <?php foreach ($branches as $b): ?>
                    <option value="<?= (int)$b['bid'] ?>"><?= htmlspecialchars($b['bname']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="login-notice">
                  🔒 <strong>Please sign in to place your order.</strong>
                  <a href="signin.php">Sign in / Create account →</a>
                </div>
              <?php endif; ?>
            </div>

            <?php foreach ($cart as $dname => $qty): ?>
              <input type="hidden" name="item_qty[<?= htmlspecialchars($dname) ?>]" value="<?= (int)$qty ?>">
            <?php endforeach; ?>

            <div class="cart-actions">
              <?php if (isset($_SESSION['user_id'])): ?>
                <button type="submit" name="place_order" class="btn-place-order">Place Order →</button>
              <?php else: ?>
                <a href="signin.php" class="btn-place-order" style="text-align:center;text-decoration:none;">Sign In to Order →</a>
              <?php endif; ?>
              <button type="submit" form="cart-form" name="clear_cart" value="1" class="btn-clear-cart"
                onclick="return confirm('Clear your entire cart?')">Clear Cart</button>
              <a href="order.php" class="btn-continue-shopping">+ Add More</a>
            </div>
          </form>
        <?php endif; ?>

      <?php endif; ?>

    </div>
  </div>

  <footer>
    <span class="footer-logo">Rasoi</span>
    <span class="footer-copy">© 2026 Rasoi. All rights reserved.</span>
    <a href="locations.php" class="footer-contact">Contact</a>
  </footer>
</div>

<script>
function changeCartQty(btn, delta) {
  const input = btn.closest('.cart-qty-wrap').querySelector('.cart-qty-input');
  input.value = Math.max(0, Math.min(20, parseInt(input.value || 0) + delta));
  recalcCart();
  clearTimeout(window._saveTimer);
  window._saveTimer = setTimeout(() => document.getElementById('auto-save').click(), 600);
}

function removeItem(btn) {
  btn.closest('tr').querySelector('.cart-qty-input').value = 0;
  recalcCart();
  clearTimeout(window._saveTimer);
  window._saveTimer = setTimeout(() => document.getElementById('auto-save').click(), 200);
}

function recalcCart() {
  let total = 0, count = 0;
  document.querySelectorAll('#cart-body tr').forEach(function(row) {
    const price = parseFloat(row.dataset.price) || 0;
    const inp   = row.querySelector('.cart-qty-input');
    const qty   = parseInt(inp ? inp.value : 0) || 0;
    count += qty;
    total += price * qty;
  });
  const fmt = n => '₹' + Math.round(n).toLocaleString('en-IN');
  document.getElementById('cart-total').textContent      = fmt(total);
  document.getElementById('cart-subtotal').textContent   = fmt(total);
  document.getElementById('cart-item-count').textContent = count;
}
</script>
</body>
</html>
