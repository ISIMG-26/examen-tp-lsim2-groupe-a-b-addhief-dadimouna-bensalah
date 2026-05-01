<?php
// ============================================
// ROULEZ.TN - Configuration Check
// ============================================
echo '<h1>✅ Vérification du projet Roulez.tn</h1>';
echo '<style>body{font-family:Arial;margin:20px;} .ok{color:green;} .err{color:red;} .warn{color:orange;}</style>';

// Check PHP version
echo '<p><strong>PHP Version:</strong> ' . phpversion() . '</p>';

// Check required directories
$dirs = ['uploads'];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        echo "<p class='ok'>✅ Dossier <code>$dir/</code> existe</p>";
    } else {
        echo "<p class='err'>❌ Dossier <code>$dir/</code> manquant</p>";
    }
}

// Check required files
$files = ['index.html', 'browser.html', 'lend.html', 'detail.html', 'style.css', 'main.js', 
          'auth.php', 'machines.php', 'bookings.php', 'config.php', 'database.sql'];
foreach ($files as $file) {
    if (file_exists($file)) {
        $size = filesize($file);
        echo "<p class='ok'>✅ <code>$file</code> ($size bytes)</p>";
    } else {
        echo "<p class='err'>❌ <code>$file</code> manquant</p>";
    }
}

// Try database connection
echo '<h2>Vérification de la base de données</h2>';
require_once 'config.php';
$db = getDB();
if ($db) {
    echo "<p class='ok'>✅ Connexion à la base réussie</p>";
    
    // Check tables
    $tables = ['users', 'machines', 'bookings'];
    foreach ($tables as $table) {
        $result = $db->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            echo "<p class='ok'>✅ Table <code>$table</code> existe</p>";
        } else {
            echo "<p class='warn'>⚠️ Table <code>$table</code> manquante - importez database.sql</p>";
        }
    }
    $db->close();
} else {
    echo "<p class='err'>❌ Erreur de connexion à la base</p>";
}

echo '<h2>Prochaines étapes</h2>';
echo '<ol>';
echo '<li>Vérifiez que tous les fichiers ✅ sont présents</li>';
echo '<li>Si des tables manquent, importez <code>database.sql</code> dans phpMyAdmin</li>';
echo '<li>Accédez à <a href="index.html">index.html</a> pour lancer l\'application</li>';
echo '</ol>';
?>
