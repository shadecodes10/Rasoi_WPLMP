<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>About — Rasoi</title>
<link rel="stylesheet" href="style.css">
<style>
.about-wrap { max-width: 740px; margin: 0 auto; padding: 56px 48px 80px; }
.about-headline { font-family: 'Cormorant Garamond', serif; font-size: clamp(36px, 5vw, 56px); font-weight: 400; line-height: 1.1; color: var(--dark); margin-bottom: 36px; }
.about-headline em { color: var(--spice); font-style: italic; }
.about-body { font-size: 17px; line-height: 1.85; color: var(--muted); }
.about-body p + p { margin-top: 22px; }
.divider { width: 56px; height: 1px; background: var(--spice); opacity: 0.4; margin: 44px 0; }
.values-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 48px; }
.value-card { padding: 28px; background: var(--cream-dark); border-left: 3px solid var(--spice); }
.value-title { font-family: 'Cinzel', serif; font-size: 11px; letter-spacing: 0.2em; text-transform: uppercase; color: var(--spice); margin-bottom: 10px; }
.value-text { font-size: 15px; color: var(--muted); line-height: 1.75; }
.team-section { margin-top: 64px; }
.team-section h2 { font-family: 'Cormorant Garamond', serif; font-size: 32px; font-weight: 400; color: var(--dark); margin-bottom: 24px; }
.timeline { border-left: 1px solid rgba(181,82,43,0.3); padding-left: 28px; }
.timeline-item { position: relative; margin-bottom: 28px; }
.timeline-item::before { content: ''; position: absolute; left: -34px; top: 6px; width: 10px; height: 10px; border-radius: 50%; background: var(--spice); }
.timeline-year { font-family: 'Cinzel', serif; font-size: 10px; letter-spacing: 0.2em; color: var(--spice); margin-bottom: 4px; }
.timeline-text { font-size: 16px; color: var(--muted); line-height: 1.7; }
</style>
</head>
<body>
<nav>
  <a href="index.php" class="nav-logo">Rasoi</a>
  <ul class="nav-links">
    <li><a href="menu.php">Menu</a></li>
    <li><a href="order.php">Order Online</a></li>
    <li><a href="about.php" class="active">About</a></li>
    <li><a href="locations.php">Locations</a></li>
  </ul>
  <div class="nav-right">
    <a href="order.php" class="nav-cart-btn">Cart <span class="cart-badge" id="cart-badge" style="display:none">0</span></a>
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
    <div class="about-wrap">
      <p class="section-eyebrow" style="margin-bottom:16px">Our Story</p>
      <h1 class="about-headline">Rooted in Tradition,<br><em>Alive in Every Bite</em></h1>
      <div class="about-body">
        <p>Rasoi — meaning "kitchen" in Hindi — was born from a simple belief: that Indian cuisine, in all its glorious regional diversity, deserves a single, beautiful table. Fifteen years ago, our founders set out to create a space where the smoky tandoors of Punjab share the same roof as the coconut curries of Kerala and the slow-braised dals of Awadh.</p>
        <p>Every recipe at Rasoi traces its lineage to a family kitchen, a village festival, or a grandmother's handwritten notebook. Our chefs travel across India each year — returning with spice mixes, techniques, and stories that find their way onto your plate.</p>
        <p>We source whole spices from Khari Baoli, saffron from Pampore, and dairy from small cooperatives in Anand. Nothing is rushed. Nothing is compromised.</p>
      </div>

      <div class="divider"></div>

      <div class="values-grid">
        <div class="value-card"><div class="value-title">Tradition</div><div class="value-text">Every dish follows recipes that predate convenience. We cook the slow way — because it's the right way.</div></div>
        <div class="value-card"><div class="value-title">Provenance</div><div class="value-text">We know the name of every farm we source from. Real ingredients, real relationships.</div></div>
        <div class="value-card"><div class="value-title">Hospitality</div><div class="value-text">Atithi Devo Bhava — the guest is God. Every table is treated as an honour.</div></div>
        <div class="value-card"><div class="value-title">Craft</div><div class="value-text">Fifteen years and 50,000 guests later, we still treat every meal as if it's our first.</div></div>
      </div>

      <div class="team-section">
        <h2>Our Journey</h2>
        <div class="timeline">
          <div class="timeline-item"><div class="timeline-year">2011</div><div class="timeline-text">Rasoi opens its first kitchen in Bandra, Mumbai — a 40-cover restaurant with one tandoor and one dream.</div></div>
          <div class="timeline-item"><div class="timeline-year">2014</div><div class="timeline-text">Delhi's Connaught Place location opens, bringing Punjabi and Mughlai traditions to the capital.</div></div>
          <div class="timeline-item"><div class="timeline-year">2018</div><div class="timeline-text">Bangalore and Pune restaurants launch. South Indian and Rajasthani kitchens added to the menu.</div></div>
          <div class="timeline-item"><div class="timeline-year">2022</div><div class="timeline-text">Rasoi launches its delivery service. 50,000 happy guests milestone celebrated.</div></div>
          <div class="timeline-item"><div class="timeline-year">2026</div><div class="timeline-text">150+ dishes across five regional cuisines. Still cooking with the same fire as day one.</div></div>
        </div>
      </div>
    </div>
  </div>

  <footer>
    <span class="footer-logo">Rasoi</span>
    <span class="footer-copy">© 2026 Rasoi. All rights reserved.</span>
    <a href="locations.php" class="footer-contact">Contact</a>
  </footer>
</div>
<script src="cart.js"></script>
</body>
</html>
