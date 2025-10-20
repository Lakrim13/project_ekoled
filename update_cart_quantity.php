<?php
session_start();
require 'config.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit();
}

// Check method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

// Get and validate data
$cart_item_id = isset($_POST['cart_item_id']) ? intval($_POST['cart_item_id']) : 0;
$change = isset($_POST['change']) ? intval($_POST['change']) : 0;
$user_id = intval($_SESSION['user_id']);

// Validate inputs
if ($cart_item_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Article invalide']);
    exit();
}

if ($change == 0) {
    echo json_encode(['success' => false, 'message' => 'Changement invalide']);
    exit();
}

try {
    // Get current cart item with product stock
    $stmt = $conn->prepare("
        SELECT ci.id, ci.quantity, ci.product_id, p.stock, p.name
        FROM cart_items ci
        JOIN cart c ON ci.cart_id = c.id
        JOIN products p ON ci.product_id = p.id
        WHERE ci.id = ? AND c.user_id = ?
    ");
    $stmt->bind_param("ii", $cart_item_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Article introuvable']);
        exit();
    }
    
    $cart_item = $result->fetch_assoc();
    $stmt->close();
    
    $new_quantity = $cart_item['quantity'] + $change;
    
    // Validate new quantity
    if ($new_quantity < 1) {
        echo json_encode(['success' => false, 'message' => 'La quantité ne peut pas être inférieure à 1']);
        exit();
    }
    
    if ($new_quantity > $cart_item['stock']) {
        echo json_encode([
            'success' => false, 
            'message' => "Stock insuffisant. Disponible: {$cart_item['stock']}"
        ]);
        exit();
    }
    
    // Update quantity
    $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_quantity, $cart_item_id);
    
    if ($stmt->execute()) {
        // Get updated cart total
        $total_stmt = $conn->prepare("
            SELECT SUM(ci.quantity * p.price) as total
            FROM cart_items ci
            JOIN cart c ON ci.cart_id = c.id
            JOIN products p ON ci.product_id = p.id
            WHERE c.user_id = ?
        ");
        $total_stmt->bind_param("i", $user_id);
        $total_stmt->execute();
        $total_result = $total_stmt->get_result();
        $total_row = $total_result->fetch_assoc();
        $cart_total = $total_row['total'] ?? 0;
        $total_stmt->close();
        
        // Get cart count
        $count_stmt = $conn->prepare("
            SELECT SUM(ci.quantity) as count
            FROM cart_items ci
            JOIN cart c ON ci.cart_id = c.id
            WHERE c.user_id = ?
        ");
        $count_stmt->bind_param("i", $user_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count_row = $count_result->fetch_assoc();
        $cart_count = $count_row['count'] ?? 0;
        $count_stmt->close();
        
        echo json_encode([
            'success' => true,
            'message' => 'Quantité mise à jour',
            'new_quantity' => $new_quantity,
            'cart_total' => number_format($cart_total, 2),
            'cart_count' => $cart_count
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}

exit();
