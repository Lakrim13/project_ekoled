<?php
// checkout.php - Page de paiement
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);

// Get cart items from database
$stmt = $conn->prepare("
    SELECT ci.id as cart_item_id, ci.quantity, ci.product_id,
           p.id, p.name, p.price, p.image, p.stock
    FROM cart_items ci
    JOIN cart c ON ci.cart_id = c.id
    JOIN products p ON ci.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Check if cart is empty
if (empty($products)) {
    $_SESSION['error'] = "Votre panier est vide.";
    header("Location: cart.php");
    exit();
}

// Calculate total and verify stock
$total = 0;
foreach ($products as $product) {
    $quantity = $product['quantity'];
    
    // Check stock availability
    if ($product['stock'] < $quantity) {
        $_SESSION['error'] = "Le produit '{$product['name']}' n'a pas assez de stock (disponible: {$product['stock']}, demandé: {$quantity}).";
        header("Location: cart.php");
        exit();
    }
    
    $total += $product['price'] * $quantity;
}

// Traitement du paiement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    // CSRF Token Validation
    if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
        $_SESSION['error'] = "Token de sécurité invalide!";
        header("Location: checkout.php");
        exit();
    }
    
    // Validate and sanitize inputs
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $customer_address = trim($_POST['customer_address'] ?? '');
    $payment_method = $_POST['payment_method'] ?? '';
    
    // Validation
    if (empty($customer_name) || strlen($customer_name) < 3) {
        $_SESSION['error'] = "Le nom doit contenir au moins 3 caractères.";
        header("Location: checkout.php");
        exit();
    }
    
    if (empty($customer_phone) || !preg_match('/^[0-9]{8,15}$/', $customer_phone)) {
        $_SESSION['error'] = "Numéro de téléphone invalide (8-15 chiffres).";
        header("Location: checkout.php");
        exit();
    }
    
    if (empty($customer_address) || strlen($customer_address) < 10) {
        $_SESSION['error'] = "L'adresse doit contenir au moins 10 caractères.";
        header("Location: checkout.php");
        exit();
    }
    
    if (!in_array($payment_method, ['cod', 'card', 'paypal', 'bank'])) {
        $_SESSION['error'] = "Méthode de paiement invalide.";
        header("Location: checkout.php");
        exit();
    }
    
    // Sanitize
    $customer_name = htmlspecialchars($customer_name, ENT_QUOTES, 'UTF-8');
    $customer_address = htmlspecialchars($customer_address, ENT_QUOTES, 'UTF-8');
    
    // Vérifier à nouveau le stock avant de créer la commande
    foreach ($products as $product) {
        $quantity = $product['quantity'];
        if ($product['stock'] < $quantity) {
            $_SESSION['error'] = "Le produit '{$product['name']}' n'est plus disponible en quantité suffisante.";
            header("Location: cart.php");
            exit();
        }
    }
    
    // Créer la commande
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total, payment_method, customer_name, customer_phone, customer_address, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("idssss", $user_id, $total, $payment_method, $customer_name, $customer_phone, $customer_address);
    $stmt->execute();
    $order_id = $stmt->insert_id;
    $stmt->close();
    
    // Ajouter les items et réduire le stock
    $insertItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    $updateStock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
    
    foreach ($products as $product) {
        $quantity = $product['quantity'];
        $item_price = $product['price'];
        
        // Insérer l'item avec la bonne quantité
        $insertItem->bind_param("iiid", $order_id, $product['id'], $quantity, $item_price);
        $insertItem->execute();
        
        // Réduire le stock de la quantité commandée
        $updateStock->bind_param("iii", $quantity, $product['id'], $quantity);
        $updateStock->execute();
    }
    $insertItem->close();
    $updateStock->close();
    
    // Vider le panier de la base de données
    $stmt = $conn->prepare("
        DELETE ci FROM cart_items ci
        JOIN cart c ON ci.cart_id = c.id
        WHERE c.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    
    // Rediriger selon la méthode de paiement
    if ($payment_method === 'card') {
        header("Location: payment_card.php?order_id=" . $order_id);
    } elseif ($payment_method === 'paypal') {
        header("Location: payment_paypal.php?order_id=" . $order_id);
    } elseif ($payment_method === 'bank') {
        header("Location: payment_bank.php?order_id=" . $order_id);
    } else {
        // Paiement à la livraison (COD)
        $stmt = $conn->prepare("UPDATE orders SET status = 'confirmed' WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $stmt->close();
        header("Location: order_success.php?order_id=" . $order_id);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement - EKOLED</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0a0a0a;
            color: #ffffff;
            min-height: 100vh;
        }

        /* Header */
        header {
            background: linear-gradient(135deg, #1a1a1a 0%, #0a0a0a 100%);
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #d4af37;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
        }

        .logo img {
            filter: brightness(1.2);
        }

        nav {
            display: flex;
            gap: 30px;
        }

        nav a {
            color: #ffffff;
            text-decoration: none;
            font-weight: 500;
            font-size: 16px;
            transition: all 0.3s;
            position: relative;
        }

        nav a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: #d4af37;
            transition: width 0.3s;
        }

        nav a:hover {
            color: #d4af37;
        }

        nav a:hover::after {
            width: 100%;
        }

        /* Main Container */
        .checkout-container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 40px;
            display: grid;
            grid-template-columns: 1fr 450px;
            gap: 40px;
        }

        /* Form Section */
        .checkout-form {
            background: linear-gradient(135deg, #1a1a1a 0%, #0f0f0f 100%);
            padding: 40px;
            border-radius: 20px;
            border: 1px solid #333;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        }

        .checkout-form h2 {
            color: #d4af37;
            font-size: 28px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .checkout-form h3 {
            color: #ffffff;
            font-size: 20px;
            margin: 30px 0 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #b3b3b3;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 15px;
            background: #0a0a0a;
            border: 2px solid #333;
            border-radius: 12px;
            color: #ffffff;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #d4af37;
            outline: none;
            box-shadow: 0 0 20px rgba(212, 175, 55, 0.2);
        }

        .form-group textarea {
            resize: vertical;
            font-family: inherit;
        }

        /* Payment Methods */
        .payment-methods {
            display: grid;
            gap: 15px;
            margin: 20px 0;
        }

        .payment-option {
            background: #0a0a0a;
            border: 2px solid #333;
            border-radius: 15px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 20px;
            position: relative;
            overflow: hidden;
        }

        .payment-option::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(212, 175, 55, 0.1), transparent);
            transition: left 0.5s;
        }

        .payment-option:hover::before {
            left: 100%;
        }

        .payment-option:hover {
            border-color: #d4af37;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(212, 175, 55, 0.2);
        }

        .payment-option.selected {
            border-color: #d4af37;
            background: linear-gradient(135deg, rgba(212, 175, 55, 0.1) 0%, transparent 100%);
            box-shadow: 0 0 30px rgba(212, 175, 55, 0.2);
        }

        .payment-option input[type="radio"] {
            width: 20px;
            height: 20px;
            accent-color: #d4af37;
        }

        .payment-icon {
            font-size: 32px;
            width: 50px;
            text-align: center;
        }

        .payment-details {
            flex-grow: 1;
        }

        .payment-title {
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 5px;
            font-size: 16px;
        }

        .payment-desc {
            color: #888;
            font-size: 13px;
        }

        /* Order Summary */
        .order-summary {
            background: linear-gradient(135deg, #1a1a1a 0%, #0f0f0f 100%);
            padding: 40px;
            border-radius: 20px;
            border: 1px solid #333;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .order-summary h3 {
            color: #d4af37;
            font-size: 24px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
        }

        /* Order Items */
        .order-item {
            display: flex;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid #222;
            transition: all 0.3s;
        }

        .order-item:hover {
            background: rgba(212, 175, 55, 0.05);
            padding-left: 10px;
            margin-left: -10px;
            border-radius: 10px;
        }

        .order-item img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 12px;
            margin-right: 20px;
            border: 2px solid #333;
        }

        .item-info {
            flex-grow: 1;
        }

        .item-name {
            font-weight: 600;
            margin-bottom: 8px;
            color: #ffffff;
            font-size: 15px;
        }

        .item-price {
            color: #d4af37;
            font-weight: 700;
            font-size: 16px;
        }

        /* Total Section */
        .total-section {
            margin-top: 30px;
            padding-top: 25px;
            border-top: 2px solid #d4af37;
        }

        .total-line {
            display: flex;
            justify-content: space-between;
            margin: 15px 0;
            color: #b3b3b3;
            font-size: 15px;
        }

        .total-final {
            font-size: 24px;
            font-weight: 900;
            color: #d4af37;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #333;
        }

        /* Pay Button */
        .pay-button {
            width: 100%;
            background: linear-gradient(135deg, #d4af37 0%, #f0c947 100%);
            color: #000000;
            border: none;
            padding: 18px;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 900;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 30px;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 5px 20px rgba(212, 175, 55, 0.3);
        }

        .pay-button:hover {
            background: linear-gradient(135deg, #f0c947 0%, #d4af37 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(212, 175, 55, 0.5);
        }

        .pay-button:active {
            transform: translateY(-1px);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .checkout-container {
                grid-template-columns: 1fr;
                padding: 20px;
            }

            .order-summary {
                position: static;
            }
        }

        @media (max-width: 768px) {
            header {
                padding: 15px 20px;
            }

            nav {
                gap: 15px;
            }

            nav a {
                font-size: 14px;
            }

            .checkout-form,
            .order-summary {
                padding: 25px;
            }

            .checkout-form h2 {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="logo"><img src="uploads/ekoled2.png" width="100" height="50"></div>
    <nav>
        <a href="profile.php">Accueil</a>
        <a href="products.php">Produits</a>
        <a href="cart.php">Mon Panier</a>
        <a href="#">À propos</a>
    </nav>
</header>

<div class="checkout-container">
    <div class="checkout-form">
        <h2><i class="fas fa-credit-card"></i> Finaliser votre commande</h2>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            
            <div class="form-group">
                <label>Nom complet *</label>
                <input type="text" name="customer_name" required>
            </div>
            
            <div class="form-group">
                <label>Téléphone *</label>
                <input type="number" name="customer_phone" required>
            </div>
            
            <div class="form-group">
                <label>Adresse de livraison *</label>
                <textarea name="customer_address" rows="3" required></textarea>
            </div>
            
            <h3>Méthode de paiement</h3>
            <div class="payment-methods">
                <div class="payment-option" onclick="selectPayment('cod', this)">
                    <input type="radio" name="payment_method" value="cod" id="cod" checked>
                    <div class="payment-icon" style="color: #28a745;">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="payment-details">
                        <div class="payment-title">Paiement à la livraison</div>
                        <div class="payment-desc">Payez en espèces lors de la réception</div>
                    </div>
                </div>
                
                <div class="payment-option" onclick="selectPayment('card', this)">
                    <input type="radio" name="payment_method" value="card" id="card">
                    <div class="payment-icon" style="color: #007bff;">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="payment-details">
                        <div class="payment-title">Carte bancaire</div>
                        <div class="payment-desc">Visa, Mastercard, etc.</div>
                    </div>
                </div>
                
                <div class="payment-option" onclick="selectPayment('paypal', this)">
                    <input type="radio" name="payment_method" value="paypal" id="paypal">
                    <div class="payment-icon" style="color: #0070ba;">
                        <i class="fab fa-paypal"></i>
                    </div>
                    <div class="payment-details">
                        <div class="payment-title">PayPal</div>
                        <div class="payment-desc">Paiement sécurisé via PayPal</div>
                    </div>
                </div>
                
                <div class="payment-option" onclick="selectPayment('bank', this)">
                    <input type="radio" name="payment_method" value="bank" id="bank">
                    <div class="payment-icon" style="color: #6c757d;">
                        <i class="fas fa-university"></i>
                    </div>
                    <div class="payment-details">
                        <div class="payment-title">Virement bancaire</div>
                        <div class="payment-desc">Paiement par virement</div>
                    </div>
                </div>
            </div>
            
            <button type="submit" name="process_payment" class="pay-button">
                <i class="fas fa-lock"></i> Finaliser le paiement
            </button>
        </form>
    </div>
    
    <div class="order-summary">
        <h3><i class="fas fa-receipt"></i> Récapitulatif</h3>
        
        <?php foreach ($products as $product): ?>
            <div class="order-item">
                <img src="uploads/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                <div class="item-info">
                    <div class="item-name">
                        <?= htmlspecialchars($product['name']) ?>
                        <?php if ($product['quantity'] > 1): ?>
                            <span style="color: #666; font-size: 0.9em;"> × <?= $product['quantity'] ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="item-price">
                        <?= number_format($product['price'] * $product['quantity'], 2) ?> DT
                        <?php if ($product['quantity'] > 1): ?>
                            <span style="color: #999; font-size: 0.85em;">(<?= number_format($product['price'], 2) ?> DT × <?= $product['quantity'] ?>)</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div class="total-section">
            <div class="total-line">
                <span>Sous-total:</span>
                <span><?= number_format($total, 2) ?> DT</span>
            </div>
            <div class="total-line">
                <span>Livraison:</span>
                <span>Gratuite</span>
            </div>
            <div class="total-line total-final">
                <span>Total:</span>
                <span><?= number_format($total, 2) ?> DT</span>
            </div>
        </div>
    </div>
</div>

<script>
function selectPayment(method, element) {
    // Retirer la sélection précédente
    document.querySelectorAll('.payment-option').forEach(option => {
        option.classList.remove('selected');
    });
    
    // Ajouter la sélection
    element.classList.add('selected');
    document.getElementById(method).checked = true;
}

// Sélectionner COD par défaut
document.addEventListener('DOMContentLoaded', function() {
    selectPayment('cod', document.querySelector('.payment-option'));
});
</script>

</body>
</html>