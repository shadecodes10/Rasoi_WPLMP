<?php
$conn = new mysqli("localhost", "root", "", "restaurant_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$page = $_GET['page'] ?? 'home';

if ($page === 'bookings' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $type   = $_POST['type']   ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    if ($action === 'delete' && $id > 0) {
        if ($type === 'order') {
            $conn->query("DELETE FROM orders WHERE oid = $id");
        } else {
            $conn->query("DELETE FROM reservation WHERE rid = $id");
        }
    }

    if ($action === 'edit_reservation' && $id > 0) {
        $rdate = $conn->real_escape_string($_POST['rdate'] ?? '');
        $rtime = $conn->real_escape_string($_POST['rtime'] ?? '');
        $count = (int)($_POST['count'] ?? 1);
        $bid   = (int)($_POST['bid']   ?? 0);
        $conn->query("UPDATE reservation SET rdate='$rdate', rtime='$rtime', count=$count, bid=$bid WHERE rid=$id");
    }

    if ($action === 'edit_order' && $id > 0) {
        $oamount = (float)($_POST['oamount'] ?? 0);
        $bid     = (int)($_POST['bid'] ?? 0);
        $conn->query("UPDATE orders SET oamount=$oamount, bid=$bid WHERE oid=$id");
    }

    if ($action === 'mark_delivered' && $id > 0) {
        $conn->query("UPDATE orders SET ostatus='delivered' WHERE oid=$id");
    }

    $tab = ($type === 'order') ? 'orders' : 'reservations';
    header("Location: portal2.php?page=bookings&tab=$tab");
    exit;
}

if ($page === 'dishes' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $dish_action = $_POST['dish_action'] ?? '';
    if ($dish_action === 'update_price') {
        $dname = $conn->real_escape_string($_POST['dname'] ?? '');
        $price = (float)($_POST['dprice'] ?? 0);
        if ($dname && $price > 0) {
            $conn->query("UPDATE dishes SET dprice=$price WHERE dname='$dname'");
        }
    }
    if ($dish_action === 'add_dish') {
        $new_name     = $conn->real_escape_string(trim($_POST['new_dname']     ?? ''));
        $new_price    = (float)($_POST['new_dprice']    ?? 0);
        $raw_cat      = trim($_POST['new_dcategory'] ?? '');
        // If user picked "+ Add new category" use the typed custom value instead
        if ($raw_cat === '__new__') $raw_cat = trim($_POST['new_dcategory_custom'] ?? '');
        $new_category = $conn->real_escape_string($raw_cat);
        $new_type     = $conn->real_escape_string(trim($_POST['new_dtype']     ?? ''));
        if ($new_name && $new_price > 0 && $new_category) {
            $conn->query("INSERT INTO dishes (dname, dprice, dcategory, dtype) VALUES ('$new_name', $new_price, '$new_category', '$new_type')");
        }
    }
    if ($dish_action === 'delete_dish') {
        $del_name = $conn->real_escape_string($_POST['del_dname'] ?? '');
        if ($del_name) {
            $conn->query("DELETE FROM dishes WHERE dname='$del_name'");
        }
    }
    header("Location: portal2.php?page=dishes");
    exit;
}

if (isset($_GET['toggle_id'])) {
    $toggle_id = (int)$_GET['toggle_id'];
    if ($toggle_id > 0) {
        $cur = $conn->query("SELECT status FROM reservation WHERE rid = $toggle_id LIMIT 1");
        if ($cur && $cur->num_rows > 0) {
            $row_status = $cur->fetch_assoc();
            $new_status = ($row_status['status'] === 'booked') ? 'free' : 'booked';
            $conn->query("UPDATE reservation SET status = '$new_status' WHERE rid = $toggle_id");
        }
    }
    header("Location: portal2.php?page=bookings&tab=reservations");
    exit;
}

$bookings_tab = $_GET['tab'] ?? 'reservations';
$edit_id      = (int)($_GET['edit_id']   ?? 0);
$edit_type    = $_GET['edit_type'] ?? '';

// Distinct dish categories for dropdown
$cat_res = $conn->query("SELECT DISTINCT dcategory FROM dishes ORDER BY dcategory");
$dish_categories = [];
if ($cat_res) while ($cr = $cat_res->fetch_row()) $dish_categories[] = $cr[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Rasoi — Admin Portal</title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Jost:wght@300;400;500&display=swap" rel="stylesheet"/>
  <style>
    :root {
      --cream: #f0ebe0;
      --brown-dark: #2c1f14;
      --rust: #8b3a1e;
      --rust-light: #b05030;
      --card-bg: #ddd8cc;
      --card-hover: #c8c2b4;
      --text-main: #2c1f14;
      --text-muted: #7a6a5a;
      --table-bg: #faf8f4;
      --border: #cec8bc;
    }
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
    body { background: var(--cream); font-family: 'Jost', sans-serif; color: var(--text-main); min-height: 100vh; display: flex; flex-direction: column; }
    a { text-decoration: none; color: inherit; }
    header { text-align: center; padding: 48px 20px 12px; }
    header h1 { font-family: 'Cormorant Garamond', serif; font-size: 2.6rem; font-weight: 600; color: var(--rust); letter-spacing: 0.04em; }
    header p { font-size: 0.95rem; font-weight: 300; letter-spacing: 0.18em; text-transform: uppercase; color: var(--text-muted); margin-top: 6px; }
    .divider { width: 60px; height: 1px; background: var(--rust-light); margin: 20px auto 0; opacity: 0.5; }
    main { flex: 1; padding: 60px 40px; max-width: 1080px; margin: 0 auto; width: 100%; }
    .card-grid { display: flex; gap: 48px; flex-wrap: wrap; justify-content: center; margin-top: 20px; }
    .admin-card { background: var(--card-bg); width: 200px; height: 200px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 14px; border-radius: 4px; transition: background 0.2s, transform 0.2s, box-shadow 0.2s; }
    .admin-card:hover { background: var(--card-hover); transform: translateY(-3px); box-shadow: 0 8px 24px rgba(44,31,20,0.12); }
    .card-icon { font-size: 1.8rem; opacity: 0.65; }
    .card-label { font-size: 0.8rem; letter-spacing: 0.14em; text-transform: uppercase; font-weight: 400; color: var(--text-muted); }
    .card-btn { display: inline-block; padding: 9px 22px; background: var(--rust); color: #fff; font-family: 'Jost', sans-serif; font-size: 0.75rem; font-weight: 500; letter-spacing: 0.16em; text-transform: uppercase; border-radius: 2px; cursor: pointer; transition: background 0.2s, transform 0.15s; }
    .card-btn:hover { background: var(--rust-light); transform: scale(1.03); }
    .back-link { display: inline-flex; align-items: center; gap: 6px; font-size: 0.8rem; letter-spacing: 0.1em; text-transform: uppercase; color: var(--text-muted); margin-bottom: 28px; transition: color 0.2s; }
    .back-link:hover { color: var(--rust); }
    .section-title { font-family: 'Cormorant Garamond', serif; font-size: 1.8rem; font-weight: 600; color: var(--rust); margin-bottom: 6px; }
    .section-divider { width: 40px; height: 1px; background: var(--rust-light); opacity: 0.5; margin-bottom: 28px; }
    table { width: 100%; border-collapse: collapse; background: var(--table-bg); border-radius: 4px; overflow: hidden; box-shadow: 0 2px 12px rgba(44,31,20,0.07); }
    thead tr { background: var(--brown-dark); }
    thead th { padding: 14px 16px; font-size: 0.72rem; font-weight: 500; letter-spacing: 0.18em; text-transform: uppercase; color: rgba(240,235,224,0.85); text-align: left; }
    tbody tr { border-bottom: 1px solid var(--border); transition: background 0.15s; }
    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: #ede8df; }
    tbody td { padding: 13px 16px; font-size: 0.88rem; font-weight: 300; }
    tbody td.price { font-weight: 500; color: var(--rust); }
    .empty-row td { text-align: center; color: var(--text-muted); font-style: italic; padding: 30px; }
    footer { background: var(--brown-dark); padding: 20px 36px; display: flex; align-items: center; justify-content: space-between; margin-top: auto; }
    footer .logo { font-family: 'Cormorant Garamond', serif; font-size: 1.25rem; font-style: italic; color: var(--cream); letter-spacing: 0.06em; }
    footer .copyright { font-size: 0.72rem; font-weight: 300; color: rgba(240,235,224,0.45); }
    .tab-bar { display: flex; gap: 0; border-bottom: 2px solid var(--border); margin-bottom: 32px; }
    .tab-btn { padding: 10px 28px; font-family: 'Jost', sans-serif; font-size: 0.78rem; letter-spacing: 0.14em; text-transform: uppercase; color: var(--text-muted); text-decoration: none; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: color 0.2s, border-color 0.2s; }
    .tab-btn:hover { color: var(--rust); }
    .tab-btn.active { color: var(--rust); border-bottom-color: var(--rust); font-weight: 500; }
    .badge { display: inline-block; padding: 3px 10px; border-radius: 2px; font-size: 0.72rem; font-weight: 500; letter-spacing: 0.1em; text-transform: uppercase; }
    .badge-confirmed  { background: #e8f4ea; color: #2a6b35; }
    .badge-completed  { background: #ede8df; color: var(--text-muted); }
    .badge-preparing  { background: #fff3e0; color: #a05a00; }
    .badge-delivered  { background: #e8f4ea; color: #2a6b35; }
    .badge-booked     { background: #e8eef8; color: #2a4a8b; }
    .badge-free       { background: #ede8df; color: var(--text-muted); }
    .act-btn { display: inline-block; padding: 5px 14px; font-family: 'Jost', sans-serif; font-size: 0.72rem; font-weight: 500; letter-spacing: 0.1em; text-transform: uppercase; border-radius: 2px; cursor: pointer; border: none; transition: background 0.18s; }
    .act-edit   { background: var(--card-bg); color: var(--text-main); text-decoration: none; }
    .act-edit:hover { background: var(--card-hover); }
    .act-delete { background: #fdecea; color: #b52a1e; }
    .act-delete:hover { background: #f5c8c4; }
    .act-done   { background: #e8f4ea; color: #2a6b35; }
    .act-done:hover { background: #c8e6cb; }
    .edit-row td { background: #f5f0e8; padding: 20px 16px !important; }
    .edit-form-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px 16px; margin-bottom: 14px; }
    .edit-form-grid label { display: block; font-size: 0.7rem; letter-spacing: 0.12em; text-transform: uppercase; color: var(--text-muted); margin-bottom: 4px; }
    .edit-form-grid input, .edit-form-grid select { width: 100%; padding: 7px 10px; font-family: 'Jost', sans-serif; font-size: 0.85rem; border: 1px solid var(--border); border-radius: 2px; background: #fff; color: var(--text-main); }
    .edit-actions { display: flex; gap: 10px; margin-top: 4px; }
    .btn-save { padding: 7px 22px; background: var(--rust); color: #fff; font-family: 'Jost', sans-serif; font-size: 0.75rem; font-weight: 500; letter-spacing: 0.14em; text-transform: uppercase; border: none; border-radius: 2px; cursor: pointer; transition: background 0.2s; }
    .btn-save:hover { background: var(--rust-light); }
    .btn-cancel-edit { padding: 7px 18px; background: var(--card-bg); color: var(--text-muted); font-family: 'Jost', sans-serif; font-size: 0.75rem; letter-spacing: 0.14em; text-transform: uppercase; border: none; border-radius: 2px; cursor: pointer; text-decoration: none; display: inline-block; }
    .btn-cancel-edit:hover { background: var(--card-hover); }

    /* Revenue analytics */
    .rev-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 36px; }
    .rev-card { background: var(--table-bg); border-radius: 4px; padding: 24px 20px; box-shadow: 0 2px 12px rgba(44,31,20,0.07); border-top: 3px solid var(--rust); }
    .rev-card-label { font-size: 0.7rem; letter-spacing: 0.18em; text-transform: uppercase; color: var(--text-muted); margin-bottom: 8px; }
    .rev-card-value { font-family: 'Cormorant Garamond', serif; font-size: 2rem; font-weight: 600; color: var(--rust); }
    .rev-card-sub { font-size: 0.78rem; color: var(--text-muted); margin-top: 4px; }
    .rev-section-title { font-family: 'Cormorant Garamond', serif; font-size: 1.3rem; color: var(--rust); margin: 28px 0 14px; }

    /* Price edit inline */
    .price-edit-form { display: inline-flex; align-items: center; gap: 6px; }
    .price-edit-form input { width: 90px; padding: 4px 8px; font-family: 'Jost', sans-serif; font-size: 0.85rem; border: 1px solid var(--border); border-radius: 2px; background: #fff; }
    .price-edit-form button { padding: 4px 12px; background: var(--rust); color: #fff; border: none; border-radius: 2px; font-size: 0.72rem; font-family: 'Jost', sans-serif; cursor: pointer; }
    .price-edit-form button:hover { background: var(--rust-light); }
    .price-display { cursor: pointer; border-bottom: 1px dashed var(--text-muted); font-weight: 500; color: var(--rust); }
    .price-display:hover { border-bottom-color: var(--rust); }

    /* Add dish form */
    .add-dish-box { background: var(--table-bg); border: 1px solid var(--border); border-radius: 4px; padding: 24px 28px; margin-bottom: 28px; box-shadow: 0 2px 12px rgba(44,31,20,0.05); }
    .add-dish-title { font-family: 'Cormorant Garamond', serif; font-size: 1.2rem; font-weight: 600; color: var(--rust); margin-bottom: 16px; }
    .add-dish-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px 16px; }
    .add-dish-grid label { display: block; font-size: 0.7rem; letter-spacing: 0.12em; text-transform: uppercase; color: var(--text-muted); margin-bottom: 4px; }
    .add-dish-grid input, .add-dish-grid select { width: 100%; padding: 7px 10px; font-family: 'Jost', sans-serif; font-size: 0.85rem; border: 1px solid var(--border); border-radius: 2px; background: #fff; color: var(--text-main); }

    /* Feedbacks */
    .feedback-summary { background: #fff9f3; border-left: 3px solid var(--rust); padding: 12px 18px; border-radius: 2px; margin-bottom: 20px; font-size: 0.9rem; color: var(--text-main); }
    .star-rating { color: #c87941; font-size: 1rem; letter-spacing: 2px; }
  </style>
</head>
<body>

  <header>
    <h1>Rasoi</h1>
    <p>Admin Portal</p>
    <div class="divider"></div>
  </header>

  <main>

  <?php if ($page === 'home'): ?>
    <div class="card-grid">
      <div class="admin-card">
        <span class="card-icon">&#128101;</span>
        <span class="card-label">Users</span>
        <a href="?page=users" class="card-btn">View All Users</a>
      </div>
      <div class="admin-card">
        <span class="card-icon">&#127373;</span>
        <span class="card-label">Dishes</span>
        <a href="?page=dishes" class="card-btn">Manage Dishes</a>
      </div>
      <div class="admin-card">
        <span class="card-icon">&#128203;</span>
        <span class="card-label">Orders &amp; Reservations</span>
        <a href="?page=bookings" class="card-btn">Manage Bookings</a>
      </div>
      <div class="admin-card">
        <span class="card-icon">&#128200;</span>
        <span class="card-label">Revenue</span>
        <a href="?page=revenue" class="card-btn">View Analytics</a>
      </div>
      <div class="admin-card">
        <span class="card-icon">&#11088;</span>
        <span class="card-label">Feedbacks</span>
        <a href="?page=feedbacks" class="card-btn">View Feedbacks</a>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($page === 'users'): ?>
    <a href="portal2.php" class="back-link">&larr; Back</a>
    <h2 class="section-title">Users</h2>
    <div class="section-divider"></div>
    <table>
      <thead>
        <tr>
          <th>First Name</th><th>Last Name</th><th>Email</th><th>Phone</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $result = $conn->query("SELECT firstname, lastname, email, phone_no FROM user");
        if ($result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {
            echo "<tr>
              <td>" . htmlspecialchars($row['firstname']) . "</td>
              <td>" . htmlspecialchars($row['lastname']) . "</td>
              <td>" . htmlspecialchars($row['email']) . "</td>
              <td>" . htmlspecialchars($row['phone_no'] ?? '—') . "</td>
            </tr>";
          }
        } else {
          echo "<tr class='empty-row'><td colspan='4'>No users found.</td></tr>";
        }
        ?>
      </tbody>
    </table>
  <?php endif; ?>

  <?php if ($page === 'dishes'): ?>
    <a href="portal2.php" class="back-link">&larr; Back</a>
    <h2 class="section-title">Manage Dishes</h2>
    <div class="section-divider"></div>

    <!-- Add New Dish Form -->
    <div class="add-dish-box">
      <div class="add-dish-title">&#43; Add New Dish</div>
      <form method="POST" action="?page=dishes" class="add-dish-form">
        <input type="hidden" name="dish_action" value="add_dish">
        <div class="add-dish-grid">
          <div>
            <label>Dish Name *</label>
            <input type="text" name="new_dname" placeholder="e.g. Butter Chicken" required>
          </div>
          <div>
            <label>Price (&#8377;) *</label>
            <input type="number" name="new_dprice" placeholder="e.g. 349" min="1" step="1" required>
          </div>
          <div>
            <label>Category *</label>
            <select name="new_dcategory" id="cat_select" onchange="toggleNewCat(this)" required>
              <option value="" disabled selected>— select category —</option>
              <?php foreach($dish_categories as $dc): ?>
              <option value="<?= htmlspecialchars($dc, ENT_QUOTES) ?>"><?= htmlspecialchars($dc) ?></option>
              <?php endforeach; ?>
              <option value="__new__">＋ Add new category…</option>
            </select>
            <input type="text" id="cat_custom" name="new_dcategory_custom"
                   placeholder="Type new category name" style="display:none;margin-top:6px;width:100%;padding:7px 10px;font-family:'Jost',sans-serif;font-size:0.85rem;border:1px solid var(--border);border-radius:2px;">
          </div>
          <div>
            <label>Type</label>
            <select name="new_dtype">
              <option value="Veg" selected>Veg</option>
              <option value="Jain">Jain</option>
            </select>
          </div>
        </div>
        <button type="submit" class="btn-save" style="margin-top:10px;">Add Dish</button>
      </form>
    </div>

    <table>
      <thead>
        <tr>
          <th>Name</th><th>Price</th><th>Category</th><th>Type</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $result = $conn->query("SELECT dname, dprice, dcategory, dtype FROM dishes ORDER BY dcategory, dname");
        if ($result && $result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {
            echo "<tr>
              <td>" . htmlspecialchars($row['dname']) . "</td>
              <td>
                <form method='POST' action='?page=dishes' class='price-edit-form'>
                  <input type='hidden' name='dish_action' value='update_price'>
                  <input type='hidden' name='dname' value='" . htmlspecialchars($row['dname'], ENT_QUOTES) . "'>
                  &#8377;<input type='number' name='dprice' value='" . number_format((float)$row['dprice'], 0, '.', '') . "' min='1' step='1'>
                  <button type='submit'>Save</button>
                </form>
              </td>
              <td>" . htmlspecialchars($row['dcategory']) . "</td>
              <td>" . htmlspecialchars($row['dtype'] ?? '—') . "</td>
              <td>
                <form method='POST' action='?page=dishes' onsubmit=\"return confirm('Delete &quot;" . htmlspecialchars($row['dname'], ENT_QUOTES) . "&quot;?')\" style='display:inline'>
                  <input type='hidden' name='dish_action' value='delete_dish'>
                  <input type='hidden' name='del_dname' value='" . htmlspecialchars($row['dname'], ENT_QUOTES) . "'>
                  <button type='submit' class='act-btn act-delete'>Delete</button>
                </form>
              </td>
            </tr>";
          }
        } else {
          echo "<tr class='empty-row'><td colspan='5'>No dishes found.</td></tr>";
        }
        ?>
      </tbody>
    </table>
  <?php endif; ?>

  <?php if ($page === 'revenue'):
    $total_revenue = 0;
    $order_count   = 0;
    $paid_total    = 0;
    $avg_order     = 0;

    $rev = $conn->query("SELECT COUNT(oid) as cnt, SUM(oamount) as total FROM orders");
    if ($rev && $r = $rev->fetch_assoc()) {
      $order_count   = (int)$r['cnt'];
      $total_revenue = (float)$r['total'];
      $avg_order     = $order_count > 0 ? $total_revenue / $order_count : 0;
    }

    $paidq = $conn->query("SELECT SUM(amount) as paid FROM payments");
    if ($paidq && $p = $paidq->fetch_assoc()) {
      $paid_total = (float)$p['paid'];
    }

    $branch_rev = $conn->query("
      SELECT b.bname, COUNT(o.oid) as cnt, SUM(o.oamount) as total
      FROM orders o LEFT JOIN branches b ON o.bid = b.bid
      GROUP BY o.bid, b.bname ORDER BY total DESC
    ");

    $method_rev = $conn->query("SELECT method, COUNT(*) as cnt, SUM(amount) as total FROM payments GROUP BY method ORDER BY total DESC");
  ?>
    <a href="portal2.php" class="back-link">&larr; Back</a>
    <h2 class="section-title">Revenue Analytics</h2>
    <div class="section-divider"></div>

    <div class="rev-grid">
      <div class="rev-card">
        <div class="rev-card-label">Total Revenue (Orders)</div>
        <div class="rev-card-value">&#8377;<?= number_format($total_revenue, 0) ?></div>
        <div class="rev-card-sub"><?= $order_count ?> orders placed</div>
      </div>
      <div class="rev-card">
        <div class="rev-card-label">Payments Collected</div>
        <div class="rev-card-value">&#8377;<?= number_format($paid_total, 0) ?></div>
        <div class="rev-card-sub">from payments table</div>
      </div>
      <div class="rev-card">
        <div class="rev-card-label">Average Order Value</div>
        <div class="rev-card-value">&#8377;<?= number_format($avg_order, 0) ?></div>
        <div class="rev-card-sub">per order</div>
      </div>
      <div class="rev-card">
        <div class="rev-card-label">Pending (Unpaid)</div>
        <div class="rev-card-value">&#8377;<?= number_format(max(0, $total_revenue - $paid_total), 0) ?></div>
        <div class="rev-card-sub">orders vs payments gap</div>
      </div>
    </div>

    <div class="rev-section-title">Revenue by Branch</div>
    <table>
      <thead>
        <tr><th>Branch</th><th>Orders</th><th>Total Revenue</th></tr>
      </thead>
      <tbody>
        <?php if ($branch_rev && $branch_rev->num_rows > 0):
          while ($br = $branch_rev->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($br['bname'] ?? 'Unknown') ?></td>
            <td><?= (int)$br['cnt'] ?></td>
            <td class="price">&#8377;<?= number_format((float)$br['total'], 0) ?></td>
          </tr>
        <?php endwhile; else: ?>
          <tr class="empty-row"><td colspan="3">No data yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <?php if ($method_rev && $method_rev->num_rows > 0): ?>
    <div class="rev-section-title">Payments by Method</div>
    <table>
      <thead>
        <tr><th>Method</th><th>Transactions</th><th>Total Collected</th></tr>
      </thead>
      <tbody>
        <?php while ($pm = $method_rev->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($pm['method'] ?? '—') ?></td>
          <td><?= (int)$pm['cnt'] ?></td>
          <td class="price">&#8377;<?= number_format((float)$pm['total'], 0) ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <?php endif; ?>

  <?php endif; ?>

  <?php if ($page === 'feedbacks'):
    $fb_res = $conn->query("
      SELECT rv.rid, rv.rating, rv.comment,
             u.firstname, u.lastname, u.email,
             b.bname, o.oid, o.oamount
      FROM reviews rv
      LEFT JOIN user u ON rv.uid = u.uid
      LEFT JOIN branches b ON rv.bid = b.bid
      LEFT JOIN orders o ON rv.oid = o.oid
      ORDER BY rv.rid DESC
    ");
  ?>
    <a href="portal2.php" class="back-link">&larr; Back</a>
    <h2 class="section-title">Customer Feedbacks</h2>
    <div class="section-divider"></div>
    <?php
      $fb_count = $fb_res ? $fb_res->num_rows : 0;
      $fb_all = [];
      if ($fb_res && $fb_count > 0) while($row=$fb_res->fetch_assoc()) $fb_all[]=$row;
    ?>
    <?php if ($fb_all): ?>
    <div class="feedback-summary">
      <?php
        $total_rating = array_sum(array_column($fb_all,'rating'));
        $avg_rating = $total_rating / count($fb_all);
        $stars = str_repeat('&#9733;', round($avg_rating)) . str_repeat('&#9734;', 5-round($avg_rating));
      ?>
      <span>&#11088; Average Rating: <strong><?= number_format($avg_rating,1) ?>/5</strong> &nbsp;<?= $stars ?>&nbsp; (<?= $fb_count ?> reviews)</span>
    </div>
    <table>
      <thead>
        <tr>
          <th>#</th><th>Guest</th><th>Email</th><th>Branch</th><th>Order</th><th>Rating</th><th>Comment</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($fb_all as $fb): ?>
        <tr>
          <td><?= (int)$fb['rid'] ?></td>
          <td><?= htmlspecialchars(trim($fb['firstname'].' '.$fb['lastname'])) ?></td>
          <td><?= htmlspecialchars($fb['email'] ?? '—') ?></td>
          <td><?= htmlspecialchars($fb['bname'] ?? '—') ?></td>
          <td><?= $fb['oid'] ? '#'.(int)$fb['oid'].' &mdash; &#8377;'.number_format((float)$fb['oamount'],0) : '—' ?></td>
          <td>
            <span class="star-rating">
              <?php for($s=1;$s<=5;$s++) echo $s<=(int)$fb['rating'] ? '&#9733;' : '&#9734;'; ?>
            </span>
          </td>
          <td><?= htmlspecialchars($fb['comment'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
    <p style="color:var(--text-muted);font-style:italic;padding:24px 0;">No customer feedbacks yet.</p>
    <?php endif; ?>
  <?php endif; ?>

  <?php if ($page === 'bookings'):
    $branches_res = $conn->query("SELECT bid, bname FROM BRANCHES ORDER BY bname");
    $branches = [];
    if ($branches_res) while ($b = $branches_res->fetch_assoc()) $branches[] = $b;
  ?>
    <a href="portal2.php" class="back-link">&larr; Back</a>
    <h2 class="section-title">Orders &amp; Reservations</h2>
    <div class="section-divider"></div>

    <div class="tab-bar">
      <a href="?page=bookings&tab=reservations" class="tab-btn <?= $bookings_tab === 'reservations' ? 'active' : '' ?>">Reservations</a>
      <a href="?page=bookings&tab=orders"       class="tab-btn <?= $bookings_tab === 'orders'       ? 'active' : '' ?>">Orders</a>
    </div>

    <?php if ($bookings_tab === 'reservations'):
      $res = $conn->query("
        SELECT r.rid, r.rdate, r.rtime, r.count, r.bid, r.status,
               u.firstname, u.lastname, u.email, u.phone_no, b.bname
        FROM reservation r
        LEFT JOIN user u ON r.uid = u.uid
        LEFT JOIN branches b ON r.bid = b.bid
        ORDER BY r.rdate DESC, r.rtime DESC
      ");
    ?>
    <table>
      <thead>
        <tr>
          <th>RID</th><th>Guest</th><th>Email</th><th>Phone</th>
          <th>Date</th><th>Time</th><th>Guests</th><th>Branch</th>
          <th>Status</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($res && $res->num_rows > 0):
        while ($r = $res->fetch_assoc()): ?>
          <?php if ($edit_id === (int)$r['rid'] && $edit_type === 'reservation'): ?>
          <tr class="edit-row"><td colspan="10">
            <form method="POST" action="?page=bookings&tab=reservations">
              <input type="hidden" name="action" value="edit_reservation">
              <input type="hidden" name="type"   value="reservation">
              <input type="hidden" name="id"     value="<?= (int)$r['rid'] ?>">
              <div class="edit-form-grid">
                <div><label>Date</label><input type="date" name="rdate" value="<?= htmlspecialchars($r['rdate']) ?>" required></div>
                <div><label>Time</label><input type="time" name="rtime" value="<?= htmlspecialchars($r['rtime']) ?>"></div>
                <div><label>Guests</label><input type="number" name="count" min="1" max="20" value="<?= (int)$r['count'] ?>"></div>
                <div>
                  <label>Branch</label>
                  <select name="bid">
                    <?php foreach ($branches as $b): ?>
                      <option value="<?= (int)$b['bid'] ?>" <?= (int)$r['bid'] === (int)$b['bid'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['bname']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="edit-actions">
                <button type="submit" class="btn-save">Save</button>
                <a href="?page=bookings&tab=reservations" class="btn-cancel-edit">Cancel</a>
              </div>
            </form>
          </td></tr>
          <?php else: ?>
          <tr>
            <td><?= (int)$r['rid'] ?></td>
            <td><?= htmlspecialchars($r['firstname'] . ' ' . $r['lastname']) ?></td>
            <td><?= htmlspecialchars($r['email']) ?></td>
            <td><?= htmlspecialchars($r['phone_no'] ?? '—') ?></td>
            <td><?= htmlspecialchars($r['rdate']) ?></td>
            <td><?= htmlspecialchars($r['rtime']) ?></td>
            <td><?= (int)$r['count'] ?></td>
            <td><?= htmlspecialchars($r['bname'] ?? '—') ?></td>
            <td>
              <?php $st = $r['status'] ?? 'free'; ?>
              <span class="badge badge-<?= $st ?>"><?= htmlspecialchars($st) ?></span>
            </td>
            <td style="white-space:nowrap; display:flex; gap:6px;">
              <a href="?page=bookings&tab=reservations&edit_id=<?= (int)$r['rid'] ?>&edit_type=reservation" class="act-btn act-edit">Edit</a>
              <a href="?page=bookings&tab=reservations&toggle_id=<?= (int)$r['rid'] ?>" class="act-btn" style="background:#e8eef8;color:#2a4a8b;">
                <?= ($r['status'] === 'booked') ? 'Mark Free' : 'Mark Booked' ?>
              </a>
              <form method="POST" action="?page=bookings&tab=reservations" onsubmit="return confirm('Delete reservation #<?= (int)$r['rid'] ?>?')" style="display:inline">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="type"   value="reservation">
                <input type="hidden" name="id"     value="<?= (int)$r['rid'] ?>">
                <button type="submit" class="act-btn act-delete">Delete</button>
              </form>
            </td>
          </tr>
          <?php endif; ?>
        <?php endwhile;
      else: ?>
        <tr class="empty-row"><td colspan="10">No reservations found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>

    <?php elseif ($bookings_tab === 'orders'):
      $ord = $conn->query("
        SELECT o.oid, o.oamount, o.bid, o.ostatus,
               u.firstname, u.lastname, u.email,
               b.bname
        FROM orders o
        LEFT JOIN user     u ON o.uid = u.uid
        LEFT JOIN branches b ON o.bid = b.bid
        ORDER BY o.oid DESC
      ");
    ?>
    <table>
      <thead>
        <tr>
          <th>OID</th><th>Customer</th><th>Email</th>
          <th>Amount</th><th>Branch</th><th>Status</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($ord && $ord->num_rows > 0):
        while ($o = $ord->fetch_assoc()): ?>
          <?php if ($edit_id === (int)$o['oid'] && $edit_type === 'order'): ?>
          <tr class="edit-row"><td colspan="7">
            <form method="POST" action="?page=bookings&tab=orders">
              <input type="hidden" name="action" value="edit_order">
              <input type="hidden" name="type"   value="order">
              <input type="hidden" name="id"     value="<?= (int)$o['oid'] ?>">
              <div class="edit-form-grid">
                <div><label>Amount (₹)</label><input type="number" name="oamount" step="0.01" min="0" value="<?= htmlspecialchars($o['oamount']) ?>" required></div>
                <div>
                  <label>Branch</label>
                  <select name="bid">
                    <?php foreach ($branches as $b): ?>
                      <option value="<?= (int)$b['bid'] ?>" <?= (int)$o['bid'] === (int)$b['bid'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['bname']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="edit-actions">
                <button type="submit" class="btn-save">Save</button>
                <a href="?page=bookings&tab=orders" class="btn-cancel-edit">Cancel</a>
              </div>
            </form>
          </td></tr>
          <?php else: ?>
          <tr>
            <td><?= (int)$o['oid'] ?></td>
            <td><?= htmlspecialchars($o['firstname'] . ' ' . $o['lastname']) ?></td>
            <td><?= htmlspecialchars($o['email']) ?></td>
            <td class="price">&#8377;<?= number_format((float)$o['oamount'], 2) ?></td>
            <td><?= htmlspecialchars($o['bname'] ?? '—') ?></td>
            <td>
              <?php $st = $o['ostatus'] ?? 'preparing'; ?>
              <span class="badge badge-<?= $st ?>"><?= htmlspecialchars($st) ?></span>
            </td>
            <td style="white-space:nowrap; display:flex; gap:6px; flex-wrap:wrap;">
              <a href="?page=bookings&tab=orders&edit_id=<?= (int)$o['oid'] ?>&edit_type=order" class="act-btn act-edit">Edit</a>
              <?php if ($o['ostatus'] !== 'delivered'): ?>
              <form method="POST" action="?page=bookings&tab=orders" style="display:inline">
                <input type="hidden" name="action" value="mark_delivered">
                <input type="hidden" name="type"   value="order">
                <input type="hidden" name="id"     value="<?= (int)$o['oid'] ?>">
                <button type="submit" class="act-btn act-done">&#10003; Done</button>
              </form>
              <?php endif; ?>
              <form method="POST" action="?page=bookings&tab=orders" onsubmit="return confirm('Delete order #<?= (int)$o['oid'] ?>?')" style="display:inline">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="type"   value="order">
                <input type="hidden" name="id"     value="<?= (int)$o['oid'] ?>">
                <button type="submit" class="act-btn act-delete">Delete</button>
              </form>
            </td>
          </tr>
          <?php endif; ?>
        <?php endwhile;
      else: ?>
        <tr class="empty-row"><td colspan="7">No orders found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <?php endif; ?>

  </main>

  <footer>
    <span class="logo">Rasoi</span>
    <span class="copyright">&copy; 2026 Rasoi. All rights reserved.</span>
  </footer>

<script>
function toggleNewCat(sel) {
  var custom = document.getElementById('cat_custom');
  if (sel.value === '__new__') {
    custom.style.display = 'block';
    custom.required = true;
  } else {
    custom.style.display = 'none';
    custom.required = false;
    custom.value = '';
  }
}
</script>
</body>
</html>