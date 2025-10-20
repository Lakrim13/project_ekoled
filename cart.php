<?php
session_start();
require 'config.php';

// Ensure cart session is set when adding items
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Add item to cart session
if (isset($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);
    if (!in_array($product_id, $_SESSION['cart'])) {
        $_SESSION['cart'][] = $product_id;
    }
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = intval($_SESSION['user_id']);

// Get cart items
$stmt = $conn->prepare("
    SELECT ci.*, p.name, p.price, p.image, p.stock
    FROM cart_items ci
    JOIN cart c ON ci.cart_id = c.id
    JOIN products p ON ci.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total = 0;
foreach($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Panier - EKOLED</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --bg-dark: #0a0a0a;
            --bg-darker: #000000;
            --bg-card: #1a1a1a;
            --bg-card-hover: #252525;
            --text-primary: #ffffff;
            --text-secondary: #b3b3b3;
            --text-muted: #666666;
            --accent-gold: #d4af37;
            --accent-gold-hover: #f0c947;
            --success: #00ff88;
            --danger: #ff4444;
            --border-color: #333333;
            --border-gold: rgba(212, 175, 55, 0.3);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-dark);
            color: var(--text-primary);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* Animated Background */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 30%, rgba(212,175,55,0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(212,175,55,0.1) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(212,175,55,0.05) 0%, transparent 70%);
            animation: backgroundMove 20s ease-in-out infinite;
            z-index: 0;
        }
        
        @keyframes backgroundMove {
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.1) rotate(5deg); }
        }
        
        /* Top Bar */
        .top-bar {
            background: var(--bg-darker);
            color: var(--text-secondary);
            padding: 10px 0;
            text-align: center;
            font-size: 13px;
            border-bottom: 1px solid var(--border-color);
            position: relative;
            z-index: 10;
        }
        
        .top-bar i {
            color: var(--accent-gold);
            margin: 0 8px;
        }
        
        /* Header */
        header {
            background: rgba(26, 26, 26, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-gold);
            padding: 20px 0;
            position: sticky;
            top: 0;
            z-index: 100;
            animation: slideDown 0.5s ease;
        }
        
        @keyframes slideDown {
            from { transform: translateY(-100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo-text {
            font-size: 32px;
            font-weight: 900;
            color: var(--accent-gold);
            letter-spacing: 3px;
            text-shadow: 0 0 30px rgba(212,175,55,0.5);
        }
        
        .back-btn {
            background: rgba(212,175,55,0.1);
            color: var(--accent-gold);
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 12px;
            border: 1px solid var(--border-gold);
            font-weight: 700;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .back-btn:hover {
            background: var(--accent-gold);
            color: var(--bg-darker);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(212,175,55,0.3);
        }
        
        /* Container */
        .container {
            max-width: 1400px;
            margin: 60px auto;
            padding: 0 30px;
            position: relative;
            z-index: 1;
            animation: fadeInUp 0.8s ease;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Page Header */
        .page-header {
            text-align: center;
            margin-bottom: 60px;
            animation: fadeInUp 0.6s ease;
        }
        
        .page-title {
            font-size: 52px;
            font-weight: 900;
            color: var(--text-primary);
            margin-bottom: 15px;
            letter-spacing: -1px;
        }
        
        .page-subtitle {
            color: var(--text-secondary);
            font-size: 16px;
            letter-spacing: 1px;
        }
        
        .title-underline {
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, transparent, var(--accent-gold), transparent);
            margin: 20px auto 0;
            border-radius: 2px;
        }
        
        /* Empty Cart */
        .cart-empty {
            text-align: center;
            padding: 100px 40px;
            background: var(--bg-card);
            border: 1px solid var(--border-gold);
            border-radius: 24px;
            animation: scaleIn 0.6s ease;
        }
        
        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .cart-empty i {
            font-size: 100px;
            color: var(--accent-gold);
            margin-bottom: 30px;
            animation: float 3s ease-in-out infinite;
            display: inline-block;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        .cart-empty h2 {
            font-size: 32px;
            color: var(--text-primary);
            margin-bottom: 15px;
            font-weight: 800;
        }
        
        .cart-empty p {
            color: var(--text-secondary);
            font-size: 16px;
            margin-bottom: 40px;
        }
        
        .cart-empty .btn-primary {
            display: inline-block;
            padding: 16px 40px;
            background: linear-gradient(135deg, var(--accent-gold) 0%, var(--accent-gold-hover) 100%);
            color: var(--bg-darker);
            text-decoration: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.4s ease;
            box-shadow: 0 8px 25px rgba(212,175,55,0.3);
        }
        
        .cart-empty .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(212,175,55,0.5);
        }
        
        /* Cart Layout */
        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 40px;
        }
        
        /* Cart Items */
        .cart-items {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .cart-item {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 25px;
            display: grid;
            grid-template-columns: 120px 1fr auto;
            gap: 25px;
            align-items: center;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            animation: slideInRight 0.6s ease backwards;
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .cart-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--accent-gold);
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }
        
        .cart-item:hover {
            background: var(--bg-card-hover);
            border-color: var(--border-gold);
            transform: translateX(5px);
        }
        
        .cart-item:hover::before {
            transform: scaleY(1);
        }
        
        .cart-item-image {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        
        .cart-item img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            transition: transform 0.4s ease;
        }
        
        .cart-item:hover img {
            transform: scale(1.1);
        }
        
        .cart-item-info h3 {
            color: var(--text-primary);
            margin-bottom: 12px;
            font-size: 20px;
            font-weight: 700;
        }
        
        .cart-item-price {
            color: var(--accent-gold);
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 10px;
        }
        
        .cart-item-price small {
            font-size: 14px;
            opacity: 0.8;
        }
        
        .cart-item-stock {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--success);
            font-size: 13px;
            background: rgba(0, 255, 136, 0.1);
            padding: 6px 12px;
            border-radius: 8px;
            border: 1px solid rgba(0, 255, 136, 0.3);
        }
        
        .cart-item-actions {
            display: flex;
            flex-direction: column;
            gap: 15px;
            align-items: flex-end;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--bg-darker);
            padding: 8px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }
        
        .quantity-control button {
            width: 36px;
            height: 36px;
            border: none;
            background: var(--bg-card);
            border: 1px solid var(--border-gold);
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
            font-weight: bold;
            color: var(--accent-gold);
            transition: all 0.3s ease;
        }
        
        .quantity-control button:hover {
            background: var(--accent-gold);
            color: var(--bg-darker);
            transform: scale(1.1);
        }
        
        .quantity-control button:active {
            transform: scale(0.95);
        }
        
        .quantity-control input {
            width: 60px;
            text-align: center;
            border: none;
            background: transparent;
            color: var(--text-primary);
            font-size: 16px;
            font-weight: 700;
        }
        
        .remove-btn {
            background: rgba(255, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(255, 68, 68, 0.3);
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .remove-btn:hover {
            background: var(--danger);
            color: var(--bg-darker);
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(255, 68, 68, 0.3);
        }
        
        /* Cart Summary */
        .cart-summary {
            background: var(--bg-card);
            border: 1px solid var(--border-gold);
            border-radius: 24px;
            padding: 35px;
            position: sticky;
            top: 120px;
            height: fit-content;
            animation: fadeInUp 0.8s ease 0.2s backwards;
        }
        
        .cart-summary h3 {
            color: var(--text-primary);
            margin-bottom: 30px;
            font-size: 24px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .cart-summary h3 i {
            color: var(--accent-gold);
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 18px;
            padding-bottom: 18px;
            border-bottom: 1px dashed var(--border-color);
            font-size: 15px;
            color: var(--text-secondary);
        }
        
        .summary-row span:last-child {
            color: var(--text-primary);
            font-weight: 600;
        }
        
        .summary-row.total {
            font-size: 28px;
            font-weight: 800;
            color: var(--accent-gold);
            border-bottom: none;
            padding-top: 18px;
            margin-top: 18px;
            border-top: 2px solid var(--border-gold);
        }
        
        .checkout-btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, var(--accent-gold) 0%, var(--accent-gold-hover) 100%);
            color: var(--bg-darker);
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.4s ease;
            margin-top: 25px;
            box-shadow: 0 8px 25px rgba(212,175,55,0.3);
            position: relative;
            overflow: hidden;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .checkout-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(0,0,0,0.1);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .checkout-btn:hover::before {
            width: 500px;
            height: 500px;
        }
        
        .checkout-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(212,175,55,0.5);
        }
        
        .checkout-btn:active {
            transform: translateY(-1px);
        }
        
        .checkout-btn i {
            position: relative;
            z-index: 1;
        }
        
        .checkout-btn span {
            position: relative;
            z-index: 1;
        }
        
        /* Security Badge */
        .security-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
            padding: 12px;
            background: rgba(0, 255, 136, 0.05);
            border-radius: 10px;
            color: var(--success);
            font-size: 12px;
            border: 1px solid rgba(0, 255, 136, 0.2);
        }
        
        /* Footer */
        footer {
            background: var(--bg-darker);
            border-top: 1px solid var(--border-color);
            padding: 60px 0 30px;
            margin-top: 100px;
        }
        
        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
        }
        
        .footer-section h4 {
            color: var(--accent-gold);
            font-size: 16px;
            font-weight: 800;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .footer-section p, .footer-section a {
            color: var(--text-secondary);
            font-size: 14px;
            line-height: 1.8;
            text-decoration: none;
            display: block;
            margin-bottom: 10px;
            transition: color 0.3s ease;
        }
        
        .footer-section a:hover {
            color: var(--accent-gold);
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 30px;
            margin-top: 30px;
            border-top: 1px solid var(--border-color);
            color: var(--text-muted);
            font-size: 13px;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .cart-layout {
                grid-template-columns: 1fr;
            }
            
            .cart-summary {
                position: static;
            }
        }
        
        @media (max-width: 768px) {
            .cart-item {
                grid-template-columns: 1fr;
                text-align: center;
                padding: 20px;
            }
            
            .cart-item img {
                width: 100%;
                height: 200px;
                margin: 0 auto;
            }
            
            .cart-item-actions {
                align-items: center;
                width: 100%;
            }
            
            .page-title {
                font-size: 36px;
            }
            
            .container {
                padding: 0 20px;
            }
        }
        
        @media (max-width: 480px) {
            .page-title {
                font-size: 28px;
            }
            
            .cart-item {
                padding: 15px;
            }
            
            .cart-summary {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <i class="fas fa-truck"></i> Livraison gratuite pour les commandes supérieures à 500 DT | <i class="fas fa-phone"></i> Contact: +216 44 266 555
    </div>

    <!-- Header -->
    <header>
        <div class="header-content">
            <div class="logo">
                <div class="logo-text">EKOLED</div>
            </div>
            <a href="<?= $_SESSION['role'] === 'admin' ? 'profile.php' : 'client_dashboard.php' ?>" class="back-btn">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>
    </header>
    
    <!-- Main Container -->
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Mon Panier</h1>
            <p class="page-subtitle">Gérez vos articles et finalisez votre commande</p>
            <div class="title-underline"></div>
        </div>
        
        <?php if (empty($cart_items)): ?>
            <div class="cart-empty">
                <i class="fas fa-shopping-cart"></i>
                <h2>Votre panier est vide</h2>
                <p>Ajoutez des produits pour commencer vos achats</p>
                <a href="<?= $_SESSION['role'] === 'admin' ? 'profile.php' : 'client_dashboard.php' ?>" class="btn-primary">
                    <i class="fas fa-shopping-bag"></i> Continuer mes achats
                </a>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <!-- Cart Items -->
                <div class="cart-items">
                    <?php foreach($cart_items as $index => $item): ?>
                    <div class="cart-item" style="animation-delay: <?= $index * 0.1 ?>s;" data-cart-item="<?= $item['id'] ?>">
                        <div class="cart-item-image">
                            <img src="uploads/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" onerror="this.src='https://via.placeholder.com/120/d4af37/0a0a0a?text=LED'">
                        </div>
                        <div class="cart-item-info">
                            <h3><?= htmlspecialchars($item['name']) ?></h3>
                            <div class="cart-item-price">
                                <?= number_format($item['price'], 2) ?> <small>DT</small>
                            </div>
                            <div class="cart-item-stock">
                                <i class="fas fa-box"></i>
                                En stock: <?= $item['stock'] ?>
                            </div>
                        </div>
                        <div class="cart-item-actions">
                            <div class="quantity-control">
                                <button onclick="updateQuantity(<?= $item['id'] ?>, -1)" title="Diminuer">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>" readonly data-item-id="<?= $item['id'] ?>">
                                <button onclick="updateQuantity(<?= $item['id'] ?>, 1)" title="Augmenter">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <button class="remove-btn" onclick="removeItem(<?= $item['id'] ?>)">
                                <i class="fas fa-trash-alt"></i> Retirer
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Cart Summary -->
                <div class="cart-summary">
                    <h3><i class="fas fa-receipt"></i> Récapitulatif</h3>
                    <div class="summary-row">
                        <span>Sous-total (<?= count($cart_items) ?> article<?= count($cart_items) > 1 ? 's' : '' ?>)</span>
                        <span><?= number_format($total, 2) ?> DT</span>
                    </div>
                    <div class="summary-row">
                        <span>Livraison</span>
                        <span style="color: var(--success);">Gratuite</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span class="total-amount"><?= number_format($total, 2) ?> DT</span>
                    </div>
                    <a href="checkout.php" class="checkout-btn">
                        <i class="fas fa-lock"></i>
                        <span>Passer la commande</span>
                    </a>
                    <div class="security-badge">
                        <i class="fas fa-shield-alt"></i>
                        <span>Paiement 100% Sécurisé</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h4>À propos d'EKOLED</h4>
                <p>Leader tunisien de l'éclairage LED premium. Solutions professionnelles pour tous vos projets.</p>
            </div>
            <div class="footer-section">
                <h4>Liens Rapides</h4>
                <a href="client_dashboard.php">Accueil</a>
                <a href="#">Nos Produits</a>
                <a href="#">À Propos</a>
                <a href="#">Contact</a>
            </div>
            <div class="footer-section">
                <h4>Service Client</h4>
                <a href="#">Livraison</a>
                <a href="#">Retours</a>
                <a href="#">Garantie</a>
                <a href="#">FAQ</a>
            </div>
            <div class="footer-section">
                <h4>Contact</h4>
                <p><i class="fas fa-phone"></i> +216 44 266 555</p>
                <p><i class="fas fa-envelope"></i> contact@ekoled.tn</p>
                <p><i class="fas fa-map-marker-alt"></i> Tunis, Tunisie</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 EKOLED. Tous droits réservés.</p>
        </div>
    </footer>
    
    <script>
        function updateQuantity(itemId, change) {
            const quantityInput = document.querySelector(`input[data-item-id="${itemId}"]`);
            const currentQuantity = parseInt(quantityInput?.value || 1);
            const newQuantity = currentQuantity + change;
            
            if (newQuantity < 1) {
                showNotification('La quantité ne peut pas être inférieure à 1', 'error');
                return;
            }
            
            fetch('update_cart_quantity.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `cart_item_id=${itemId}&change=${change}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Quantité mise à jour', 'success');
                    // Update UI
                    if (quantityInput) {
                        quantityInput.value = data.new_quantity;
                    }
                    // Update total
                    const totalElement = document.querySelector('.total-amount');
                    if (totalElement) {
                        totalElement.textContent = data.cart_total + ' DT';
                    }
                    // Reload to update all prices
                    setTimeout(() => location.reload(), 500);
                } else {
                    showNotification(data.message || 'Erreur lors de la mise à jour', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Erreur de connexion', 'error');
            });
        }
        
        function removeItem(itemId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
                fetch('remove_from_cart.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `cart_item_id=${itemId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Article supprimé', 'success');
                        // Remove item from DOM with animation
                        const itemElement = document.querySelector(`[data-cart-item="${itemId}"]`);
                        if (itemElement) {
                            itemElement.style.animation = 'slideOutRight 0.3s ease-out';
                            setTimeout(() => {
                                location.reload();
                            }, 300);
                        } else {
                            location.reload();
                        }
                    } else {
                        showNotification(data.message || 'Erreur lors de la suppression', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Erreur de connexion', 'error');
                });
            }
        }
        
        // Notification system
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            `;
            
            // Add styles if not already added
            if (!document.querySelector('#notification-styles')) {
                const style = document.createElement('style');
                style.id = 'notification-styles';
                style.textContent = `
                    .notification {
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        padding: 15px 25px;
                        border-radius: 10px;
                        color: white;
                        font-weight: 600;
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        z-index: 10000;
                        animation: slideInRight 0.3s ease-out;
                        box-shadow: 0 5px 20px rgba(0,0,0,0.3);
                    }
                    .notification-success {
                        background: linear-gradient(135deg, #48bb78, #38a169);
                    }
                    .notification-error {
                        background: linear-gradient(135deg, #ff4444, #cc0000);
                    }
                    .notification-info {
                        background: linear-gradient(135deg, #d4af37, #f0c947);
                        color: #000;
                    }
                    @keyframes slideInRight {
                        from {
                            transform: translateX(400px);
                            opacity: 0;
                        }
                        to {
                            transform: translateX(0);
                            opacity: 1;
                        }
                    }
                    @keyframes slideOutRight {
                        from {
                            transform: translateX(0);
                            opacity: 1;
                        }
                        to {
                            transform: translateX(400px);
                            opacity: 0;
                        }
                    }
                `;
                document.head.appendChild(style);
            }
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        // Smooth scroll to top on page load
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    </script>
</body>
</html>
