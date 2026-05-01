<?php
// ============================================
// ROULEZ.TN - Bookings Handler
// ============================================
require_once 'config.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'book':
        createBooking();
        break;
    case 'my_bookings':
        getMyBookings();
        break;
    case 'cancel':
        cancelBooking();
        break;
    default:
        echo json_encode(['error' => 'Action invalide']);
}

function createBooking() {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour réserver.']);
        return;
    }

    $machine_id  = intval($_POST['machine_id'] ?? 0);
    $start_date  = $_POST['start_date'] ?? '';
    $end_date    = $_POST['end_date'] ?? '';
    $card_number = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
    $card_expiry = trim($_POST['card_expiry'] ?? '');
    $card_cvv    = trim($_POST['card_cvv'] ?? '');
    $card_name   = trim($_POST['card_name'] ?? '');

    // Validations
    if (!$machine_id || !$start_date || !$end_date) {
        echo json_encode(['success' => false, 'message' => 'Informations de réservation incomplètes.']);
        return;
    }
    if (!preg_match('/^\d{16}$/', $card_number)) {
        echo json_encode(['success' => false, 'message' => 'Numéro de carte invalide (16 chiffres requis).']);
        return;
    }
    if (!preg_match('/^\d{2}\/\d{2}$/', $card_expiry)) {
        echo json_encode(['success' => false, 'message' => 'Date d\'expiration invalide (MM/AA).']);
        return;
    }
    if (!preg_match('/^\d{3,4}$/', $card_cvv)) {
        echo json_encode(['success' => false, 'message' => 'CVV invalide.']);
        return;
    }
    if (!$card_name) {
        echo json_encode(['success' => false, 'message' => 'Nom sur la carte requis.']);
        return;
    }
    if ($start_date >= $end_date) {
        echo json_encode(['success' => false, 'message' => 'La date de fin doit être après la date de début.']);
        return;
    }

    $db   = getDB();
    // Get machine info
    $stmt = $db->prepare("SELECT * FROM machines WHERE id = ? AND status = 'available'");
    $stmt->bind_param('i', $machine_id);
    $stmt->execute();
    $result  = $stmt->get_result();
    $machine = $result->fetch_assoc();
    $stmt->close();

    if (!$machine) {
        echo json_encode(['success' => false, 'message' => 'Machine non disponible.']);
        $db->close(); return;
    }
    // Check owner isn't renting their own machine
    if ($machine['owner_id'] == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Vous ne pouvez pas louer votre propre machine.']);
        $db->close(); return;
    }
    // Check dates within availability
    if ($start_date < $machine['available_from'] || $end_date > $machine['available_to']) {
        echo json_encode(['success' => false, 'message' => 'Les dates choisies sont en dehors de la période de disponibilité.']);
        $db->close(); return;
    }

    // Calculate pricing
    $d1         = new DateTime($start_date);
    $d2         = new DateTime($end_date);
    $total_days = $d2->diff($d1)->days;
    if ($total_days < 1) {
        echo json_encode(['success' => false, 'message' => 'Durée minimum: 1 jour.']);
        $db->close(); return;
    }

    $subtotal       = $machine['price_per_day'] * $total_days;
    $platform_fee   = round($subtotal * PLATFORM_FEE_PERCENT / 100, 2);
    $total_amount   = $subtotal + $platform_fee;
    $card_last4     = substr($card_number, -4);
    $renter_id      = $_SESSION['user_id'];
    $price_per_day  = $machine['price_per_day'];

    $stmt = $db->prepare("INSERT INTO bookings (machine_id, renter_id, start_date, end_date, total_days, price_per_day, subtotal, platform_fee, total_amount, card_last4) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('iissiiddds', $machine_id, $renter_id, $start_date, $end_date, $total_days, $price_per_day, $subtotal, $platform_fee, $total_amount, $card_last4);

    if ($stmt->execute()) {
        $booking_id = $db->insert_id;
        // Update machine status
        $upd = $db->prepare("UPDATE machines SET status = 'rented' WHERE id = ?");
        $upd->bind_param('i', $machine_id);
        $upd->execute();
        $upd->close();

        echo json_encode([
            'success'       => true,
            'message'       => 'Réservation confirmée!',
            'booking_id'    => $booking_id,
            'total_days'    => $total_days,
            'subtotal'      => $subtotal,
            'platform_fee'  => $platform_fee,
            'total_amount'  => $total_amount,
            'card_last4'    => $card_last4,
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la réservation: ' . $stmt->error]);
    }
    $stmt->close(); $db->close();
}

function getMyBookings() {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Non connecté.']);
        return;
    }
    $renter_id = $_SESSION['user_id'];
    $db        = getDB();
    $stmt      = $db->prepare("SELECT b.*, m.brand, m.model, m.type, m.city, m.photo FROM bookings b JOIN machines m ON b.machine_id = m.id WHERE b.renter_id = ? ORDER BY b.created_at DESC");
    $stmt->bind_param('i', $renter_id);
    $stmt->execute();
    $result   = $stmt->get_result();
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    echo json_encode(['success' => true, 'bookings' => $bookings]);
    $stmt->close(); $db->close();
}

function cancelBooking() {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Non connecté.']);
        return;
    }
    $booking_id = intval($_POST['booking_id'] ?? 0);
    $user_id    = $_SESSION['user_id'];
    $db         = getDB();

    $stmt = $db->prepare("SELECT b.*, m.id AS mid FROM bookings b JOIN machines m ON b.machine_id = m.id WHERE b.id = ? AND b.renter_id = ?");
    $stmt->bind_param('ii', $booking_id, $user_id);
    $stmt->execute();
    $result  = $stmt->get_result();
    $booking = $result->fetch_assoc();
    $stmt->close();

    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Réservation introuvable.']);
        $db->close(); return;
    }

    $stmt = $db->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
    $stmt->bind_param('i', $booking_id);
    $stmt->execute();
    $stmt->close();

    // Free the machine
    $stmt = $db->prepare("UPDATE machines SET status = 'available' WHERE id = ?");
    $stmt->bind_param('i', $booking['mid']);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Réservation annulée.']);
    $db->close();
}
