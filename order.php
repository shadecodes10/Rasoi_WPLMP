<?php
session_start();

$conn = new mysqli("localhost", "root", "", "restaurant_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* ── ADD / SET ITEM IN CART (fetch-based, no redirect) ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $dname = trim($_POST['dname']);
    $qty   = (int)($_POST['qty'] ?? 1);

    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

    if ($qty <= 0) {
        unset($_SESSION['cart'][$dname]);
    } else {
        /* Validate dish exists */
        $pstmt = $conn->prepare("SELECT dprice FROM dishes WHERE dname = ? LIMIT 1");
        $pstmt->bind_param("s", $dname);
        $pstmt->execute();
        $pres = $pstmt->get_result();
        if ($pres->fetch_assoc()) {
            $_SESSION['cart'][$dname] = $qty;   /* SET (not add) — stepper controls absolute qty */
        }
        $pstmt->close();
    }

    /* If it's a fetch call (no redirect needed) just exit cleanly */
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_SERVER['HTTP_FETCH_DEST'])) {
        http_response_code(200);
        exit;
    }
    /* Fallback normal form post */
    header("Location: order.php?cat=" . urlencode($_POST['cat'] ?? ''));
    exit;
}

/* ── REMOVE ITEM FROM CART ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    $dname = trim($_POST['dname']);
    if (isset($_SESSION['cart'][$dname])) {
        unset($_SESSION['cart'][$dname]);
    }
    http_response_code(200);
    exit;
}

/* ── FETCH DISHES BY CATEGORY ── */
$categories_order = ['Starters', 'Main Course', 'Breads', 'Dessert', 'Drinks'];
$menu = [];
$res = $conn->query("SELECT dname, dprice, dcategory FROM dishes ORDER BY dcategory, dname");
while ($row = $res->fetch_assoc()) {
    $menu[$row['dcategory']][] = $row;
}

/* All available categories in preferred order */
$all_cats = [];
foreach (array_merge($categories_order, array_keys($menu)) as $c) {
    if (!in_array($c, $all_cats) && !empty($menu[$c])) {
        $all_cats[] = $c;
    }
}

$conn->close();

/* ── DEFAULT ACTIVE CATEGORY ── */
$active_cat = $all_cats[0] ?? '';

/* ── CART COUNT ── */
$cartCount = array_sum($_SESSION['cart'] ?? []);

