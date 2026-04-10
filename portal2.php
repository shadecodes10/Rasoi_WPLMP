<?php
$conn = new mysqli("localhost", "root", "", "restaurant_db");

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$page = isset($_GET['page']) ? $_GET['page'] : 'home';

/* ── BOOKINGS: handle all POST actions ── */
if ($page == 'bookings' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $type   = $_POST['type']   ?? '';
  $id     = (int)($_POST['id'] ?? 0);

  /* ── DELETE ── */
  if ($action === 'delete' && $id > 0) {
    if ($type === 'order') {
      $conn->query("DELETE FROM orders WHERE oid = $id");
    } else {
      $conn->query("DELETE FROM reservation WHERE rid = $id"); // MODIFIED
    }
  }

  /* ── EDIT RESERVATION ── */
  /* Editable fields: rdate, rtime, count, bid (branch) */
  if ($action === 'edit_reservation' && $id > 0) {
    $rdate = $conn->real_escape_string($_POST['rdate'] ?? ''); // MODIFIED
    $rtime = $conn->real_escape_string($_POST['rtime'] ?? ''); // MODIFIED
    $count = (int)($_POST['count'] ?? 1);                      // MODIFIED
    $bid   = (int)($_POST['bid']   ?? 0);
    $conn->query("UPDATE reservation                           
                  SET rdate='$rdate', rtime='$rtime',
                      count=$count, bid=$bid
                  WHERE rid=$id");                             // MODIFIED
  }

  /* ── EDIT ORDER ── */
  if ($action === 'edit_order' && $id > 0) {
    $oamount = $conn->real_escape_string($_POST['oamount'] ?? '');
    $bid     = (int)($_POST['bid'] ?? 0);
    $conn->query("UPDATE orders SET oamount='$oamount', bid=$bid WHERE oid=$id");
  }

  /* redirect to same tab after action */
  $tab = ($type === 'order') ? 'orders' : 'reservations';
  header("Location: portal2.php?page=bookings&tab=$tab");
  exit;
}

// ADDED: Toggle reservation status via GET ?toggle_id=RID
if (isset($_GET['toggle_id'])) {
  $toggle_id = (int)$_GET['toggle_id'];
  if ($toggle_id > 0) {
    $cur = $conn->query("SELECT status FROM reservation WHERE rid = $toggle_id LIMIT 1"); // MODIFIED
    if ($cur && $cur->num_rows > 0) {
      $row_status = $cur->fetch_assoc();
      $new_status = ($row_status['status'] === 'booked') ? 'free' : 'booked';
      $conn->query("UPDATE reservation SET status = '$new_status' WHERE rid = $toggle_id"); // MODIFIED
    }
  }
  header("Location: portal2.php?page=bookings&tab=reservations");
  exit;
}

$bookings_tab = $_GET['tab'] ?? 'reservations';
$edit_id      = isset($_GET['edit_id'])   ? (int)$_GET['edit_id']   : 0;
$edit_type    = $_GET['edit_type'] ?? '';
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
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
 
    body {
      background-color: var(--cream);
      font-family: 'Jost', sans-serif;
      color: var(--text-main);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
 
    a { text-decoration: none; color: inherit; }
 
    header {
      text-align: center;
      padding: 48px 20px 12px;
    }
 
    header h1 {
      font-family: 'Cormorant Garamond', serif;
      font-size: 2.6rem;
      font-weight: 600;
      color: var(--rust);
      letter-spacing: 0.04em;
    }
 
    header p {
      font-size: 0.95rem;
      font-weight: 300;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: var(--text-muted);
      margin-top: 6px;
    }
 
    .divider {
      width: 60px;
      height: 1px;
      background: var(--rust-light);
      margin: 20px auto 0;
      opacity: 0.5;
    }
 
    main {
      flex: 1;
      padding: 60px 40px;
      max-width: 1000px;
      margin: 0 auto;
      width: 100%;
    }
 
    .card-grid {
      display: flex;
      gap: 48px;
      flex-wrap: wrap;
      justify-content: center;
      margin-top: 20px;
    }
 
    .admin-card {
      background: var(--card-bg);
      width: 200px;
      height: 200px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 14px;
      border-radius: 4px;
      transition: background 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
    }
 
    .admin-card:hover {
      background: var(--card-hover);
      transform: translateY(-3px);
      box-shadow: 0 8px 24px rgba(44,31,20,0.12);
    }
 
    .card-icon { font-size: 1.8rem; opacity: 0.65; line-height: 1; }
 
    .card-label {
      font-size: 0.8rem;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      font-weight: 400;
      color: var(--text-muted);
    }
 
    .card-btn {
      display: inline-block;
      padding: 9px 22px;
      background: var(--rust);
      color: #fff;
      font-family: 'Jost', sans-serif;
      font-size: 0.75rem;
      font-weight: 500;
      letter-spacing: 0.16em;
      text-transform: uppercase;
      border-radius: 2px;
      cursor: pointer;
      transition: background 0.2s ease, transform 0.15s ease;
    }
 
    .card-btn:hover { background: var(--rust-light); transform: scale(1.03); }
    .card-btn:active { transform: scale(0.98); }
 
    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 0.8rem;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--text-muted);
      margin-bottom: 28px;
      transition: color 0.2s;
    }
 
    .back-link:hover { color: var(--rust); }
 
    .section-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 1.8rem;
      font-weight: 600;
      color: var(--rust);
      margin-bottom: 6px;
    }
 
    .section-divider {
      width: 40px;
      height: 1px;
      background: var(--rust-light);
      opacity: 0.5;
      margin-bottom: 28px;
    }
 
    table {
      width: 100%;
      border-collapse: collapse;
      background: var(--table-bg);
      border-radius: 4px;
      overflow: hidden;
      box-shadow: 0 2px 12px rgba(44,31,20,0.07);
    }
 
    thead tr { background: var(--brown-dark); }
 
    thead th {
      padding: 14px 16px;
      font-size: 0.72rem;
      font-weight: 500;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: rgba(240,235,224,0.85);
      text-align: left;
    }
 
    tbody tr { border-bottom: 1px solid var(--border); transition: background 0.15s; }
    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: #ede8df; }
 
    tbody td { padding: 13px 16px; font-size: 0.88rem; font-weight: 300; }
    tbody td.price { font-weight: 500; color: var(--rust); }
 
    .empty-row td {
      text-align: center;
      color: var(--text-muted);
      font-style: italic;
      padding: 30px;
    }
 
    footer {
      background: var(--brown-dark);
      padding: 20px 36px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: auto;
    }
 
    footer .logo {
      font-family: 'Cormorant Garamond', serif;
      font-size: 1.25rem;
      font-style: italic;
      color: var(--cream);
      letter-spacing: 0.06em;
    }
 
    footer .copyright {
      font-size: 0.72rem;
      font-weight: 300;
      color: rgba(240,235,224,0.45);
    }

    /* ── TABS ── */
    .tab-bar {
      display: flex;
      gap: 0;
      border-bottom: 2px solid var(--border);
      margin-bottom: 32px;
    }
    .tab-btn {
      padding: 10px 28px;
      font-family: 'Jost', sans-serif;
      font-size: 0.78rem;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      color: var(--text-muted);
      text-decoration: none;
      border-bottom: 2px solid transparent;
      margin-bottom: -2px;
      transition: color 0.2s, border-color 0.2s;
    }
    .tab-btn:hover { color: var(--rust); }
    .tab-btn.active { color: var(--rust); border-bottom-color: var(--rust); font-weight: 500; }

    /* ── STATUS BADGE ── */
    .badge {
      display: inline-block;
      padding: 3px 10px;
      border-radius: 2px;
      font-size: 0.72rem;
      font-weight: 500;
      letter-spacing: 0.1em;
      text-transform: uppercase;
    }
    .badge-confirmed  { background: #e8f4ea; color: #2a6b35; }
    .badge-seated     { background: #fff3e0; color: #a05a00; }
    .badge-completed  { background: #ede8df; color: var(--text-muted); }
    .badge-cancelled  { background: #fdecea; color: #b52a1e; }
    .badge-pending    { background: #e8eef8; color: #2a4a8b; }
    .badge-preparing  { background: #fff3e0; color: #a05a00; }
    .badge-delivered  { background: #e8f4ea; color: #2a6b35; }

    /* ── ACTION BUTTONS ── */
    .act-btn {
      display: inline-block;
      padding: 5px 14px;
      font-family: 'Jost', sans-serif;
      font-size: 0.72rem;
      font-weight: 500;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      border-radius: 2px;
      cursor: pointer;
      border: none;
      transition: background 0.18s;
    }
    .act-edit   { background: var(--card-bg); color: var(--text-main); text-decoration: none; }
    .act-edit:hover { background: var(--card-hover); }
    .act-delete { background: #fdecea; color: #b52a1e; }
    .act-delete:hover { background: #f5c8c4; }

    /* ── STATUS SELECT in table ── */
    .status-select {
      font-family: 'Jost', sans-serif;
      font-size: 0.78rem;
      padding: 5px 8px;
      border: 1px solid var(--border);
      border-radius: 2px;
      background: var(--table-bg);
      color: var(--text-main);
      cursor: pointer;
    }

    /* ── INLINE EDIT FORM ROW ── */
    .edit-row td {
      background: #f5f0e8;
      padding: 20px 16px !important;
    }
    .edit-form-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: 12px 16px;
      margin-bottom: 14px;
    }
    .edit-form-grid label {
      display: block;
      font-size: 0.7rem;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--text-muted);
      margin-bottom: 4px;
    }
    .edit-form-grid input,
    .edit-form-grid select {
      width: 100%;
      padding: 7px 10px;
      font-family: 'Jost', sans-serif;
      font-size: 0.85rem;
      border: 1px solid var(--border);
      border-radius: 2px;
      background: #fff;
      color: var(--text-main);
    }
    .edit-actions { display: flex; gap: 10px; margin-top: 4px; }
    .btn-save {
      padding: 7px 22px;
      background: var(--rust);
      color: #fff;
      font-family: 'Jost', sans-serif;
      font-size: 0.75rem;
      font-weight: 500;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      border: none;
      border-radius: 2px;
      cursor: pointer;
      transition: background 0.2s;
    }
    .btn-save:hover { background: var(--rust-light); }
    .btn-cancel-edit {
      padding: 7px 18px;
      background: var(--card-bg);
      color: var(--text-muted);
      font-family: 'Jost', sans-serif;
      font-size: 0.75rem;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      border: none;
      border-radius: 2px;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
    }
    .btn-cancel-edit:hover { background: var(--card-hover); }
  </style>
</head>
<body>
 
  <header>
    <h1>Rasoi</h1>
    <p>Admin Portal</p>
    <div class="divider"></div>
  </header>
 
  <main>
 
    <?php if ($page == 'home') { ?>
 
      <div class="card-grid">
        <div class="admin-card">
          <span class="card-icon">&#128101;</span>
          <span class="card-label">Users</span>
          <a href="?page=users" class="card-btn">View All Users</a>
        </div>
        <div class="admin-card">
          <span class="card-icon">&#127373;</span>
          <span class="card-label">Dishes</span>
          <a href="?page=dishes" class="card-btn">View All Dishes</a>
        </div>
        <div class="admin-card">
          <span class="card-icon">&#128203;</span>
          <span class="card-label">Orders &amp; Reservations</span>
          <a href="?page=bookings" class="card-btn">Manage Bookings</a>
        </div>
      </div>
 
    <?php } ?>
 
    <?php if ($page == 'users') { ?>
 
      <a href="portal2.php" class="back-link">&larr; Back</a>
      <h2 class="section-title">Users</h2>
      <div class="section-divider"></div>
 
      <table>
        <thead>
          <tr>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Email</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $result = $conn->query("SELECT firstname, lastname, email FROM user");
          if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              echo "<tr>
                <td>" . htmlspecialchars($row['firstname']) . "</td>
                <td>" . htmlspecialchars($row['lastname']) . "</td>
                <td>" . htmlspecialchars($row['email']) . "</td>
              </tr>";
            }
          } else {
            echo "<tr class='empty-row'><td colspan='3'>No users found.</td></tr>";
          }
          ?>
        </tbody>
      </table>
 
    <?php } ?>
 
    <?php if ($page == 'dishes') { ?>
 
      <a href="portal2.php" class="back-link">&larr; Back</a>
      <h2 class="section-title">Dishes</h2>
      <div class="section-divider"></div>
 
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Price</th>
            <th>Category</th>
            <th>Type</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $result = $conn->query("SELECT * FROM dishes");
          if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              echo "<tr>
                <td>" . htmlspecialchars($row['name']) . "</td>
                <td class='price'>&#8377;" . htmlspecialchars($row['price']) . "</td>
                <td>" . htmlspecialchars($row['category']) . "</td>
                <td>" . htmlspecialchars($row['type']) . "</td>
              </tr>";
            }
          } else {
            echo "<tr class='empty-row'><td colspan='4'>No dishes found.</td></tr>";
          }
          ?>
        </tbody>
      </table>
 
    <?php } ?>

    <?php if ($page == 'bookings') { ?>

      <a href="portal2.php" class="back-link">&larr; Back</a>
      <h2 class="section-title">Orders &amp; Reservations</h2>
      <div class="section-divider"></div>

      <div class="tab-bar">
        <a href="?page=bookings&tab=reservations"
           class="tab-btn <?= $bookings_tab === 'reservations' ? 'active' : '' ?>">Reservations</a>
        <a href="?page=bookings&tab=orders"
           class="tab-btn <?= $bookings_tab === 'orders' ? 'active' : '' ?>">Orders</a>
      </div>

      <?php
      /* ── Pre-fetch branches for dropdowns ── */
      $branches_res = $conn->query("SELECT bid, bname FROM BRANCHES ORDER BY bname");
      $branches = [];
      if ($branches_res) {
        while ($b = $branches_res->fetch_assoc()) $branches[] = $b;
      }
      ?>

      <?php if ($bookings_tab === 'reservations'):
        /* JOIN: reservation → user (name, phone) → branches (bname) */ // MODIFIED
        $res = $conn->query("
          SELECT r.rid, r.rdate, r.rtime, r.count, r.bid, r.status,
                 u.firstname, u.lastname, u.email, u.phone_no,
                 b.bname
          FROM reservation r
          LEFT JOIN user     u ON r.uid = u.uid
          LEFT JOIN branches b ON r.bid = b.bid
          ORDER BY r.rdate DESC, r.rtime DESC
        "); // MODIFIED — your actual table/column names, removed t_no/table join

      // MODIFIED: removed table fetch — your reservation table has no t_no column
      ?>
      <table>
        <thead>
          <tr>
            <th>RID</th>
            <th>Guest</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Date</th>
            <th>Time</th>
            <th>Guests</th>
            <th>Branch</th>
            <th>Status</th><!-- MODIFIED -->
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($res && $res->num_rows > 0):
          while ($r = $res->fetch_assoc()): ?>

            <?php if ($edit_id === (int)$r['rid'] && $edit_type === 'reservation'): // MODIFIED: rid ?>
            <tr class="edit-row"><td colspan="10">
              <form method="POST" action="?page=bookings&tab=reservations">
                <input type="hidden" name="action" value="edit_reservation">
                <input type="hidden" name="type"   value="reservation">
                <input type="hidden" name="id"     value="<?= (int)$r['rid'] ?>"> <!-- MODIFIED: rid -->
                <div class="edit-form-grid">
                  <div>
                    <label>Date</label>
                    <input type="date" name="rdate" value="<?= htmlspecialchars($r['rdate']) ?>" required> <!-- MODIFIED -->
                  </div>
                  <div>
                    <label>Time</label>
                    <input type="time" name="rtime" value="<?= htmlspecialchars($r['rtime']) ?>"> <!-- MODIFIED -->
                  </div>
                  <div>
                    <label>No. of Guests</label>
                    <input type="number" name="count" min="1" max="20" value="<?= (int)$r['count'] ?>"> <!-- MODIFIED -->
                  </div>
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
                </div> <!-- MODIFIED: removed t_no/table dropdown -->
                <p style="font-size:0.78rem; color:var(--text-muted); margin-bottom:12px;">
                  Note: Guest name, email &amp; phone are tied to the user account and cannot be changed here.
                </p>
                <div class="edit-actions">
                  <button type="submit" class="btn-save">Save Changes</button>
                  <a href="?page=bookings&tab=reservations" class="btn-cancel-edit">Cancel</a>
                </div>
              </form>
            </td></tr>

            <?php else: ?>
            <tr>
              <td><?= (int)$r['rid'] ?></td> <!-- MODIFIED: rid -->
              <td><?= htmlspecialchars($r['firstname'] . ' ' . $r['lastname']) ?></td>
              <td><?= htmlspecialchars($r['email']) ?></td>
              <td><?= htmlspecialchars($r['phone_no']) ?></td>
              <td><?= htmlspecialchars($r['rdate']) ?></td>  <!-- MODIFIED: rdate -->
              <td><?= htmlspecialchars($r['rtime']) ?></td>  <!-- MODIFIED: rtime -->
              <td><?= (int)$r['count'] ?></td>               <!-- MODIFIED: count -->
              <td><?= htmlspecialchars($r['bname'] ?? '—') ?></td>
              <!-- ADDED: status badge -->
              <td>
                <?php
                  $st = $r['status'] ?? 'free';
                  $badge_class = ($st === 'booked') ? 'badge-confirmed' : 'badge-completed';
                ?>
                <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($st) ?></span>
              </td>
              <td style="white-space:nowrap; display:flex; gap:6px;">
                <a href="?page=bookings&tab=reservations&edit_id=<?= (int)$r['rid'] ?>&edit_type=reservation"
                   class="act-btn act-edit">Edit</a> <!-- MODIFIED: rid -->
                <!-- ADDED: Toggle button -->
                <a href="?page=bookings&tab=reservations&toggle_id=<?= (int)$r['rid'] ?>"
                   class="act-btn"
                   style="background:#e8eef8; color:#2a4a8b;">
                   <?= ($r['status'] === 'booked') ? 'Mark Free' : 'Mark Booked' ?>
                </a>
                <form method="POST" action="?page=bookings&tab=reservations"
                      onsubmit="return confirm('Delete reservation #<?= (int)$r['rid'] ?>?')" style="display:inline">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="type"   value="reservation">
                  <input type="hidden" name="id"     value="<?= (int)$r['rid'] ?>"> <!-- MODIFIED: rid -->
                  <button type="submit" class="act-btn act-delete">Delete</button>
                </form>
              </td>
            </tr>
            <?php endif; ?>

          <?php endwhile; ?>
        <?php else: ?>
          <tr class="empty-row"><td colspan="10">No reservations found.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>

      <?php elseif ($bookings_tab === 'orders'):
        /* JOIN: ORDERS → USER (name, email) → BRANCHES (bname) */
        $ord = $conn->query("
          SELECT o.oid, o.oamount, o.bid,
                 u.firstname, u.lastname, u.email,
                 b.bname
          FROM ORDERS o
          LEFT JOIN USER     u ON o.uid = u.uid
          LEFT JOIN BRANCHES b ON o.bid = b.bid
          ORDER BY o.oid DESC
        ");
      ?>
      <table>
        <thead>
          <tr>
            <th>OID</th>
            <th>Customer</th>
            <th>Email</th>
            <th>Amount</th>
            <th>Branch</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($ord && $ord->num_rows > 0):
          while ($o = $ord->fetch_assoc()): ?>

            <?php if ($edit_id === (int)$o['oid'] && $edit_type === 'order'): ?>
            <tr class="edit-row"><td colspan="6">
              <form method="POST" action="?page=bookings&tab=orders">
                <input type="hidden" name="action" value="edit_order">
                <input type="hidden" name="type"   value="order">
                <input type="hidden" name="id"     value="<?= (int)$o['oid'] ?>">
                <div class="edit-form-grid">
                  <div>
                    <label>Amount (₹)</label>
                    <input type="number" name="oamount" step="0.01" min="0"
                           value="<?= htmlspecialchars($o['oamount']) ?>" required>
                  </div>
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
                <p style="font-size:0.78rem; color:var(--text-muted); margin-bottom:12px;">
                  Note: Customer name &amp; email are tied to the user account and cannot be changed here.
                </p>
                <div class="edit-actions">
                  <button type="submit" class="btn-save">Save Changes</button>
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
              <td style="white-space:nowrap; display:flex; gap:6px;">
                <a href="?page=bookings&tab=orders&edit_id=<?= (int)$o['oid'] ?>&edit_type=order"
                   class="act-btn act-edit">Edit</a>
                <form method="POST" action="?page=bookings&tab=orders"
                      onsubmit="return confirm('Delete order #<?= (int)$o['oid'] ?>?')" style="display:inline">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="type"   value="order">
                  <input type="hidden" name="id"     value="<?= (int)$o['oid'] ?>">
                  <button type="submit" class="act-btn act-delete">Delete</button>
                </form>
              </td>
            </tr>
            <?php endif; ?>

          <?php endwhile; ?>
        <?php else: ?>
          <tr class="empty-row"><td colspan="6">No orders found.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
      <?php endif; ?>

    <?php } ?>

  </main>
 
  <footer>
    <span class="logo">Rasoi</span>
    <span class="copyright">&copy; 2026 Rasoi. All rights reserved.</span>
  </footer>
 
</body>
</html>