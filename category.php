<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$category_id = intval($_GET['id'] ?? 0);

if ($category_id <= 0) {
    header('Location: client_dashboard.php');
    exit();
}

$user_id = intval($_SESSION['user_id']);

// Get user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get category info
$category = null;
$stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $category = $result->fetch_assoc();
} else {
    header('Location: client_dashboard.php');
    exit();
}
$stmt->close();

// Get series for this category with product counts
$series = [];
$stmt = $conn->prepare("
    SELECT s.*, COUNT(DISTINCT p.id) as product_count
    FROM series s
    LEFT JOIN products p ON s.id = p.series_id AND p.category_id = ?
    WHERE EXISTS (SELECT 1 FROM products WHERE series_id = s.id AND category_id = ?)
    GROUP BY s.id
    ORDER BY s.name ASC
");
$stmt->bind_param("ii", $category_id, $category_id);
$stmt->execute();
$series = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

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
    <title><?= htmlspecialchars($category['name']) ?> - Séries | EKOLED</title>
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
            padding: 30px 40px;
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
            padding: 60px 40px;
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
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.1) rotate(5deg); }
        }
        
        .page-header-content {
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }
        
        .page-header h1 {
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 15px;
            background: linear-gradient(135deg, var(--accent-gold) 0%, var(--accent-gold-hover) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .page-header p {
            font-size: 18px;
            color: var(--text-secondary);
        }
        
        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 80px 40px;
        }
        
        /* Section Title */
        .section-title {
            font-size: 36px;
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
        
        /* Series Grid */
        .series-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 35px;
        }
        
        .series-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            position: relative;
            text-decoration: none;
        }
        
        .series-card::before {
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
        
        .series-card:hover {
            transform: translateY(-12px) scale(1.02);
            border-color: var(--accent-gold);
            box-shadow: 0 20px 50px rgba(212,175,55,0.3);
        }
        
        .series-card:hover::before {
            opacity: 1;
        }
        
        .series-image {
            width: 100%;
            height: 240px;
            background: var(--bg-darker);
            overflow: hidden;
            position: relative;
        }
        
        .series-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .series-card:hover .series-image img {
            transform: scale(1.15) rotate(2deg);
        }
        
        .product-count-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--accent-gold);
            color: var(--bg-darker);
            padding: 8px 18px;
            border-radius: 25px;
            font-size: 13px;
            font-weight: 700;
            z-index: 2;
        }
        
        .series-info {
            padding: 30px;
            position: relative;
            z-index: 2;
        }
        
        .series-info h3 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 12px;
            color: var(--text-primary);
        }
        
        .series-info p {
            color: var(--text-secondary);
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .series-btn {
            display: inline-block;
            background: transparent;
            border: 2px solid var(--accent-gold);
            color: var(--accent-gold);
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .series-card:hover .series-btn {
            background: var(--accent-gold);
            color: var(--bg-darker);
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
        
        .series-card {
            animation: fadeInUp 0.6s ease-out forwards;
            opacity: 0;
        }
        
        .series-card:nth-child(1) { animation-delay: 0.1s; }
        .series-card:nth-child(2) { animation-delay: 0.2s; }
        .series-card:nth-child(3) { animation-delay: 0.3s; }
        .series-card:nth-child(4) { animation-delay: 0.4s; }
        .series-card:nth-child(5) { animation-delay: 0.5s; }
        .series-card:nth-child(6) { animation-delay: 0.6s; }
        
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
            
            .page-header {
                padding: 40px 20px;
            }
            
            .page-header h1 {
                font-size: 32px;
            }
            
            .container {
                padding: 40px 20px;
            }
            
            .series-grid {
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
                <a href="client_dashboard.php#categories">Catégories</a>
                <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
                <span class="breadcrumb-active"><?= htmlspecialchars($category['name']) ?></span>
            </div>
        </div>
    </div>
    
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header-content">
            <h1><?= htmlspecialchars($category['name']) ?></h1>
            <p>Découvrez nos séries de produits dans cette catégorie</p>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="container">
        <?php if(!empty($series)): ?>
            <h2 class="section-title">Nos Séries</h2>
            <div class="series-grid">
                <?php foreach($series as $s): ?>
                    <a href="series.php?id=<?= $s['id'] ?>&category=<?= $category_id ?>" class="series-card">
                        <div class="series-image">
                            <?php if(!empty($s['image'])): ?>
                                <img src="uploads/<?= htmlspecialchars($s['image']) ?>" alt="<?= htmlspecialchars($s['name']) ?>">
                            <?php else: ?>
                                <img src="https://via.placeholder.com/400x300/1a1a1a/d4af37?text=<?= urlencode($s['name']) ?>" alt="<?= htmlspecialchars($s['name']) ?>">
                            <?php endif; ?>
                            <span class="product-count-badge">
                                <?= $s['product_count'] ?> Produit<?= $s['product_count'] > 1 ? 's' : '' ?>
                            </span>
                        </div>
                        <div class="series-info">
                            <h3><?= htmlspecialchars($s['name']) ?></h3>
                            <p>Explorez notre gamme de produits <?= htmlspecialchars($s['name']) ?></p>
                            <span class="series-btn">
                                <i class="fas fa-arrow-right"></i> Voir les produits
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <h3>Aucune série disponible</h3>
                <p>Cette catégorie ne contient aucune série pour le moment.</p>
                <a href="client_dashboard.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Retour à l'accueil
                </a>
            </div>
        <?php endif; ?>
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
</body>
</html>
