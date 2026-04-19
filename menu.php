<?php
session_start();
$conn = new mysqli("localhost", "root", "", "restaurant_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Fetch all dishes grouped by category
$dishes_by_cat = [];
$res = $conn->query("SELECT dname, dprice, dcategory, dtype FROM dishes ORDER BY dcategory, dname");
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $cat = $row['dcategory'];
        $dishes_by_cat[$cat][] = $row;
    }
}

// Define tab → category mapping (dcategory values in DB)
// We group categories into frontend tabs
$tab_map = [
    'drinks'     => ['Drinks', 'Beverages', 'Mocktails', 'Lassi', 'Mocktails & Coolers', 'Lassi & Chaas'],
    'appetizers' => ['Appetizers', 'Starters', 'Tandoor Starters', 'Tawa & Snacks'],
    'maincourse' => ['Main Course', 'Mains', "Sabzi's", 'Dal & Lentils', 'Rice & Biryani'],
    'breads'     => ['Breads', 'From the Tandoor', 'Bread', 'Tandoor Breads'],
    'dessert'    => ['Dessert', 'Desserts', 'Traditional Sweets', 'Ice Creams & Kulfi', 'Ice Creams'],
];

// Also gather any uncategorized into a fallback
function getDishesForTab($dishes_by_cat, $categories) {
    $result = [];
    foreach($categories as $cat) {
        if(isset($dishes_by_cat[$cat])) {
            $result[$cat] = $dishes_by_cat[$cat];
        }
    }
    return $result;
}

