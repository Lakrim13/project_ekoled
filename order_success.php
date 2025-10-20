<?php
session_start();
require 'config.php';

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$order_details = null;

if ($order_id > 0 && isset($_SESSION['user_id'])) {
    $user_id = intval($_SESSION['user_id']);
    $stmt = $conn->prepare("
        SELECT o.*, 
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
        FROM orders o 
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order_details = $result->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande Confirmée - EKOLED</title>
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
            background: radial-gradient(circle at 50% 50%, rgba(72, 187, 120, 0.15) 0%, transparent 50%),
                        radial-gradient(circle at 80% 20%, rgba(212, 175, 55, 0.1) 0%, transparent 50%);
            animation: rotate 20s linear infinite;
            z-index: 0;
        }

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .success-container {
            background: linear-gradient(135deg, #1a1a1a 0%, #0f0f0f 100%);
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5), 0 0 100px rgba(72, 187, 120, 0.1);
            max-width: 600px;
            width: 100%;
            padding: 50px;
            text-align: center;
            border: 1px solid #333;
            position: relative;
            z-index: 1;
            animation: slideUp 0.6s ease-out;
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

        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: scaleIn 0.5s ease-out 0.2s both;
            box-shadow: 0 10px 40px rgba(72, 187, 120, 0.3);
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        .success-icon i {
            font-size: 50px;
            color: #ffffff;
        }

        h1 {
            color: #48bb78;
            font-size: 32px;
            margin-bottom: 15px;
            animation: fadeIn 0.5s ease-out 0.4s both;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .success-message {
            color: #b3b3b3;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 35px;
            animation: fadeIn 0.5s ease-out 0.5s both;
        }

        .order-summary {
            background: rgba(72, 187, 120, 0.05);
            border: 1px solid rgba(72, 187, 120, 0.2);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 35px;
            animation: fadeIn 0.5s ease-out 0.6s both;
        }

        .order-summary h3 {
            color: #d4af37;
            font-size: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .order-info-grid {
            display: grid;
            gap: 15px;
            text-align: left;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #888;
            font-size: 14px;
        }

        .info-value {
            color: #ffffff;
            font-weight: 600;
            font-size: 14px;
        }

        .total-row {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid rgba(212, 175, 55, 0.3);
        }

        .total-row .info-value {
            color: #d4af37;
            font-size: 24px;
            font-weight: 900;
        }

        .action-buttons {
            display: grid;
            gap: 15px;
            animation: fadeIn 0.5s ease-out 0.7s both;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #d4af37 0%, #f0c947 100%);
            color: #000000;
            box-shadow: 0 5px 20px rgba(212, 175, 55, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(212, 175, 55, 0.5);
        }

        .btn-secondary {
            background: transparent;
            color: #b3b3b3;
            border: 2px solid #333;
        }

        .btn-secondary:hover {
            border-color: #d4af37;
            color: #d4af37;
            transform: translateY(-2px);
        }

        .thank-you {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #333;
            color: #666;
            font-size: 14px;
            animation: fadeIn 0.5s ease-out 0.8s both;
        }

        @media (max-width: 768px) {
            .success-container {
                padding: 30px;
            }

            h1 {
                font-size: 26px;
            }

            .success-icon {
                width: 80px;
                height: 80px;
            }

            .success-icon i {
                font-size: 40px;
            }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>
        
        <h1>Commande Confirmée !</h1>
        
        <p class="success-message">
            Merci pour votre confiance ! Votre commande a été enregistrée avec succès.<br>
            Nous la traitons dans les plus brefs délais.
        </p>

        <?php if ($order_details): ?>
        <div class="order-summary">
            <h3>
                <i class="fas fa-receipt"></i>
                Détails de la commande
            </h3>
            
            <div class="order-info-grid">
                <div class="info-row">
                    <span class="info-label">Numéro de commande</span>
                    <span class="info-value">#<?= $order_id ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Client</span>
                    <span class="info-value"><?= htmlspecialchars($order_details['customer_name']) ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Téléphone</span>
                    <span class="info-value"><?= htmlspecialchars($order_details['customer_phone']) ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Méthode de paiement</span>
                    <span class="info-value">
                        <?php
                        $methods = [
                            'cod' => 'Paiement à la livraison',
                            'card' => 'Carte bancaire',
                            'paypal' => 'PayPal',
                            'bank' => 'Virement bancaire'
                        ];
                        echo $methods[$order_details['payment_method']] ?? 'N/A';
                        ?>
                    </span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Articles</span>
                    <span class="info-value"><?= $order_details['item_count'] ?> produit(s)</span>
                </div>
                
                <div class="info-row total-row">
                    <span class="info-label">Total</span>
                    <span class="info-value"><?= number_format($order_details['total'], 2) ?> DT</span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="action-buttons">
            <a href="client_dashboard.php" class="btn btn-primary">
                <i class="fas fa-home"></i>
                Retour à l'accueil
            </a>
            
            <a href="cart.php" class="btn btn-secondary">
                <i class="fas fa-shopping-cart"></i>
                Voir mon panier
            </a>
        </div>

        <div class="thank-you">
            <i class="fas fa-heart" style="color: #d4af37;"></i>
            Merci de faire vos achats sur EKOLED
        </div>
    </div>
</body>
</html>
