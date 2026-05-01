<?php
require_once 'config.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        listMachines();
        break;
    case 'detail':
        getMachineDetail();
        break;
    case 'add':
        addMachine();
        break;
    case 'my_machines':
        getMyMachines();
        break;
    default:
        echo json_encode(['error' => 'Action invalide']);
}

function listMachines() {
    $db   = getDB();
    $type = $_GET['type'] ?? '';
    $city = $_GET['city'] ?? '';
    $q    = $_GET['q'] ?? '';
    $from = $_GET['from'] ?? '';
    $to   = $_GET['to'] ?? '';

    $sql    = "SELECT m.*, u.full_name AS owner_name, u.phone AS owner_phone 
               FROM machines m 
               JOIN users u ON m.owner_id = u.id 
               WHERE m.status = 'available'";
    $params = [];
    $types  = '';

    if ($type && in_array($type, ['car','bike','motorcycle','scooter'])) {
        $sql    .= " AND m.type = ?";
        $params[] = $type;
        $types   .= 's';
    }
    if ($city) {
        $sql    .= " AND m.city LIKE ?";
        $params[] = "%$city%";
        $types   .= 's';
    }
    if ($q) {
        $sql    .= " AND (m.brand LIKE ? OR m.model LIKE ? OR m.description LIKE ?)";
        $params[] = "%$q%";
        $params[] = "%$q%";
        $params[] = "%$q%";
        $types   .= 'sss';
    }
    if ($from) {
        $sql    .= " AND m.available_from <= ?";
        $params[] = $from;
        $types   .= 's';
    }
    if ($to) {
        $sql    .= " AND m.available_to >= ?";
        $params[] = $to;
        $types   .= 's';
    }

    $sql .= " ORDER BY m.created_at DESC";

    $stmt = $db->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result   = $stmt->get_result();
    $machines = [];
    while ($row = $result->fetch_assoc()) {
        $machines[] = $row;
    }
    echo json_encode(['success' => true, 'machines' => $machines, 'count' => count($machines)]);
    $stmt->close(); $db->close();
}

function getMachineDetail() {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) { echo json_encode(['error' => 'ID manquant']); return; }

    $db   = getDB();
    $stmt = $db->prepare("SELECT m.*, u.full_name AS owner_name, u.phone AS owner_phone, u.email AS owner_email 
                          FROM machines m JOIN users u ON m.owner_id = u.id WHERE m.id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result  = $stmt->get_result();
    $machine = $result->fetch_assoc();
    if ($machine) {
        echo json_encode(['success' => true, 'machine' => $machine]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Machine introuvable']);
    }
    $stmt->close(); $db->close();
}

function addMachine() {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Vous devez être connecté.']);
        return;
    }

    $type          = $_POST['type'] ?? '';
    $brand         = trim($_POST['brand'] ?? '');
    $model         = trim($_POST['model'] ?? '');
    $year          = intval($_POST['year'] ?? 0);
    $description   = trim($_POST['description'] ?? '');
    $price_per_day = floatval($_POST['price_per_day'] ?? 0);
    $available_from = $_POST['available_from'] ?? '';
    $available_to   = $_POST['available_to'] ?? '';
    $city           = trim($_POST['city'] ?? '');
    $owner_id       = $_SESSION['user_id'];

    if (!$type || !$brand || !$model || !$year || !$price_per_day || !$available_from || !$available_to || !$city) {
        echo json_encode(['success' => false, 'message' => 'Tous les champs obligatoires doivent être remplis.']);
        return;
    }
    if (!in_array($type, ['car','bike','motorcycle','scooter'])) {
        echo json_encode(['success' => false, 'message' => 'Type de machine invalide.']);
        return;
    }
    if ($available_from >= $available_to) {
        echo json_encode(['success' => false, 'message' => 'La date de fin doit être après la date de début.']);
        return;
    }
    if ($price_per_day <= 0) {
        echo json_encode(['success' => false, 'message' => 'Le prix par jour doit être positif.']);
        return;
    }

    // Handle photo upload
    $photo = null;
    if (!empty($_FILES['photo']['name'])) {
        $ext     = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Format de photo invalide (jpg, png, webp seulement).']);
            return;
        }
        $filename = 'machine_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        $dest     = '../uploads/' . $filename;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
            $photo = $filename;
        }
    }

    $db   = getDB();
    $stmt = $db->prepare("INSERT INTO machines (owner_id, type, brand, model, year, description, photo, price_per_day, available_from, available_to, city) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('isssisdss s', $owner_id, $type, $brand, $model, $year, $description, $photo, $price_per_day, $available_from, $available_to, $city);

    // Fix binding
    $stmt->close();
    $stmt = $db->prepare("INSERT INTO machines (owner_id, type, brand, model, year, description, photo, price_per_day, available_from, available_to, city) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('isssisdsss', $owner_id, $type, $brand, $model, $year, $description, $photo, $price_per_day, $available_from, $available_to, $city);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Votre machine a été ajoutée avec succès!', 'id' => $db->insert_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout: ' . $stmt->error]);
    }
    $stmt->close(); $db->close();
}

function getMyMachines() {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Non connecté.']);
        return;
    }
    $owner_id = $_SESSION['user_id'];
    $db       = getDB();
    $stmt     = $db->prepare("SELECT * FROM machines WHERE owner_id = ? ORDER BY created_at DESC");
    $stmt->bind_param('i', $owner_id);
    $stmt->execute();
    $result   = $stmt->get_result();
    $machines = [];
    while ($row = $result->fetch_assoc()) {
        $machines[] = $row;
    }
    echo json_encode(['success' => true, 'machines' => $machines]);
    $stmt->close(); $db->close();
}