<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = intval($_SESSION['user_id']);
$seriesId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$categoryId = isset($_GET['category']) ? intval($_GET['category']) : 0;

// Get user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get series info
$series = null;
if ($seriesId > 0) {
    $stmt = $conn->prepare("SELECT * FROM series WHERE id = ?");
    $stmt->bind_param("i", $seriesId);
    $stmt->execute();
    $series = $stmt->get_result()->fetch_assoc();
    
    if (!$series) {
        header('Location: client_dashboard.php');
        exit();
    }
}

// Get category info
$category = null;
if ($categoryId > 0) {
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $category = $stmt->get_result()->fetch_assoc();
}

// Get products for this series and category
$products = [];
if ($seriesId > 0) {
    $sql = "
        SELECT p.*, c.name AS category_name, s.name AS series_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN series s ON p.series_id = s.id
        WHERE p.series_id = ?
    ";
    
    if ($categoryId > 0) {
        $sql .= " AND p.category_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $seriesId, $categoryId);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $seriesId);
    }
    
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

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

// Calculate statistics
$stats = [
    'total' => count($products),
    'in_stock' => 0,
    'avg_price' => 0
];

if (!empty($products)) {
    $total_price = 0;
    foreach ($products as $p) {
        if ($p['stock'] > 0) $stats['in_stock']++;
        $total_price += $p['price'];
    }
    $stats['avg_price'] = $total_price / count($products);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($series['name'] ?? 'Produits') ?> | EKOLED</title>
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
        
        /* Breadcrumb */
        .breadcrumb-section {
            background: linear-gradient(135deg, #1a1a1a 0%, #000000 100%);
            padding: 20px 40px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .breadcrumb-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 14px;
        }
        
        .breadcrumb a {
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .breadcrumb a:hover {
            color: var(--accent-gold);
        }
        
        .breadcrumb-separator {
            color: var(--text-muted);
        }
        
        .breadcrumb-active {
            color: var(--accent-gold);
            font-weight: 600;
        }
        
        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, #1a1a1a 0%, #0a0a0a 100%);
            padding: 50px 40px;
            position: relative;
            overflow: hidden;
            border-bottom: 1px solid var(--border-color);
        }
        
        .page-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 60%;
            height: 200%;
            background: radial-gradient(circle, rgba(212,175,55,0.08) 0%, transparent 70%);
            animation: pulse 8s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .page-header-content {
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-header h1 {
            font-size: 42px;
            font-weight: 800;
            margin-bottom: 10px;
            background: linear-gradient(135deg, var(--accent-gold) 0%, var(--accent-gold-hover) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .page-header p {
            font-size: 16px;
            color: var(--text-secondary);
        }
        
        .stats-bar {
            display: flex;
            gap: 30px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 800;
            color: var(--accent-gold);
            display: block;
        }
        
        .stat-label {
            font-size: 12px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Container with Sidebar */
        .container-with-sidebar {
            max-width: 1400px;
            margin: 0 auto;
            padding: 60px 40px;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 40px;
        }
        
        /* Sidebar Filters */
        .sidebar {
            position: sticky;
            top: 120px;
            height: fit-content;
        }
        
        .filter-section {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .filter-section h3 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--accent-gold);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filter-option {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            cursor: pointer;
        }
        
        .filter-option input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-right: 12px;
            cursor: pointer;
            accent-color: var(--accent-gold);
        }
        
        .filter-option label {
            color: var(--text-secondary);
            cursor: pointer;
            transition: color 0.3s ease;
            flex: 1;
        }
        
        .filter-option:hover label {
            color: var(--text-primary);
        }
        
        .filter-count {
            color: var(--text-muted);
            font-size: 12px;
            margin-left: auto;
        }
        
        .price-filter {
            margin-top: 15px;
        }
        
        .price-inputs {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .price-inputs input {
            flex: 1;
            background: var(--bg-darker);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 10px;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .price-inputs input:focus {
            outline: none;
            border-color: var(--accent-gold);
        }
        
        .btn-filter {
            width: 100%;
            background: var(--accent-gold);
            color: var(--bg-darker);
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
        }
        
        .btn-filter:hover {
            background: var(--accent-gold-hover);
            transform: translateY(-2px);
        }
        
        .btn-reset {
            width: 100%;
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            padding: 10px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn-reset:hover {
            color: var(--text-primary);
            border-color: var(--text-primary);
        }
        
        /* Main Content */
        .main-content {
            min-height: 400px;
        }
        
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .results-count {
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        .results-count strong {
            color: var(--accent-gold);
            font-weight: 700;
        }
        
        .sort-options {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .sort-options select {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .sort-options select:focus {
            outline: none;
            border-color: var(--accent-gold);
        }
        
        .view-toggle {
            display: flex;
            gap: 8px;
        }
        
        .view-btn {
            width: 40px;
            height: 40px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .view-btn:hover,
        .view-btn.active {
            background: var(--accent-gold);
            color: var(--bg-darker);
            border-color: var(--accent-gold);
        }
        
        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
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
            height: 260px;
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
            padding: 25px;
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
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--text-primary);
            min-height: 44px;
            line-height: 1.3;
        }
        
        .product-price {
            font-size: 26px;
            font-weight: 800;
            color: var(--accent-gold);
            margin-bottom: 15px;
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
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
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
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 100px 40px;
        }
        
        .empty-state i {
            font-size: 80px;
            color: var(--text-muted);
            margin-bottom: 30px;
        }
        
        .empty-state h3 {
            font-size: 28px;
            color: var(--text-secondary);
            margin-bottom: 15px;
        }
        
        .empty-state p {
            color: var(--text-muted);
            margin-bottom: 30px;
        }
        
        .btn-back {
            display: inline-block;
            background: var(--accent-gold);
            color: var(--bg-darker);
            padding: 14px 35px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
        }
        
        .btn-back:hover {
            background: var(--accent-gold-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(212,175,55,0.4);
        }
        
        /* Footer */
        footer {
            background: var(--bg-darker);
            border-top: 1px solid var(--border-color);
            padding: 60px 40px 30px;
            margin-top: 100px;
        }
        
        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .footer-section h3 {
            color: var(--accent-gold);
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .footer-section ul {
            list-style: none;
        }
        
        .footer-section ul li {
            margin-bottom: 12px;
        }
        
        .footer-section a {
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .footer-section a:hover {
            color: var(--accent-gold);
            padding-left: 5px;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid var(--border-color);
            color: var(--text-muted);
            font-size: 14px;
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
        
        .product-card {
            animation: fadeInUp 0.6s ease-out forwards;
            opacity: 0;
        }
        
        .product-card:nth-child(1) { animation-delay: 0.1s; }
        .product-card:nth-child(2) { animation-delay: 0.15s; }
        .product-card:nth-child(3) { animation-delay: 0.2s; }
        .product-card:nth-child(4) { animation-delay: 0.25s; }
        .product-card:nth-child(5) { animation-delay: 0.3s; }
        .product-card:nth-child(6) { animation-delay: 0.35s; }
        .product-card:nth-child(7) { animation-delay: 0.4s; }
        .product-card:nth-child(8) { animation-delay: 0.45s; }
        .product-card:nth-child(9) { animation-delay: 0.5s; }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .container-with-sidebar {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                position: static;
            }
            
            .stats-bar {
                flex-wrap: wrap;
                gap: 15px;
            }
        }
        
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
            
            .page-header {
                padding: 30px 20px;
            }
            
            .page-header-content {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
            
            .page-header h1 {
                font-size: 32px;
            }
            
            .container-with-sidebar {
                padding: 40px 20px;
            }
            
            .products-grid {
                grid-template-columns: 1fr;
            }
            
            .toolbar {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
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
                <a href="client_dashboard.php#categories">Catégories</a>
                <a href="client_dashboard.php#products">Produits</a>
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
    
    <!-- Breadcrumb -->
    <div class="breadcrumb-section">
        <div class="breadcrumb-container">
            <div class="breadcrumb">
                <a href="client_dashboard.php"><i class="fas fa-home"></i> Accueil</a>
                <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
                <?php if($category): ?>
                    <a href="category.php?id=<?= $categoryId ?>"><?= htmlspecialchars($category['name']) ?></a>
                    <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
                <?php endif; ?>
                <span class="breadcrumb-active"><?= htmlspecialchars($series['name'] ?? 'Produits') ?></span>
            </div>
        </div>
    </div>
    
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1><?= htmlspecialchars($series['name'] ?? 'Produits') ?></h1>
                <p><?= $category ? htmlspecialchars($category['name']) : 'Tous les produits' ?></p>
            </div>
            <div class="stats-bar">
                <div class="stat-item">
                    <span class="stat-value"><?= $stats['total'] ?></span>
                    <span class="stat-label">Produits</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?= $stats['in_stock'] ?></span>
                    <span class="stat-label">En Stock</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?= number_format($stats['avg_price'], 0) ?> DT</span>
                    <span class="stat-label">Prix Moyen</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content with Sidebar -->
    <div class="container-with-sidebar">
        <!-- Sidebar Filters -->
        <aside class="sidebar">
            <div class="filter-section">
                <h3><i class="fas fa-filter"></i> Filtres</h3>
                
                <div class="filter-option">
                    <input type="checkbox" id="in-stock">
                    <label for="in-stock">En Stock</label>
                    <span class="filter-count">(<?= $stats['in_stock'] ?>)</span>
                </div>
                
                <div class="filter-option">
                    <input type="checkbox" id="out-stock">
                    <label for="out-stock">Rupture de Stock</label>
                    <span class="filter-count">(<?= $stats['total'] - $stats['in_stock'] ?>)</span>
                </div>
            </div>
            
            <div class="filter-section">
                <h3><i class="fas fa-tag"></i> Prix</h3>
                <div class="price-filter">
                    <div class="price-inputs">
                        <input type="number" placeholder="Min" id="price-min">
                        <input type="number" placeholder="Max" id="price-max">
                    </div>
                    <button class="btn-filter"><i class="fas fa-check"></i> Appliquer</button>
                </div>
            </div>
            
            <button class="btn-reset"><i class="fas fa-redo"></i> Réinitialiser</button>
        </aside>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="toolbar">
                <div class="results-count">
                    <strong><?= count($products) ?></strong> produit<?= count($products) > 1 ? 's' : '' ?> trouvé<?= count($products) > 1 ? 's' : '' ?>
                </div>
                <div class="sort-options">
                    <select id="sort-by">
                        <option value="default">Trier par défaut</option>
                        <option value="price-asc">Prix croissant</option>
                        <option value="price-desc">Prix décroissant</option>
                        <option value="name-asc">Nom A-Z</option>
                        <option value="name-desc">Nom Z-A</option>
                    </select>
                    <div class="view-toggle">
                        <button class="view-btn active" data-view="grid">
                            <i class="fas fa-th"></i>
                        </button>
                        <button class="view-btn" data-view="list">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <?php if(!empty($products)): ?>
                <div class="products-grid" id="products-container">
                    <?php foreach($products as $product): ?>
                        <div class="product-card" data-price="<?= $product['price'] ?>" data-stock="<?= $product['stock'] ?>">
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
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h3>Aucun produit disponible</h3>
                    <p>Cette série ne contient aucun produit pour le moment.</p>
                    <?php if($category): ?>
                        <a href="category.php?id=<?= $categoryId ?>" class="btn-back">
                            <i class="fas fa-arrow-left"></i> Retour à la catégorie
                        </a>
                    <?php else: ?>
                        <a href="client_dashboard.php" class="btn-back">
                            <i class="fas fa-arrow-left"></i> Retour à l'accueil
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>EKOLED</h3>
                <p style="color: var(--text-secondary); line-height: 1.8;">
                    Votre partenaire de confiance pour l'éclairage LED professionnel et résidentiel au Maroc.
                </p>
            </div>
            
            <div class="footer-section">
                <h3>Liens Rapides</h3>
                <ul>
                    <li><a href="client_dashboard.php#categories">Catégories</a></li>
                    <li><a href="client_dashboard.php#products">Produits</a></li>
                    <li><a href="#about">À Propos</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Contact</h3>
                <ul>
                    <li style="color: var(--text-secondary);"><i class="fas fa-map-marker-alt" style="color: var(--accent-gold); margin-right: 10px;"></i> Casablanca, Maroc</li>
                    <li style="color: var(--text-secondary);"><i class="fas fa-phone" style="color: var(--accent-gold); margin-right: 10px;"></i> +212 XXX-XXXXXX</li>
                    <li style="color: var(--text-secondary);"><i class="fas fa-envelope" style="color: var(--accent-gold); margin-right: 10px;"></i> contact@ekoled.ma</li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> EKOLED. Tous droits réservés.</p>
        </div>
    </footer>
    
    <!-- Notification -->
    <div class="notification" id="notification">
        <i class="fas fa-check-circle"></i>
        <span id="notificationText">Produit ajouté au panier!</span>
    </div>
    
    <script>
        // Add to Cart
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
            location.reload();
        }
        
        // Sorting
        document.getElementById('sort-by').addEventListener('change', function() {
            const value = this.value;
            const container = document.getElementById('products-container');
            const products = Array.from(container.children);
            
            products.sort((a, b) => {
                switch(value) {
                    case 'price-asc':
                        return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
                    case 'price-desc':
                        return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
                    case 'name-asc':
                        return a.querySelector('h3').textContent.localeCompare(b.querySelector('h3').textContent);
                    case 'name-desc':
                        return b.querySelector('h3').textContent.localeCompare(a.querySelector('h3').textContent);
                    default:
                        return 0;
                }
            });
            
            products.forEach(product => container.appendChild(product));
        });
        
        // Filters
        document.getElementById('in-stock').addEventListener('change', filterProducts);
        document.getElementById('out-stock').addEventListener('change', filterProducts);
        
        function filterProducts() {
            const inStock = document.getElementById('in-stock').checked;
            const outStock = document.getElementById('out-stock').checked;
            const products = document.querySelectorAll('.product-card');
            
            products.forEach(product => {
                const stock = parseInt(product.dataset.stock);
                let show = true;
                
                if (inStock && stock <= 0) show = false;
                if (outStock && stock > 0) show = false;
                if (inStock && outStock) show = true;
                
                product.style.display = show ? 'block' : 'none';
            });
        }
        
        // Price Filter
        document.querySelector('.btn-filter').addEventListener('click', function() {
            const min = parseFloat(document.getElementById('price-min').value) || 0;
            const max = parseFloat(document.getElementById('price-max').value) || Infinity;
            const products = document.querySelectorAll('.product-card');
            
            products.forEach(product => {
                const price = parseFloat(product.dataset.price);
                product.style.display = (price >= min && price <= max) ? 'block' : 'none';
            });
        });
        
        // Reset Filters
        document.querySelector('.btn-reset').addEventListener('click', function() {
            document.getElementById('in-stock').checked = false;
            document.getElementById('out-stock').checked = false;
            document.getElementById('price-min').value = '';
            document.getElementById('price-max').value = '';
            document.querySelectorAll('.product-card').forEach(product => {
                product.style.display = 'block';
            });
        });
        
        // View Toggle
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const view = this.dataset.view;
                const container = document.getElementById('products-container');
                
                if (view === 'list') {
                    container.style.gridTemplateColumns = '1fr';
                } else {
                    container.style.gridTemplateColumns = 'repeat(auto-fill, minmax(280px, 1fr))';
                }
            });
        });
    </script>
</body>
</html>
