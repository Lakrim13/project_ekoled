<?php
session_start();
require 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = intval($_SESSION['user_id']);

// Get user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get categories
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

// Get all products
$products = $conn->query("
    SELECT p.*, c.name AS category_name, s.name AS series_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN series s ON p.series_id = s.id
    WHERE p.stock > 0
    ORDER BY p.id DESC
")->fetch_all(MYSQLI_ASSOC);

// Get series
$series = $conn->query("SELECT * FROM series ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

// Get cart count
$cart_count = 0;
$cart_stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM cart_items ci
    JOIN cart c ON ci.cart_id = c.id
    WHERE c.user_id = ?
");
$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();
if ($cart_row = $cart_result->fetch_assoc()) {
    $cart_count = $cart_row['count'];
}
$cart_stmt->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EKOLED - Éclairage LED Premium</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            /* EKOLED Dark Theme */
            --bg-dark: #0a0a0a;
            --bg-darker: #000000;
            --bg-card: #1a1a1a;
            --bg-card-hover: #252525;
            --text-primary: #ffffff;
            --text-secondary: #b3b3b3;
            --text-muted: #666666;
            --accent-gold: #d4af37;
            --accent-gold-hover: #f0c947;
            --border-color: #333333;
            --success-green: #00ff88;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.3);
            --shadow-md: 0 4px 16px rgba(0,0,0,0.4);
            --shadow-lg: 0 8px 32px rgba(0,0,0,0.5);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-dark);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
        }
        
        /* Top Bar */
        .top-bar {
            background: var(--bg-card);
            padding: 8px 0;
            font-size: 12px;
            color: var(--text-secondary);
            text-align: center;
            border-bottom: 1px solid var(--border-color);
        }
        
        /* Header */
        header {
            background: var(--bg-darker);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow-md);
        }
        
        .header-main {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .logo {
            font-size: 32px;
            font-weight: 800;
            color: var(--accent-gold);
            letter-spacing: 3px;
            text-decoration: none;
            text-transform: uppercase;
            transition: all 0.3s ease;
        }
        
        .logo:hover {
            color: var(--accent-gold-hover);
            text-shadow: 0 0 20px rgba(212,175,55,0.5);
        }
        
        nav {
            display: flex;
            gap: 40px;
            align-items: center;
        }
        
        nav a {
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
            transition: all 0.3s ease;
            position: relative;
            padding: 5px 0;
        }
        
        nav a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--accent-gold);
            transition: width 0.3s ease;
        }
        
        nav a:hover {
            color: var(--accent-gold);
        }
        
        nav a:hover::after {
            width: 100%;
        }
        
        .header-icons {
            display: flex;
            gap: 25px;
            align-items: center;
        }
        
        .header-icons a {
            color: var(--text-primary);
            font-size: 20px;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .header-icons a:hover {
            color: var(--accent-gold);
            transform: scale(1.1);
        }
        
        .cart-icon {
            position: relative;
        }
        
        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--accent-gold);
            color: var(--bg-darker);
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
        }
        
        .user-menu {
            position: relative;
        }
        
        .user-toggle {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        
        .user-toggle:hover {
            background: var(--bg-card-hover);
            border-color: var(--accent-gold);
        }
        
        .user-dropdown {
            position: absolute;
            top: 120%;
            right: 0;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            min-width: 200px;
            padding: 15px 0;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            box-shadow: var(--shadow-lg);
        }
        
        .user-menu:hover .user-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .user-dropdown a {
            display: block;
            padding: 12px 25px;
            color: var(--text-primary);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .user-dropdown a:hover {
            background: var(--bg-card-hover);
            color: var(--accent-gold);
            padding-left: 30px;
        }
        
        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #1a1a1a 0%, #000000 100%);
            padding: 100px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
            border-bottom: 1px solid var(--border-color);
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(212,175,55,0.15) 0%, transparent 70%);
            animation: pulse 8s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.3; }
            50% { transform: scale(1.2); opacity: 0.6; }
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 900px;
            margin: 0 auto;
        }
        
        .hero h1 {
            font-size: 64px;
            font-weight: 900;
            margin-bottom: 20px;
            background: linear-gradient(135deg, var(--accent-gold) 0%, var(--accent-gold-hover) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.2;
        }
        
        .hero p {
            font-size: 22px;
            color: var(--text-secondary);
            margin-bottom: 40px;
        }
        
        .hero-btn {
            display: inline-block;
            background: var(--accent-gold);
            color: var(--bg-darker);
            padding: 16px 45px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(212,175,55,0.3);
        }
        
        .hero-btn:hover {
            background: var(--accent-gold-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 30px rgba(212,175,55,0.5);
        }
        
        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 80px 40px;
        }
        
        /* Section Titles */
        .section-title {
            font-size: 42px;
            font-weight: 800;
            margin-bottom: 50px;
            color: var(--text-primary);
            position: relative;
            padding-bottom: 20px;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100px;
            height: 5px;
            background: var(--accent-gold);
            border-radius: 3px;
        }
        
        /* Categories Grid */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 35px;
            margin-bottom: 100px;
        }
        
        .category-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            position: relative;
            text-decoration: none;
        }
        
        .category-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(212,175,55,0.1) 0%, transparent 100%);
            opacity: 0;
            transition: opacity 0.4s ease;
            z-index: 1;
        }
        
        .category-card:hover {
            transform: translateY(-12px);
            border-color: var(--accent-gold);
            box-shadow: 0 15px 45px rgba(212,175,55,0.25);
        }
        
        .category-card:hover::before {
            opacity: 1;
        }
        
        .category-image {
            width: 100%;
            height: 220px;
            background: var(--bg-darker);
            overflow: hidden;
            position: relative;
        }
        
        .category-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .category-card:hover .category-image img {
            transform: scale(1.15);
        }
        
        .category-info {
            padding: 30px;
            position: relative;
            z-index: 2;
        }
        
        .category-info h3 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--text-primary);
        }
        
        .category-info p {
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 35px;
        }
        
        .product-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s ease;
            position: relative;
        }
        
        .product-card:hover {
            transform: translateY(-10px);
            border-color: var(--accent-gold);
            box-shadow: 0 15px 40px rgba(0,0,0,0.5);
        }
        
        .product-image {
            width: 100%;
            height: 280px;
            background: var(--bg-darker);
            position: relative;
            overflow: hidden;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .product-card:hover .product-image img {
            transform: scale(1.12);
        }
        
        .stock-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--success-green);
            color: var(--bg-darker);
            padding: 7px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .out-of-stock {
            background: #ff4444;
            color: white;
        }
        
        .product-info {
            padding: 28px;
        }
        
        .product-category {
            font-size: 12px;
            color: var(--accent-gold);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .product-info h3 {
            font-size: 19px;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--text-primary);
            min-height: 48px;
            line-height: 1.3;
        }
        
        .product-price {
            font-size: 28px;
            font-weight: 800;
            color: var(--accent-gold);
            margin-bottom: 20px;
        }
        
        .product-price small {
            font-size: 16px;
            color: var(--text-secondary);
            font-weight: 400;
        }
        
        .product-actions {
            display: flex;
            gap: 12px;
        }
        
        .btn-primary {
            flex: 1;
            background: var(--accent-gold);
            color: var(--bg-darker);
            border: none;
            padding: 14px 24px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-primary:hover {
            background: var(--accent-gold-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(212,175,55,0.4);
        }
        
        .btn-primary:disabled {
            background: var(--text-muted);
            cursor: not-allowed;
            transform: none;
        }
        
        /* Footer */
        footer {
            background: var(--bg-darker);
            border-top: 1px solid var(--border-color);
            padding: 80px 40px 30px;
            margin-top: 120px;
        }
        
        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 50px;
            margin-bottom: 50px;
        }
        
        .footer-section h3 {
            color: var(--accent-gold);
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 25px;
        }
        
        .footer-section ul {
            list-style: none;
        }
        
        .footer-section ul li {
            margin-bottom: 15px;
        }
        
        .footer-section a {
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .footer-section a:hover {
            color: var(--accent-gold);
            padding-left: 8px;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 40px;
            border-top: 1px solid var(--border-color);
            color: var(--text-muted);
            font-size: 14px;
        }
        
        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }
        
        .social-links a {
            width: 45px;
            height: 45px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-primary);
            transition: all 0.3s ease;
            font-size: 18px;
        }
        
        .social-links a:hover {
            background: var(--accent-gold);
            color: var(--bg-darker);
            border-color: var(--accent-gold);
            transform: translateY(-4px);
        }
        
        /* Notification */
        .notification {
            position: fixed;
            top: 100px;
            right: 30px;
            background: var(--bg-card);
            border: 1px solid var(--accent-gold);
            color: var(--text-primary);
            padding: 20px 30px;
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            transform: translateX(400px);
            transition: transform 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            z-index: 10000;
            min-width: 320px;
        }
        
        .notification.show {
            transform: translateX(0);
        }
        
        .notification i {
            color: var(--accent-gold);
            margin-right: 12px;
            font-size: 18px;
        }
        
        /* Animations */
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
        
        .category-card, .product-card {
            animation: fadeInUp 0.6s ease-out forwards;
            opacity: 0;
        }
        
        .category-card:nth-child(1) { animation-delay: 0.1s; }
        .category-card:nth-child(2) { animation-delay: 0.2s; }
        .category-card:nth-child(3) { animation-delay: 0.3s; }
        .category-card:nth-child(4) { animation-delay: 0.4s; }
        
        .product-card:nth-child(1) { animation-delay: 0.1s; }
        .product-card:nth-child(2) { animation-delay: 0.15s; }
        .product-card:nth-child(3) { animation-delay: 0.2s; }
        .product-card:nth-child(4) { animation-delay: 0.25s; }
        .product-card:nth-child(5) { animation-delay: 0.3s; }
        .product-card:nth-child(6) { animation-delay: 0.35s; }
        .product-card:nth-child(7) { animation-delay: 0.4s; }
        .product-card:nth-child(8) { animation-delay: 0.45s; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header-main {
                flex-direction: column;
                gap: 20px;
                padding: 15px 20px;
            }
            
            nav {
                flex-direction: column;
                gap: 15px;
            }
            
            .hero {
                padding: 60px 20px;
            }
            
            .hero h1 {
                font-size: 36px;
            }
            
            .hero p {
                font-size: 16px;
            }
            
            .container {
                padding: 40px 20px;
            }
            
            .section-title {
                font-size: 32px;
            }
            
            .categories-grid,
            .products-grid {
                grid-template-columns: 1fr;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
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
        <div class="header-main">
            <a href="client_dashboard.php" class="logo">EKOLED</a>
            
            <nav>
                <a href="client_dashboard.php">Accueil</a>
                <a href="#categories">Catégories</a>
                <a href="#products">Produits</a>
                <a href="#about">À Propos</a>
                <a href="#contact">Contact</a>
            </nav>
            
            <div class="header-icons">
                <a href="cart.php" class="cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if($cart_count > 0): ?>
                        <span class="cart-badge"><?= $cart_count ?></span>
                    <?php endif; ?>
                </a>
                
                <div class="user-menu">
                    <div class="user-toggle">
                        <i class="fas fa-user"></i>
                        <span><?= htmlspecialchars($user['username']) ?></span>
                        <i class="fas fa-chevron-down" style="font-size: 12px;"></i>
                    </div>
                    <div class="user-dropdown">
                        <?php if($user['role'] === 'admin'): ?>
                            <a href="profile.php"><i class="fas fa-cog"></i> Admin Panel</a>
                        <?php endif; ?>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Hero Section -->
    <div class="hero">
        <div class="hero-content">
            <h1>Éclairage LED Premium</h1>
            <p>Découvrez notre collection exclusive de solutions d'éclairage LED professionnelles</p>
            <a href="#products" class="hero-btn">
                <i class="fas fa-lightbulb"></i> Explorer nos produits
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="container">
        <!-- Categories Section -->
        <?php if(!empty($categories)): ?>
        <div id="categories">
            <h2 class="section-title">Nos Catégories</h2>
            <div class="categories-grid">
                <?php foreach($categories as $cat): ?>
                    <a href="category.php?id=<?= $cat['id'] ?>" class="category-card">
                        <div class="category-image">
                            <?php if(!empty($cat['image'])): ?>
                                <img src="uploads/<?= htmlspecialchars($cat['image']) ?>" alt="<?= htmlspecialchars($cat['name']) ?>">
                            <?php else: ?>
                                <img src="https://via.placeholder.com/400x300/1a1a1a/d4af37?text=<?= urlencode($cat['name']) ?>" alt="<?= htmlspecialchars($cat['name']) ?>">
                            <?php endif; ?>
                        </div>
                        <div class="category-info">
                            <h3><?= htmlspecialchars($cat['name']) ?></h3>
                            <p>Découvrez nos produits</p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Products Section -->
        <?php if(!empty($products)): ?>
        <div id="products">
            <h2 class="section-title">Produits Populaires</h2>
            <div class="products-grid">
                <?php foreach($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <?php if(!empty($product['image'])): ?>
                                <img src="uploads/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                            <?php else: ?>
                                <img src="https://via.placeholder.com/400x400/1a1a1a/d4af37?text=LED" alt="<?= htmlspecialchars($product['name']) ?>">
                            <?php endif; ?>
                            <span class="stock-badge <?= $product['stock'] > 0 ? '' : 'out-of-stock' ?>">
                                <?= $product['stock'] > 0 ? 'En Stock' : 'Rupture' ?>
                            </span>
                        </div>
                        <div class="product-info">
                            <div class="product-category">
                                <?= htmlspecialchars($product['category_name'] ?? 'LED') ?>
                            </div>
                            <h3><?= htmlspecialchars($product['name']) ?></h3>
                            <div class="product-price">
                                <?= number_format($product['price'], 2) ?> <small>DT</small>
                            </div>
                            <div class="product-actions">
                                <button class="btn-primary" onclick="addToCart(<?= $product['id'] ?>)" <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>
                                    <i class="fas fa-cart-plus"></i>
                                    <?= $product['stock'] > 0 ? 'Ajouter' : 'Indisponible' ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>EKOLED</h3>
                <p style="color: var(--text-secondary); line-height: 1.8;">
                    Votre partenaire de confiance pour l'éclairage LED professionnel et résidentiel au Tunisie.
                </p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            
            <div class="footer-section">
                <h3>Liens Rapides</h3>
                <ul>
                    <li><a href="#categories">Catégories</a></li>
                    <li><a href="#products">Produits</a></li>
                    <li><a href="#about">À Propos</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Service Client</h3>
                <ul>
                    <li><a href="#">Politique de Retour</a></li>
                    <li><a href="#">Conditions d'Utilisation</a></li>
                    <li><a href="#">Politique de Confidentialité</a></li>
                    <li><a href="#">FAQ</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Contact</h3>
                <ul>
                    <li style="color: var(--text-secondary);"><i class="fas fa-map-marker-alt" style="color: var(--accent-gold); margin-right: 10px;"></i> Tunisia, Sfax</li>
                    <li style="color: var(--text-secondary);"><i class="fas fa-phone" style="color: var(--accent-gold); margin-right: 10px;"></i> +216 44 266 555</li>
                    <li style="color: var(--text-secondary);"><i class="fas fa-envelope" style="color: var(--accent-gold); margin-right: 10px;"></i> hammamik99@gmail.com</li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> EKOLED. Tous droits réservés. | Éclairage LED Premium</p>
        </div>
    </footer>
    
    <!-- Notification -->
    <div class="notification" id="notification">
        <i class="fas fa-check-circle"></i>
        <span id="notificationText">Produit ajouté au panier!</span>
    </div>
    
    <script>
        function addToCart(productId) {
            fetch('add_to_list.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Produit ajouté au panier!');
                    updateCartBadge();
                } else {
                    showNotification(data.message || 'Erreur lors de l\'ajout au panier', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Erreur de connexion', 'error');
            });
        }
        
        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            const notificationText = document.getElementById('notificationText');
            
            notificationText.textContent = message;
            notification.classList.add('show');
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }
        
        function updateCartBadge() {
            fetch('cart.php?action=count')
                .then(response => response.json())
                .then(data => {
                    const badge = document.querySelector('.cart-badge');
                    if (data.count > 0) {
                        if (badge) {
                            badge.textContent = data.count;
                        } else {
                            const cartIcon = document.querySelector('.cart-icon');
                            const newBadge = document.createElement('span');
                            newBadge.className = 'cart-badge';
                            newBadge.textContent = data.count;
                            cartIcon.appendChild(newBadge);
                        }
                    }
                })
                .catch(error => console.error('Error updating cart:', error));
        }
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
