<?php session_start(); ?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rasoi — Indian Kitchen & Flavours</title>
<link rel="stylesheet" href="style.css">
<style>
.hero {
  min-height: calc(100vh - 64px - 108px);
  display: flex; align-items: center;
  padding: 80px 48px 60px; position: relative; overflow: hidden;
}
.hero::before {
  content: '';
  position: absolute; top: -20px; right: -60px;
  width: 600px; height: 600px;
  background: radial-gradient(circle, rgba(200,169,110,0.07) 0%, transparent 70%);
  border-radius: 50%; pointer-events: none;
}
.hero-content { max-width: 520px; animation: fadeUp 0.7s ease both; }
@keyframes fadeUp { from { opacity:0; transform: translateY(22px); } to { opacity:1; transform: none; } }
.hero-title {
  font-family: 'Cormorant Garamond', serif;
  font-size: clamp(56px, 6.5vw, 78px);
  font-weight: 400; line-height: 1.04; color: var(--dark); margin-bottom: 8px;
}
.hero-title em { color: var(--spice); font-style: italic; display: block; }
.hero-desc { font-size: 17px; line-height: 1.78; color: var(--muted); margin-top: 28px; max-width: 400px; }
.hero-cta { display: flex; gap: 16px; margin-top: 44px; flex-wrap: wrap; }
.stats-bar {
  background: var(--cream-dark);
  display: grid; grid-template-columns: repeat(4, 1fr); padding: 28px 48px;
}
.stat { border-left: 1px solid rgba(0,0,0,0.1); padding: 0 32px; }
.stat:first-child { border-left: none; padding-left: 0; }
.stat-num {
  font-family: 'Cormorant Garamond', serif;
  font-size: 24px; font-weight: 500; color: var(--spice); display: block; margin-bottom: 3px;
}
.stat-label { font-size: 14px; color: var(--text); letter-spacing: 0.02em; }
.features { display: grid; grid-template-columns: repeat(3, 1fr); padding: 56px 48px; gap: 48px; }
.feature-icon { font-size: 28px; margin-bottom: 14px; }
.feature-title { font-family: 'Cormorant Garamond', serif; font-size: 22px; color: var(--dark); margin-bottom: 8px; }
.feature-desc { font-size: 15px; color: var(--muted); line-height: 1.75; }
.cuisines-section { padding: 0 48px 72px; }
.cuisines-section h2 { font-family: 'Cormorant Garamond', serif; font-size: 38px; font-weight: 400; color: var(--dark); margin-bottom: 6px; }
.cuisines-section p { font-size: 16px; color: var(--muted); margin-bottom: 36px; }
.cuisines-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; }
.cuisine-card {
  background: var(--cream-dark); padding: 24px 20px;
  border-bottom: 3px solid transparent; transition: border-color 0.2s, transform 0.2s;
  cursor: pointer; text-decoration: none; display: block;
}
.cuisine-card:hover { border-bottom-color: var(--spice); transform: translateY(-3px); }
.cuisine-card-region { font-family: 'Cinzel', serif; font-size: 9px; letter-spacing: 0.22em; color: var(--spice); text-transform: uppercase; margin-bottom: 8px; }
.cuisine-card-name { font-family: 'Cormorant Garamond', serif; font-size: 19px; color: var(--dark); }
.cuisine-card-desc { font-size: 13px; color: var(--muted); margin-top: 6px; line-height: 1.6; }
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
    <a href="order.html" class="nav-cart-btn">Cart <span class="cart-badge" id="cart-badge" style="display:none">0</span></a>
    <?php if (isset($_SESSION['username'])): ?>
      <div class="nav-auth-user">
        <span class="nav-welcome">Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
        <a href="logout.php" class="nav-signout">Sign Out</a>
      </div>
    <?php else: ?>
      <a href="signin.php" class="nav-auth">Sign-in / Sign-up</a>
    <?php endif; ?>
  </div>
</nav>

