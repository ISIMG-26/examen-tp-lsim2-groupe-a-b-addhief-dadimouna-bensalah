<?php
require_once 'config.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'register':
        handleRegister();
        break;
    case 'login':
        handleLogin();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'status':
        echo json_encode(['logged_in' => isLoggedIn(), 'user' => getCurrentUser()]);
        break;
    default:
        echo json_encode(['error' => 'Action invalide']);
}

function handleRegister() {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $cin       = trim($_POST['cin'] ?? '');
    $password  = $_POST['password'] ?? '';

    if (!$full_name || !$email || !$phone || !$cin || !$password) {
        echo json_encode(['success' => false, 'message' => 'Tous les champs sont obligatoires.']);
        return;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Adresse email invalide.']);
        return;
    }
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Le mot de passe doit contenir au moins 6 caractères.']);
        return;
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Cet email est déjà utilisé.']);
        $stmt->close(); $db->close();
        return;
    }
    $stmt->close();

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $db->prepare("INSERT INTO users (full_name, email, phone, cin, password_hash) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('sssss', $full_name, $email, $phone, $cin, $hash);

    if ($stmt->execute()) {
        $user_id = $db->insert_id;
        $_SESSION['user_id']   = $user_id;
        $_SESSION['full_name'] = $full_name;
        $_SESSION['email']     = $email;
        echo json_encode(['success' => true, 'message' => 'Inscription réussie! Bienvenue ' . $full_name]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'inscription.']);
    }
    $stmt->close(); $db->close();
}

function handleLogin() {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        echo json_encode(['success' => false, 'message' => 'Email et mot de passe requis.']);
        return;
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT id, full_name, email, password_hash FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Email ou mot de passe incorrect.']);
        $stmt->close(); $db->close();
        return;
    }

    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email']     = $user['email'];
        echo json_encode(['success' => true, 'message' => 'Connexion réussie!', 'full_name' => $user['full_name']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Email ou mot de passe incorrect.']);
    }
    $stmt->close(); $db->close();
}

function handleLogout() {
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Déconnexion réussie.']);
}