/* ── ADDED ITEM FLASH ── */
$added_item = $_GET['added'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Online — Rasoi</title>
<link rel="stylesheet" href="style.css">
<style>
/* ── PAGE LAYOUT ── */
.order-wrap { padding: 56px 48px 100px; max-width: 860px; margin: 0 auto; }
.order-header { margin-bottom: 36px; }
.order-header h1 {
  font-family: 'Cormorant Garamond', serif;
  font-size: 42px; font-weight: 400; color: var(--dark);
}
.order-header p { font-size: 16px; color: var(--muted); margin-top: 6px; }

/* ── FLASH ── */
.flash-added {
  display: flex; align-items: center; gap: 10px;
  background: #d4edda; color: #155724;
  border-left: 4px solid #28a745;
  padding: 12px 18px; font-size: 14px;
  margin-bottom: 24px; border-radius: 2px;
  animation: fadeUp 0.3s ease;
}

/* ── LOGIN NOTICE ── */
.login-notice {
  background: var(--cream-dark);
  border-left: 4px solid var(--spice);
  padding: 16px 20px; font-size: 15px;
  color: var(--muted); margin-bottom: 32px; border-radius: 2px;
}
.login-notice a { color: var(--spice); font-weight: 500; text-decoration: none; }
.login-notice a:hover { text-decoration: underline; }

/* ── CATEGORY TABS ── */
.order-tabs {
  display: flex; gap: 2px; margin-bottom: 40px;
  background: var(--cream-dark); padding: 4px; border-radius: 2px;
  flex-wrap: wrap;
}
.order-tab-btn {
  flex: 1; background: transparent; border: none;
  padding: 10px 8px;
  font-family: 'Cinzel', serif; font-size: 10px; letter-spacing: 0.16em;
  text-transform: uppercase; color: var(--muted); cursor: pointer;
  transition: all 0.2s; border-radius: 1px; white-space: nowrap;
}
.order-tab-btn.active { background: var(--spice); color: #fff; }
.order-panel { display: none; animation: fadeUp 0.3s ease; }
.order-panel.active { display: block; }

/* ── CATEGORY TITLE ── */
.order-cat-title {
  font-family: 'Cormorant Garamond', serif;
  font-size: 26px; font-style: italic; color: var(--spice);
  margin: 8px 0 18px; padding-bottom: 10px;
  border-bottom: 1px solid rgba(181,82,43,0.25);
}

/* ── MENU ITEM ROW ── */
.order-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 0;
  border-bottom: 1px solid var(--border);
  gap: 16px;
  animation: fadeUp 0.25s ease;
}
.order-item-info { flex: 1; }
.order-item-name { font-size: 17px; color: var(--text); }
.order-price {
  font-family: 'Cormorant Garamond', serif;
  font-size: 17px; color: var(--spice);
  min-width: 68px; text-align: right;
}

/* ── ADD TO CART ── */
.btn-add-cart {
  background: none;
  border: 1px solid var(--spice);
  color: var(--spice);
  padding: 7px 18px;
  font-family: 'Cinzel', serif;
  font-size: 9px; letter-spacing: 0.18em;
  text-transform: uppercase; cursor: pointer;
  transition: all 0.2s; white-space: nowrap;
  border-radius: 2px;
}
.btn-add-cart:hover { background: var(--spice); color: #fff; }

/* ── INLINE QTY STEPPER ── */
.qty-stepper {
  display: none;
  align-items: center;
  border: 1px solid var(--spice);
  border-radius: 2px;
  overflow: hidden;
}
.qty-stepper.active { display: flex; }
.qty-step-btn {
  width: 32px; height: 34px;
  background: none; border: none;
  color: var(--spice); font-size: 18px;
  cursor: pointer; line-height: 1;
  transition: background 0.15s;
  font-family: sans-serif;
}
.qty-step-btn:hover { background: var(--spice); color: #fff; }
.qty-count {
  min-width: 30px; height: 34px;
  border-left: 1px solid var(--spice);
  border-right: 1px solid var(--spice);
  background: var(--cream-dark);
  text-align: center; line-height: 34px;
  font-family: 'Cormorant Garamond', serif;
  font-size: 16px; color: var(--dark);
  display: inline-block;
}

/* ── STICKY CART FOOTER ── */
.cart-sticky-bar {
  position: fixed;
  bottom: 0; left: 0; right: 0;
  background: var(--dark);
  padding: 16px 48px;
  display: flex; align-items: center;
  justify-content: space-between;
  z-index: 50;
  transform: translateY(100%);
  transition: transform 0.3s ease;
}
.cart-sticky-bar.visible { transform: translateY(0); }
.cart-sticky-info { color: rgba(255,255,255,0.75); font-size: 15px; }
.cart-sticky-info strong { color: var(--gold); font-family: 'Cormorant Garamond', serif; font-size: 20px; }
.btn-goto-cart {
  background: var(--spice); color: #fff; border: none;
  padding: 12px 32px;
  font-family: 'Cinzel', serif; font-size: 11px; letter-spacing: 0.2em;
  text-transform: uppercase; cursor: pointer;
  transition: background 0.2s; text-decoration: none;
  display: inline-block;
}
.btn-goto-cart:hover { background: #9e4422; }

@keyframes fadeUp {
  from { opacity: 0; transform: translateY(8px); }
  to   { opacity: 1; transform: none; }
}

/* hidden-btn utility */
.hidden-btn { display: none !important; }
</style>
</head>
<body>

<nav>
  <a href="index.php" class="nav-logo">Rasoi</a>
  <ul class="nav-links">
    <li><a href="menu.php">Menu</a></li>
    <li><a href="order.php" class="active">Order Online</a></li>
    <li><a href="about.php">About</a></li>
    <li><a href="locations.php">Locations</a></li>
  </ul>
  <div class="nav-right">
    <a href="cart.php" class="nav-cart-btn" id="nav-cart-link">
      🛒 Cart
      <?php if ($cartCount > 0): ?>
        <span class="cart-badge" id="cart-badge"><?= $cartCount ?></span>
      <?php else: ?>
        <span class="cart-badge" id="cart-badge" style="display:none">0</span>
      <?php endif; ?>
    </a>
    <?php if (isset($_SESSION['username'])): ?>
      <div class="nav-auth-user">
        <a href="profile.php" class="nav-welcome" style="text-decoration:none;"><?= htmlspecialchars($_SESSION['username']) ?></a>
        <a href="logout.php" class="nav-signout">Sign Out</a>
      </div>
    <?php else: ?>
      <a href="signin.php" class="nav-auth">Sign-in / Sign-up</a>
    <?php endif; ?>
  </div>
</nav>

<div class="page-wrap">
  <div class="page-content">
    <div class="order-wrap">

      <div class="order-header">
        <p class="section-eyebrow" style="margin-bottom:10px">Delivery &amp; Takeaway</p>
        <h1>Rasoi, Delivered.</h1>
        <p>Pick your dishes — we'll add them to your cart.</p>
      </div>

      <?php if (!isset($_SESSION['user_id'])): ?>
        <div class="login-notice">
          🔒 <strong>Sign in to place an order.</strong>
          <a href="signin.php">Sign in / Create account →</a>
        </div>
      <?php endif; ?>

      <?php if ($added_item): ?>
        <div class="flash-added">
          ✓ <strong><?= htmlspecialchars($added_item) ?></strong> added to your cart.
          <a href="cart.php" style="margin-left:auto; color:var(--spice); text-decoration:none; font-weight:500;">View Cart →</a>
        </div>
      <?php endif; ?>

      <!-- CATEGORY TABS -->
      <div class="order-tabs">
        <?php foreach ($all_cats as $i => $cat): ?>
          <button class="order-tab-btn <?= $i === 0 ? 'active' : '' ?>"
                  onclick="switchOrderTab('<?= htmlspecialchars(preg_replace('/[^a-z0-9]/i','_',$cat)) ?>', this)">
            <?= htmlspecialchars($cat) ?>
          </button>
        <?php endforeach; ?>
      </div>

      <!-- ALL CATEGORY PANELS (rendered server-side, shown/hidden by JS) -->
      <?php foreach ($all_cats as $i => $cat): ?>
        <div id="panel-<?= htmlspecialchars(preg_replace('/[^a-z0-9]/i','_',$cat)) ?>"
             class="order-panel <?= $i === 0 ? 'active' : '' ?>">

          <?php foreach (($menu[$cat] ?? []) as $dish): ?>
            <?php $inCart = $_SESSION['cart'][$dish['dname']] ?? 0; ?>
            <div class="order-item">
              <div class="order-item-info">
                <div class="order-item-name"><?= htmlspecialchars($dish['dname']) ?></div>
              </div>
              <div class="order-price">₹<?= number_format((float)$dish['dprice'], 0) ?></div>

              <div class="item-add-wrap"
                   data-dname="<?= htmlspecialchars($dish['dname'], ENT_QUOTES) ?>"
                   data-cat="<?= htmlspecialchars($cat, ENT_QUOTES) ?>">

                <button class="btn-add-cart <?= $inCart > 0 ? 'hidden-btn' : '' ?>"
                        onclick="showStepper(this.closest('.item-add-wrap'))">
                  + Add
                </button>

                <div class="qty-stepper <?= $inCart > 0 ? 'active' : '' ?>">
                  <button type="button" class="qty-step-btn" onclick="stepQty(this.closest('.item-add-wrap'), -1)">−</button>
                  <span class="qty-count"><?= $inCart > 0 ? $inCart : 1 ?></span>
                  <button type="button" class="qty-step-btn" onclick="stepQty(this.closest('.item-add-wrap'), 1)">+</button>
                </div>

              </div>
            </div>
          <?php endforeach; ?>

        </div>
      <?php endforeach; ?>

    </div><!-- .order-wrap -->
  </div>

  <footer>
    <span class="footer-logo">Rasoi</span>
    <span class="footer-copy">© 2026 Rasoi. All rights reserved.</span>
    <a href="locations.php" class="footer-contact">Contact</a>
  </footer>
</div>

<!-- STICKY CART BAR (shows when cart has items) -->
<div class="cart-sticky-bar <?= $cartCount > 0 ? 'visible' : '' ?>" id="sticky-bar">
  <div class="cart-sticky-info">
    <span id="sticky-count"><?= $cartCount ?></span> item<?= $cartCount !== 1 ? 's' : '' ?> in your cart
  </div>
  <a href="cart.php" class="btn-goto-cart">View Cart &amp; Checkout →</a>
</div>

<script>
/* ── CATEGORY TABS ── */
function switchOrderTab(id, btn) {
  document.querySelectorAll('.order-tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.order-panel').forEach(p => p.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('panel-' + id).classList.add('active');
}

/* ── SHOW STEPPER on first Add click ── */
function showStepper(wrap) {
  wrap.querySelector('.btn-add-cart').classList.add('hidden-btn');
  const stepper = wrap.querySelector('.qty-stepper');
  stepper.classList.add('active');
  /* Send qty=1 to cart */
  sendToCart(wrap.dataset.dname, 1, wrap.dataset.cat);
}

/* ── +/- STEPPER ── */
function stepQty(wrap, delta) {
  const countEl = wrap.querySelector('.qty-count');
  const current = parseInt(countEl.textContent) || 0;
  const next    = current + delta;

  if (next <= 0) {
    /* Remove from cart and revert to Add button */
    wrap.querySelector('.qty-stepper').classList.remove('active');
    wrap.querySelector('.btn-add-cart').classList.remove('hidden-btn');
    countEl.textContent = 1;
    sendToCart(wrap.dataset.dname, 0, wrap.dataset.cat); /* qty=0 means remove */
  } else if (next <= 20) {
    countEl.textContent = next;
    sendToCart(wrap.dataset.dname, next, wrap.dataset.cat);
  }
}

/* ── FETCH helper — posts cart update without page reload ── */
function sendToCart(dname, qty, cat) {
  const form = new FormData();
  form.append('dname', dname);
  form.append('qty', qty);
  form.append('cat', cat);
  if (qty <= 0) {
    form.append('remove_item', '1');
  } else {
    form.append('add_item', '1');
  }
  fetch('order.php', { method: 'POST', body: form })
    .then(() => updateNavBadge());
}

/* ── Update cart badge in nav ── */
function updateNavBadge() {
  fetch('cart_count.php')
    .then(r => r.text())
    .then(n => {
      const badge = document.getElementById('cart-badge');
      if (badge) {
        const count = parseInt(n) || 0;
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline' : 'none';
        const bar = document.getElementById('sticky-bar');
        if (bar) bar.classList.toggle('visible', count > 0);
        const sc = document.getElementById('sticky-count');
        if (sc) sc.textContent = count;
      }
    });
}

/* ── SHOW/HIDE STICKY BAR on load ── */
(function() {
  const bar   = document.getElementById('sticky-bar');
  const count = <?= $cartCount ?>;
  if (count > 0) bar.classList.add('visible');
})();
</script>

</body>
</html>
