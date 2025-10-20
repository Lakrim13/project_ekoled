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

// Process card payment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Token Validation
    if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
        $_SESSION['error'] = "Token de sécurité invalide!";
        header("Location: payment_card.php?order_id=" . $order_id);
        exit();
    }
    
    $card_number = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
    $card_name = trim($_POST['card_name'] ?? '');
    $card_expiry = trim($_POST['card_expiry'] ?? '');
    $card_cvv = trim($_POST['card_cvv'] ?? '');
    
    // Basic validation (in production, use a real payment gateway)
    if (empty($card_number) || strlen($card_number) != 16 || !ctype_digit($card_number)) {
        $_SESSION['error'] = "Numéro de carte invalide";
        header("Location: payment_card.php?order_id=" . $order_id);
        exit();
    }
    
    if (empty($card_name) || strlen($card_name) < 3) {
        $_SESSION['error'] = "Nom sur la carte invalide";
        header("Location: payment_card.php?order_id=" . $order_id);
        exit();
    }
    
    if (empty($card_cvv) || strlen($card_cvv) != 3 || !ctype_digit($card_cvv)) {
        $_SESSION['error'] = "CVV invalide";
        header("Location: payment_card.php?order_id=" . $order_id);
        exit();
    }
    
    // Simulate payment processing
    // In production, integrate with Stripe, PayPal, etc.
    
    // Update order status
    $stmt = $conn->prepare("UPDATE orders SET status = 'paid', payment_method = 'card' WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $stmt->close();
    
    $_SESSION['success'] = "Paiement effectué avec succès!";
    header("Location: order_success.php?order_id=" . $order_id);
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement par Carte - EKOLED</title>
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
            background: radial-gradient(circle at 20% 50%, rgba(212, 175, 55, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 80%, rgba(212, 175, 55, 0.08) 0%, transparent 50%);
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
            box-shadow: 0 20px 60px rgba(0,0,0,0.5), 0 0 100px rgba(212, 175, 55, 0.1);
            max-width: 550px;
            width: 100%;
            padding: 50px;
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
        
        h2 {
            text-align: center;
            color: #d4af37;
            margin-bottom: 35px;
            font-size: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        h2 i {
            font-size: 32px;
        }
        
        .order-info {
            background: linear-gradient(135deg, rgba(212, 175, 55, 0.1) 0%, transparent 100%);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 35px;
            text-align: center;
            border: 1px solid rgba(212, 175, 55, 0.2);
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
            color: #d4af37;
            text-shadow: 0 0 30px rgba(212, 175, 55, 0.3);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 10px;
            color: #b3b3b3;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        label i {
            margin-right: 8px;
            color: #d4af37;
        }
        
        input {
            width: 100%;
            padding: 15px;
            background: #0a0a0a;
            border: 2px solid #333;
            border-radius: 12px;
            font-size: 16px;
            color: #ffffff;
            transition: all 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: #d4af37;
            box-shadow: 0 0 20px rgba(212, 175, 55, 0.2);
        }

        input::placeholder {
            color: #555;
        }
        
        .card-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        
        .btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #d4af37 0%, #f0c947 100%);
            color: #000000;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 900;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 5px 20px rgba(212, 175, 55, 0.3);
            margin-top: 10px;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(212, 175, 55, 0.5);
            background: linear-gradient(135deg, #f0c947 0%, #d4af37 100%);
        }

        .btn:active {
            transform: translateY(-1px);
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 25px;
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
        
        .error {
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.2) 0%, transparent 100%);
            color: #ff6b6b;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: center;
            border: 1px solid rgba(220, 38, 38, 0.3);
            animation: shake 0.5s;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
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

        .card-brands {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
            opacity: 0.6;
        }

        .card-brands i {
            font-size: 32px;
            color: #666;
        }

        @media (max-width: 768px) {
            .payment-container {
                padding: 30px;
            }

            h2 {
                font-size: 24px;
            }

            .order-info .amount {
                font-size: 32px;
            }

            .card-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <h2>
            <i class="fas fa-credit-card"></i>
            Paiement Sécurisé
        </h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <div class="order-info">
            <div class="label">Commande #<?= $order_id ?></div>
            <div class="amount"><?= number_format($order['total'], 2) ?> DT</div>
        </div>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            
            <div class="form-group">
                <label><i class="fas fa-credit-card"></i> Numéro de carte</label>
                <input type="text" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19" required>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-user"></i> Nom sur la carte</label>
                <input type="text" name="card_name" placeholder="JOHN DOE" required>
            </div>
            
            <div class="card-row">
                <div class="form-group">
                    <label><i class="fas fa-calendar"></i> Date d'expiration</label>
                    <input type="text" name="card_expiry" placeholder="MM/AA" maxlength="5" required>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> CVV</label>
                    <input type="text" name="card_cvv" placeholder="123" maxlength="3" required>
                </div>
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-lock"></i> Payer <?= number_format($order['total'], 2) ?> DT
            </button>
        </form>
        
        <div class="secure-badge">
            <i class="fas fa-shield-alt"></i>
            <span>Paiement 100% sécurisé SSL</span>
        </div>

        <div class="card-brands">
            <i class="fab fa-cc-visa"></i>
            <i class="fab fa-cc-mastercard"></i>
            <i class="fab fa-cc-amex"></i>
        </div>
        
        <a href="checkout.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Retour au paiement
        </a>
    </div>
</body>
</html>