<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Locations — Rasoi</title>
<link rel="stylesheet" href="style.css">
<style>
.loc-wrap { padding: 56px 48px 80px; max-width: 860px; margin: 0 auto; }
.loc-intro { margin-bottom: 48px; }
.loc-intro h1 { font-family: 'Cormorant Garamond', serif; font-size: 42px; font-weight: 400; color: var(--dark); margin-bottom: 8px; }
.loc-intro p { font-size: 16px; color: var(--muted); }
.loc-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
.loc-card { background: var(--cream-dark); padding: 32px; border-bottom: 3px solid transparent; transition: border-color 0.2s; }
.loc-card:hover { border-bottom-color: var(--spice); }
.loc-city { font-family: 'Cormorant Garamond', serif; font-size: 28px; font-weight: 400; color: var(--dark); margin-bottom: 4px; }
.loc-area { font-family: 'Cinzel', serif; font-size: 10px; letter-spacing: 0.22em; color: var(--spice); text-transform: uppercase; margin-bottom: 18px; }
.loc-info { font-size: 15px; color: var(--muted); line-height: 1.85; }
.loc-info strong { color: var(--text); font-weight: 400; }
.loc-divider { border: none; border-top: 1px solid rgba(0,0,0,0.08); margin: 16px 0; }
.loc-tag { display: inline-block; background: rgba(181,82,43,0.08); color: var(--spice); font-family: 'Cinzel', serif; font-size: 9px; letter-spacing: 0.15em; text-transform: uppercase; padding: 4px 10px; margin-top: 14px; }
.contact-band { background: var(--dark); padding: 40px 48px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px; }
.contact-band h3 { font-family: 'Cormorant Garamond', serif; font-size: 24px; color: #fff; font-weight: 400; }
.contact-band p { font-size: 15px; color: rgba(255,255,255,0.5); margin-top: 4px; }
.contact-band a { color: var(--gold); text-decoration: none; }
.contact-band a:hover { text-decoration: underline; }
</style>
</head>
<body>
<nav>
  <a href="index.php" class="nav-logo">Rasoi</a>
  <ul class="nav-links">
    <li><a href="menu.php">Menu</a></li>
    <li><a href="order.php">Order Online</a></li>
    <li><a href="about.php">About</a></li>
    <li><a href="locations.php" class="active">Locations</a></li>
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
    <div class="loc-wrap">
      <div class="loc-intro">
        <p class="section-eyebrow" style="margin-bottom:12px">Find Us</p>
        <h1>Our Locations</h1>
        <p>Walk in. We always have a table for you.</p>
      </div>

      <div class="loc-grid">
        <div class="loc-card">
          <div class="loc-city">Mumbai</div>
          <div class="loc-area">Bandra West · Flagship</div>
          <div class="loc-info">
            <strong>14, Hill Road, Bandra West</strong><br>
            Mumbai — 400 050<br>
            <hr class="loc-divider">
            Mon – Sun: 12:00 PM – 11:00 PM<br>
            +91 22 4567 8900<br>
            mumbai@rasoi.in
          </div>
          <div class="loc-tag">Flagship</div>
        </div>

        <div class="loc-card">
          <div class="loc-city">New Delhi</div>
          <div class="loc-area">Connaught Place</div>
          <div class="loc-info">
            <strong>Block A, Connaught Place</strong><br>
            New Delhi — 110 001<br>
            <hr class="loc-divider">
            Mon – Sun: 12:00 PM – 11:30 PM<br>
            +91 11 4567 8900<br>
            delhi@rasoi.in
          </div>
          <div class="loc-tag">Open Late</div>
        </div>

        <div class="loc-card">
          <div class="loc-city">Bangalore</div>
          <div class="loc-area">Indiranagar</div>
          <div class="loc-info">
            <strong>100 Feet Road, Indiranagar</strong><br>
            Bangalore — 560 038<br>
            <hr class="loc-divider">
            Mon – Sun: 12:00 PM – 11:00 PM<br>
            +91 80 4567 8900<br>
            bangalore@rasoi.in
          </div>
        </div>

        <div class="loc-card">
          <div class="loc-city">Pune</div>
          <div class="loc-area">Koregaon Park</div>
          <div class="loc-info">
            <strong>Lane 5, Koregaon Park</strong><br>
            Pune — 411 001<br>
            <hr class="loc-divider">
            Mon – Sun: 12:00 PM – 10:30 PM<br>
            +91 20 4567 8900<br>
            pune@rasoi.in
          </div>
        </div>
      </div>
    </div>

    <div class="contact-band">
      <div>
        <h3>Group Bookings &amp; Private Dining</h3>
        <p>For parties of 8 or more, or exclusive dining experiences — <a href="mailto:hello@rasoi.in">hello@rasoi.in</a></p>
      </div>
      <a href="reservation.php" class="btn-primary">Reserve a Table</a>
    </div>
  </div>

  <footer>
    <span class="footer-logo">Rasoi</span>
    <span class="footer-copy">© 2026 Rasoi. All rights reserved.</span>
    <span class="footer-contact">Contact</span>
  </footer>
</div>
<script src="cart.js"></script>
</body>
</html>
