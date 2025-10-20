

<?php
session_start();
require 'config.php';

header('Content-Type: application/json');

// Vérifier l'authentification
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit();
}

// Vérifier la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

// Récupérer et valider les données
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
$user_id = intval($_SESSION['user_id']);

// Validation des données
if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Produit invalide']);
    exit();
}

if ($quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Quantité invalide']);
    exit();
}

try {
    // Vérifier l'existence du produit et le stock
    $stmt = $conn->prepare("SELECT id, name, stock FROM products WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Erreur de préparation de la requête");
    }
    
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Produit introuvable']);
        exit();
    }
    
    $product = $result->fetch_assoc();
    $stmt->close();
    
    // Vérifier le stock disponible
    if ($product['stock'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Stock insuffisant (disponible: ' . $product['stock'] . ')']);
        exit();
    }
    
    // Vérifier si le panier existe, sinon le créer
    $stmt = $conn->prepare("SELECT id FROM cart WHERE user_id = ?");
    if (!$stmt) {
        throw new Exception("Erreur de préparation de la requête");
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Créer un nouveau panier
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO cart (user_id, created_at) VALUES (?, NOW())");
        if (!$stmt) {
            throw new Exception("Erreur lors de la création du panier");
        }
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $cart_id = $conn->insert_id;
        $stmt->close();
    } else {
        $cart = $result->fetch_assoc();
        $cart_id = $cart['id'];
        $stmt->close();
    }
    
    // Vérifier si le produit est déjà dans le panier
    $stmt = $conn->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
    if (!$stmt) {
        throw new Exception("Erreur de vérification du panier");
    }
    
    $stmt->bind_param("ii", $cart_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Mettre à jour la quantité
        $item = $result->fetch_assoc();
        $new_quantity = $item['quantity'] + $quantity;
        $stmt->close();
        
        // Vérifier que la nouvelle quantité ne dépasse pas le stock
        if ($new_quantity > $product['stock']) {
            echo json_encode(['success' => false, 'message' => 'Stock insuffisant pour cette quantité']);
            exit();
        }
        
        $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Erreur de mise à jour du panier");
        }
        $stmt->bind_param("ii", $new_quantity, $item['id']);
        $stmt->execute();
        $stmt->close();
    } else {
        // Ajouter un nouvel article
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity, added_at) VALUES (?, ?, ?, NOW())");
        if (!$stmt) {
            throw new Exception("Erreur d'ajout au panier");
        }
        $stmt->bind_param("iii", $cart_id, $product_id, $quantity);
        $stmt->execute();
        $stmt->close();
    }
    
    // Compter le nombre total d'articles dans le panier
    $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart_items WHERE cart_id = ?");
    $stmt->bind_param("i", $cart_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cart_total = $result->fetch_assoc()['total'];
    $stmt->close();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Produit ajouté au panier',
        'cart_count' => $cart_total
    ]);
    
} catch (Exception $e) {
    error_log("Erreur add_to_cart.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Une erreur est survenue']);
}

exit();
