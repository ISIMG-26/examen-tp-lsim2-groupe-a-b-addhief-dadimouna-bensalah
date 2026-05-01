<?php
// ============================================
// ROULEZ.TN - Diagnostic complet
// ============================================
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Diagnostic PHP - Roulez.tn</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      max-width: 900px;
      margin: 0 auto;
      padding: 20px;
      background: #f5f5f5;
    }
    .container {
      background: white;
      border-radius: 8px;
      padding: 30px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    h1 { color: #c1272d; border-bottom: 2px solid #c1272d; padding-bottom: 10px; }
    h2 { color: #333; margin-top: 30px; }
    .ok { color: #4caf50; font-weight: bold; }
    .err { color: #f44336; font-weight: bold; }
    .warn { color: #ff9800; font-weight: bold; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
    code { font-family: 'Courier New', monospace; }
  </style>
</head>
<body>

<div class="container">
  <h1>🔧 Diagnostic PHP - Roulez.tn</h1>
  
  <h2>Configuration serveur</h2>
  <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
  <p><strong>Serveur:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
  <p><strong>Répertoire courant:</strong> <?php echo getcwd(); ?></p>
  
  <h2>Fichiers du projet</h2>
  <?php
  $files = ['config.php', 'auth.php', 'machines.php', 'bookings.php', 'index.html', 'browser.html'];
  foreach ($files as $file) {
    if (file_exists($file)) {
      $size = filesize($file);
      echo "<p><span class='ok'>✅</span> <code>$file</code> ($size bytes)</p>";
    } else {
      echo "<p><span class='err'>❌</span> <code>$file</code> manquant</p>";
    }
  }
  ?>
  
  <h2>Dossiers</h2>
  <?php
  $dirs = ['uploads'];
  foreach ($dirs as $dir) {
    if (is_dir($dir)) {
      echo "<p><span class='ok'>✅</span> Dossier <code>$dir/</code> existe</p>";
    } else {
      echo "<p><span class='err'>❌</span> Dossier <code>$dir/</code> manquant</p>";
    }
  }
  ?>
  
  <h2>Connexion base de données</h2>
  <?php
  require_once 'config.php';
  $db = getDB();
  if ($db) {
    echo "<p><span class='ok'>✅</span> Connexion à la base réussie</p>";
    
    // Vérifier les tables
    $tables = ['users', 'machines', 'bookings'];
    foreach ($tables as $table) {
      $result = $db->query("SHOW TABLES LIKE '$table'");
      if ($result && $result->num_rows > 0) {
        echo "<p><span class='ok'>✅</span> Table <code>$table</code> existe</p>";
      } else {
        echo "<p><span class='err'>❌</span> Table <code>$table</code> manquante</p>";
      }
    }
    
    // Vérifier les données
    $result = $db->query("SELECT COUNT(*) as count FROM machines");
    if ($result) {
      $row = $result->fetch_assoc();
      $count = $row['count'];
      echo "<p><strong>Véhicules en base:</strong> $count</p>";
    }
    
    $db->close();
  } else {
    echo "<p><span class='err'>❌</span> Erreur de connexion à la base</p>";
  }
  ?>
  
  <h2>Test d'API - Machines</h2>
  <?php
  $db = getDB();
  $stmt = $db->prepare("SELECT COUNT(*) as count FROM machines");
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  echo "<pre><code>";
  echo json_encode([
    'success' => true,
    'machines' => [],
    'count' => $row['count'],
    'note' => 'Réponse partielle pour diagnostic'
  ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  echo "</code></pre>";
  $stmt->close();
  $db->close();
  ?>
  
  <h2>Prochaines étapes</h2>
  <ol>
    <li>Si tous les fichiers ✅ sont présents, le projet est bien configuré</li>
    <li>Si les tables ❌ manquent, importez <code>database.sql</code> dans phpMyAdmin</li>
    <li>Si la connexion BD ❌, vérifiez que MySQL est actif et les identifiants dans <code>config.php</code></li>
    <li>Une fois tout ✅, allez à <a href="index.html">index.html</a></li>
  </ol>
</div>

</body>
</html>
