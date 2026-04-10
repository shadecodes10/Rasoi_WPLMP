<?php
$f   = file_get_contents('index.php');
$old = '     <span>Welcome, <?= $_SESSION[\'username\'] ?></span>';
$new = '     <span>Welcome, <?= htmlspecialchars($_SESSION[\'username\']) ?></span>';
$f2  = str_replace($old, $new, $f, $count);
if ($count) {
    file_put_contents('index.php', $f2);
    echo "Fixed $count instance(s)\n";
} else {
    echo "String not found. Check manually.\n";
    // Dump surrounding area for debugging
    $pos = strpos($f, 'Welcome,');
    echo substr($f, max(0, $pos - 10), 100) . "\n";
}
