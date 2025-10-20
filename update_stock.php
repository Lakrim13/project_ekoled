<?php
session_start();
require 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit();
}

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès refusé']);
    exit();
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    // CSRF Token Validation
    if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token de sécurité invalide']);
        exit();
    }
    
    $id = intval($_POST['id']);
    $stock = intval($_POST['stock']);
    
    // Validate inputs
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID produit invalide']);
        exit();
    }
    
    if ($stock < 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Le stock ne peut pas être négatif']);
        exit();
    }
    
    $stmt = $conn->prepare("UPDATE products SET stock=? WHERE id=?");
    $stmt->bind_param("ii", $stock, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Stock mis à jour']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
    }
    $stmt->close();
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>