<div class="page-wrap">
  <div class="page-content">
    <section class="hero">
      <div class="hero-content">
        <p class="section-eyebrow" style="margin-bottom:18px">A World of Flavours</p>
        <h1 class="hero-title">Every Cuisine,<em>One Table</em></h1>
        <p class="hero-desc">From the spice trails of Rajasthan to the coastal kitchens of Kerala — Rasoi brings India's finest regional cuisines together, crafted with tradition and served with warmth.</p>
        <div class="hero-cta">
          <?php if (isset($_SESSION['user_id'])): ?>
            <a href="reservation.php" class="btn-primary">Reserve a Table</a>
          <?php else: ?>
            <a href="signin.php" class="btn-primary" onclick="alert('Please sign in to reserve a table.')" style="cursor:pointer">Reserve a Table</a>
          <?php endif; ?>
          <a href="menu.html" class="btn-outline">Explore Menu</a>
        </div>
      </div>
    </section>

    <div class="stats-bar">
      <div class="stat"><span class="stat-num">5</span><span class="stat-label">Regional Cuisines</span></div>
      <div class="stat"><span class="stat-num">150+</span><span class="stat-label">Dishes On Menu</span></div>
      <div class="stat"><span class="stat-num">15</span><span class="stat-label">Years Of Craft</span></div>
      <div class="stat"><span class="stat-num">50k+</span><span class="stat-label">Happy Guests</span></div>
    </div>

    <div class="features">
      <div><div class="feature-icon">✦</div><div class="feature-title">Authentic Recipes</div><div class="feature-desc">Every dish traces its lineage to a family kitchen or a village festival. No shortcuts, ever.</div></div>
      <div><div class="feature-icon">◈</div><div class="feature-title">Sourced with Care</div><div class="feature-desc">Whole spices from Khari Baoli, saffron from Pampore, dairy from Anand cooperatives.</div></div>
      <div><div class="feature-icon">❋</div><div class="feature-title">Five Regional Kitchens</div><div class="feature-desc">Punjabi tandoor. Kerala coconut. Awadhi dum. Rajasthani tawa. One unforgettable roof.</div></div>
    </div>

    <div class="cuisines-section">
      <p class="section-eyebrow">Regional Heritage</p>
      <h2>India on Your Plate</h2>
      <p>Five distinct culinary traditions, each authentic to its origin.</p>
      <div class="cuisines-grid">
        <a href="menu.html" class="cuisine-card"><div class="cuisine-card-region">North India</div><div class="cuisine-card-name">Punjabi & Mughlai</div><div class="cuisine-card-desc">Tandoor, dal makhani, biryani, butter naan</div></a>
        <a href="menu.html" class="cuisine-card"><div class="cuisine-card-region">South India</div><div class="cuisine-card-name">Kerala & Chettinad</div><div class="cuisine-card-desc">Coconut curries, sambar, rasam, appam</div></a>
        <a href="menu.html" class="cuisine-card"><div class="cuisine-card-region">West India</div><div class="cuisine-card-name">Rajasthani & Gujarati</div><div class="cuisine-card-desc">Dal baati, dhokla, tawa sabzi, churma</div></a>
        <a href="menu.html" class="cuisine-card"><div class="cuisine-card-region">East India</div><div class="cuisine-card-name">Bengali & Odia</div><div class="cuisine-card-desc">Mustard gravies, mishti doi, kosha mangsho</div></a>
        <a href="menu.html" class="cuisine-card"><div class="cuisine-card-region">Central India</div><div class="cuisine-card-name">Awadhi & Bundelkhandi</div><div class="cuisine-card-desc">Slow dum, galouti, korma, sheermal</div></a>
      </div>
    </div>
  </div>

  <footer>
    <span class="footer-logo">Rasoi</span>
    <span class="footer-copy">© 2026 Rasoi. All rights reserved.</span>
    <a href="locations.html" class="footer-contact">Contact</a>
  </footer>
</div>
<script src="cart.js"></script>
</body>
</html>

