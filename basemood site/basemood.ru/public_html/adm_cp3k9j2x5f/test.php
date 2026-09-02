<?php
$root = dirname(__DIR__);
$configPath = $root . '/config.php';
echo "✅ Admin panel files are accessible!<br>";
echo "Current path: " . __DIR__ . "<br>";
echo "Config path: " . $configPath . " (" . (realpath($configPath) ?: '—') . ")<br>";
echo ".security path: " . $root . '/.security' . " (" . (realpath($root . '/.security') ?: '—') . ")<br>";

echo "<br>===== FILE CHECK =====<br>";
echo "config.php exists: " . (file_exists($configPath) ? "✅ YES" : "❌ NO") . "<br>";
$secDir = $root . '/.security';
echo ".security exists: " . (is_dir($secDir) ? "✅ YES" : "❌ NO") . "<br>";
echo ".security writable: " . (is_dir($secDir) && is_writable($secDir) ? "✅ YES" : "—") . "<br>";

echo "<br>===== CONFIG INCLUDE TEST =====<br>";
if (file_exists($configPath)) {
    require_once $configPath;
    echo "Config included! Connection: " . (!empty($conn) ? "✅ OK" : "❌ FAILED") . "<br>";
} else {
    echo "❌ config.php not found!<br>";
}