// Identify which DB categories haven't been matched by any tab
$all_mapped = [];
foreach($tab_map as $categories) {
    $all_mapped = array_merge($all_mapped, $categories);
}
$extra_cats = [];
foreach($dishes_by_cat as $cat => $items) {
    if (!in_array($cat, $all_mapped)) {
        $extra_cats[$cat] = $items;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Menu — Rasoi</title>
<link rel="stylesheet" href="style.css">
<style>
.menu-wrap { padding: 56px 48px; max-width: 820px; margin: 0 auto; }
.menu-header { text-align: center; margin-bottom: 48px; }
.menu-header h1 { font-family: 'Cinzel', serif; font-size: 13px; letter-spacing: 0.32em; color: var(--spice); text-transform: uppercase; }
.menu-tabs {
  display: flex; gap: 2px; margin-bottom: 40px;
  background: var(--cream-dark); padding: 4px; border-radius: 2px;
  flex-wrap: wrap;
}
.tab-btn {
  flex: 1; background: transparent; border: none;
  padding: 10px 8px;
  font-family: 'Cinzel', serif; font-size: 10px; letter-spacing: 0.16em;
  text-transform: uppercase; color: var(--muted); cursor: pointer;
  transition: all 0.2s; border-radius: 1px;
}
.tab-btn.active { background: var(--spice); color: #fff; }
.menu-panel { display: none; animation: fadeUp 0.3s ease; }
.menu-panel.active { display: block; }
@keyframes fadeUp { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:none; } }
.menu-section-title {
  font-family: 'Cormorant Garamond', serif;
  font-size: 21px; font-style: italic; color: var(--spice);
  margin: 36px 0 16px; padding-bottom: 8px;
  border-bottom: 1px solid rgba(181,82,43,0.2);
}
.menu-item {
  display: flex; justify-content: space-between; align-items: flex-start;
  padding: 14px 0; border-bottom: 1px solid var(--border);
}
.menu-item:last-child { border-bottom: none; }
.item-name { font-family: 'EB Garamond', serif; font-size: 18px; color: var(--text); }
.item-desc { font-size: 13px; color: var(--muted); margin-top: 3px; font-style: italic; }
.item-price { font-family: 'Cormorant Garamond', serif; font-size: 17px; color: var(--text); white-space: nowrap; margin-left: 24px; padding-top: 2px; }
.menu-note { font-size: 12px; color: var(--muted); font-style: italic; margin-top: 28px; text-align: center; letter-spacing: 0.03em; }
.order-cta { text-align: center; margin-top: 48px; padding: 32px; background: var(--cream-dark); }
.order-cta p { font-size: 16px; color: var(--muted); margin-bottom: 20px; }
.empty-tab { color: var(--muted); font-style: italic; padding: 24px 0; text-align: center; font-size: 15px; }
</style>
</head>
<body>
<nav>
  <a href="index.php" class="nav-logo">Rasoi</a>
  <ul class="nav-links">
    <li><a href="menu.php" class="active">Menu</a></li>
    <li><a href="order.php">Order Online</a></li>
    <li><a href="about.php">About</a></li>
    <li><a href="locations.php">Locations</a></li>
  </ul>
  <div class="nav-right">
    <?php $cartCount = array_sum($_SESSION['cart'] ?? []); ?>
    <a href="cart.php" class="nav-cart-btn">
      🛒 Cart
      <?php if ($cartCount > 0): ?>
        <span class="cart-badge"><?= $cartCount ?></span>
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
    <div class="menu-wrap">
      <div class="menu-header"><h1>Menu</h1></div>

      <?php
      // Build tabs dynamically — only show tab if it has at least one dish
      $active_tabs = [];
      $tab_labels = [
          'drinks'     => 'Drinks',
          'appetizers' => 'Appetizers',
          'maincourse' => 'Main Course',
          'breads'     => 'Breads',
          'dessert'    => 'Dessert',
      ];
      foreach($tab_map as $tab_id => $cats) {
          foreach($cats as $cat) {
              if(isset($dishes_by_cat[$cat]) && count($dishes_by_cat[$cat]) > 0) {
                  $active_tabs[] = $tab_id;
                  break;
              }
          }
      }
      // Add extra categories as their own tab (if any)
      if(!empty($extra_cats)) $active_tabs[] = 'other';

      $first_tab = $active_tabs[0] ?? 'drinks';
      ?>

      <div class="menu-tabs">
        <?php foreach($active_tabs as $tab_id): ?>
          <button class="tab-btn <?= $tab_id === $first_tab ? 'active' : '' ?>"
                  onclick="switchTab('<?= $tab_id ?>',this)">
            <?= $tab_id === 'other' ? 'More' : htmlspecialchars($tab_labels[$tab_id] ?? ucfirst($tab_id)) ?>
          </button>
        <?php endforeach; ?>
      </div>

      <?php foreach($active_tabs as $tab_id):
        $cats_for_tab = $tab_id === 'other' ? array_keys($extra_cats) : ($tab_map[$tab_id] ?? []);
        $has_content = false;
        // Check content
        foreach($cats_for_tab as $cat) {
            if(isset($dishes_by_cat[$cat]) && count($dishes_by_cat[$cat]) > 0) { $has_content = true; break; }
        }
      ?>
      <div id="tab-<?= $tab_id ?>" class="menu-panel <?= $tab_id === $first_tab ? 'active' : '' ?>">
        <?php if($has_content): ?>
          <?php foreach($cats_for_tab as $cat):
            $items = $dishes_by_cat[$cat] ?? [];
            if(empty($items)) continue;
          ?>
            <p class="menu-section-title"><?= htmlspecialchars($cat) ?></p>
            <?php foreach($items as $dish): ?>
              <div class="menu-item">
                <div>
                  <div class="item-name"><?= htmlspecialchars($dish['dname']) ?></div>
                </div>
                <?php if ($dish['dprice'] > 0): ?>
                  <div class="item-price">&#8377;<?= number_format((float)$dish['dprice'], 0) ?></div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="empty-tab">No items available in this category yet.</p>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>

      <div class="order-cta">
        <p>Want to order from home? Browse our full menu with delivery.</p>
        <a href="order.php" class="btn-primary">Order Online →</a>
      </div>
    </div>
  </div>
  <footer>
    <span class="footer-logo">Rasoi</span>
    <span class="footer-copy">© 2026 Rasoi. All rights reserved.</span>
    <a href="locations.php" class="footer-contact">Contact</a>
  </footer>
</div>

<script>
function switchTab(id, btn) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.menu-panel').forEach(p => p.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('tab-' + id).classList.add('active');
}
</script>
</body>
</html>
