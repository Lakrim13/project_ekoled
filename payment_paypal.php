<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    $_SESSION['error'] = "Commande introuvable";
    header("Location: cart.php");
    exit();
}

// Verify order belongs to user
$user_id = intval($_SESSION['user_id']);
$stmt = $conn->prepare("SELECT id, total FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order) {
    $_SESSION['error'] = "Commande introuvable";
    header("Location: cart.php");
    exit();
}

// Process PayPal payment simulation
if (isset($_GET['success']) && $_GET['success'] == '1') {
    // CSRF Token Validation
    if (!isset($_GET['token']) || !validateCSRF($_GET['token'])) {
        $_SESSION['error'] = "Token de sécurité invalide!";
        header("Location: cart.php");
        exit();
    }
    
    // Update order status
    $stmt = $conn->prepare("UPDATE orders SET status = 'paid', payment_method = 'paypal' WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $stmt->close();
    
    $_SESSION['success'] = "Paiement PayPal effectué avec succès!";
    header("Location: order_success.php?order_id=" . $order_id);
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement PayPal - EKOLED</title>
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Animated Background */
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 30% 40%, rgba(0, 112, 186, 0.15) 0%, transparent 50%),
                        radial-gradient(circle at 70% 70%, rgba(212, 175, 55, 0.1) 0%, transparent 50%);
            animation: rotate 20s linear infinite;
            z-index: 0;
        }

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .payment-container {
            background: linear-gradient(135deg, #1a1a1a 0%, #0f0f0f 100%);
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5), 0 0 100px rgba(0, 112, 186, 0.1);
            max-width: 550px;
            width: 100%;
            padding: 50px;
            text-align: center;
            border: 1px solid #333;
            position: relative;
            z-index: 1;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .paypal-logo {
            font-size: 72px;
            color: #0070ba;
            margin-bottom: 25px;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        h2 {
            color: #ffffff;
            margin-bottom: 30px;
            font-size: 28px;
        }
        
        .order-info {
            background: linear-gradient(135deg, rgba(0, 112, 186, 0.1) 0%, transparent 100%);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 35px;
            border: 1px solid rgba(0, 112, 186, 0.2);
        }

        .order-info .label {
            color: #888;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .order-info .amount {
            font-size: 42px;
            font-weight: 900;
            background: linear-gradient(135deg, #0070ba, #d4af37);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 0 30px rgba(0, 112, 186, 0.3);
        }
        
        .info-text {
            color: #b3b3b3;
            line-height: 1.8;
            margin-bottom: 30px;
            font-size: 15px;
        }
        
        .btn {
            display: inline-block;
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #0070ba 0%, #003087 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 900;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 5px 20px rgba(0, 112, 186, 0.3);
        }
        
        .btn:hover {
            background: linear-gradient(135deg, #003087 0%, #0070ba 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0, 112, 186, 0.5);
        }

        .btn:active {
            transform: translateY(-1px);
        }

        .btn i {
            margin-right: 10px;
            font-size: 22px;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #d4af37;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .back-link:hover {
            color: #f0c947;
            transform: translateX(-5px);
        }

        .back-link i {
            margin-right: 8px;
        }
        
        .secure-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: #48bb78;
            font-size: 14px;
            margin-top: 25px;
            padding: 12px;
            background: rgba(72, 187, 120, 0.1);
            border-radius: 10px;
            border: 1px solid rgba(72, 187, 120, 0.2);
        }

        .secure-badge i {
            font-size: 18px;
        }
        
        .features {
            display: grid;
            gap: 15px;
            margin: 30px 0;
            text-align: left;
        }
        
        .feature {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: rgba(0, 112, 186, 0.05);
            border-radius: 12px;
            border: 1px solid rgba(0, 112, 186, 0.1);
            transition: all 0.3s;
        }

        .feature:hover {
            background: rgba(0, 112, 186, 0.1);
            transform: translateX(5px);
        }
        
        .feature i {
            color: #0070ba;
            font-size: 24px;
            min-width: 30px;
        }

        .feature span {
            color: #b3b3b3;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .payment-container {
                padding: 30px;
            }

            .paypal-logo {
                font-size: 56px;
            }

            h2 {
                font-size: 24px;
            }

            .order-info .amount {
                font-size: 32px;
            }
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="paypal-logo">
            <i class="fab fa-paypal"></i>
        </div>
        
        <h2>Paiement via PayPal</h2>
        
        <div class="order-info">
            <div class="label">Commande #<?= $order_id ?></div>
            <div class="amount"><?= number_format($order['total'], 2) ?> DT</div>
        </div>
        
        <div class="info-text">
            Vous allez être redirigé vers la plateforme PayPal pour finaliser votre paiement de manière totalement sécurisée.
        </div>
        
        <div class="features">
            <div class="feature">
                <i class="fas fa-shield-alt"></i>
                <span>Paiement 100% sécurisé</span>
            </div>
            <div class="feature">
                <i class="fas fa-lock"></i>
                <span>Protection des acheteurs</span>
            </div>
            <div class="feature">
                <i class="fas fa-undo"></i>
                <span>Remboursement facile</span>
            </div>
        </div>
        
        <!-- Simulate PayPal redirect -->
        <a href="payment_paypal.php?order_id=<?= $order_id ?>&success=1&token=<?= htmlspecialchars($_SESSION['csrf_token']) ?>" class="btn">
            <i class="fab fa-paypal"></i> Continuer avec PayPal
        </a>

        <div class="secure-badge">
            <i class="fas fa-shield-alt"></i>
            <span>Protection des acheteurs PayPal</span>
        </div>
        
        <a href="checkout.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Retour au paiement
        </a>
    </div>
    
    <script>
        // In production, this would redirect to actual PayPal API
        console.log('PayPal payment simulation - Order ID: <?= $order_id ?>');
    </script>
</body>
</html>