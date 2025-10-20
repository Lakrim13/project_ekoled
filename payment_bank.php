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

// Process bank confirmation
if (isset($_GET['confirmed']) && $_GET['confirmed'] == '1') {
    // CSRF Token Validation
    if (!isset($_GET['token']) || !validateCSRF($_GET['token'])) {
        $_SESSION['error'] = "Token de sécurité invalide!";
        header("Location: cart.php");
        exit();
    }
    
    // Update order status to pending (waiting for bank transfer)
    $stmt = $conn->prepare("UPDATE orders SET status = 'pending_payment', payment_method = 'bank' WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $stmt->close();
    
    $_SESSION['success'] = "Commande enregistrée! Veuillez effectuer le virement bancaire.";
    header("Location: order_success.php?order_id=" . $order_id);
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virement Bancaire - EKOLED</title>
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

        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 30% 40%, rgba(212, 175, 55, 0.15) 0%, transparent 50%),
                        radial-gradient(circle at 70% 70%, rgba(108, 117, 125, 0.1) 0%, transparent 50%);
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
            max-width: 650px;
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

        .bank-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            box-shadow: 0 10px 30px rgba(108, 117, 125, 0.3);
        }

        .bank-icon i {
            font-size: 40px;
            color: #ffffff;
        }
        
        h2 {
            text-align: center;
            color: #ffffff;
            margin-bottom: 30px;
            font-size: 28px;
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

        .bank-details {
            background: rgba(108, 117, 125, 0.05);
            border: 1px solid rgba(108, 117, 125, 0.2);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }

        .bank-details h3 {
            color: #d4af37;
            font-size: 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .detail-label {
            color: #888;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-value {
            color: #ffffff;
            font-weight: 700;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .copy-btn {
            background: rgba(212, 175, 55, 0.2);
            border: 1px solid #d4af37;
            color: #d4af37;
            padding: 5px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
        }

        .copy-btn:hover {
            background: rgba(212, 175, 55, 0.3);
            transform: scale(1.05);
        }

        .instructions {
            background: rgba(0, 112, 186, 0.05);
            border: 1px solid rgba(0, 112, 186, 0.2);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .instructions h3 {
            color: #ffffff;
            font-size: 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .instructions ol {
            padding-left: 20px;
            color: #b3b3b3;
            line-height: 2;
        }

        .instructions li {
            margin-bottom: 10px;
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
            text-decoration: none;
            display: block;
            text-align: center;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(212, 175, 55, 0.5);
            background: linear-gradient(135deg, #f0c947 0%, #d4af37 100%);
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

        .note {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.3);
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            color: #ffc107;
            font-size: 13px;
            text-align: center;
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

            .detail-row {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="bank-icon">
            <i class="fas fa-university"></i>
        </div>
        
        <h2>Virement Bancaire</h2>
        
        <div class="order-info">
            <div class="label">Commande #<?= $order_id ?></div>
            <div class="amount"><?= number_format($order['total'], 2) ?> DT</div>
        </div>

        <div class="bank-details">
            <h3>
                <i class="fas fa-building"></i>
                Coordonnées Bancaires
            </h3>

            <div class="detail-row">
                <span class="detail-label">Banque</span>
                <span class="detail-value">
                    Banque Internationale Arabe de Tunisie (BIAT)
                </span>
            </div>

            <div class="detail-row">
                <span class="detail-label">RIB</span>
                <span class="detail-value">
                    08 000 0123456789012345
                    <button class="copy-btn" onclick="copyToClipboard('08 000 0123456789012345')">
                        <i class="fas fa-copy"></i>
                    </button>
                </span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Titulaire</span>
                <span class="detail-value">EKOLED SARL</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Référence</span>
                <span class="detail-value">
                    CMD-<?= $order_id ?>
                    <button class="copy-btn" onclick="copyToClipboard('CMD-<?= $order_id ?>')">
                        <i class="fas fa-copy"></i>
                    </button>
                </span>
            </div>
        </div>

        <div class="instructions">
            <h3>
                <i class="fas fa-info-circle"></i>
                Instructions
            </h3>
            <ol>
                <li>Effectuez un virement de <strong><?= number_format($order['total'], 2) ?> DT</strong> vers le compte ci-dessus</li>
                <li>Indiquez obligatoirement la référence <strong>CMD-<?= $order_id ?></strong> dans le libellé</li>
                <li>Votre commande sera traitée après réception du paiement (24-48h)</li>
                <li>Vous recevrez une confirmation par email</li>
            </ol>
        </div>

        <a href="payment_bank.php?order_id=<?= $order_id ?>&confirmed=1&token=<?= htmlspecialchars($_SESSION['csrf_token']) ?>" class="btn">
            <i class="fas fa-check"></i> J'ai effectué le virement
        </a>

        <div class="note">
            <i class="fas fa-exclamation-triangle"></i>
            Conservez votre reçu de virement comme preuve de paiement
        </div>
        
        <a href="checkout.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Retour au paiement
        </a>
    </div>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                // Show a temporary notification
                const btn = event.target.closest('.copy-btn');
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i>';
                btn.style.background = 'rgba(72, 187, 120, 0.3)';
                btn.style.borderColor = '#48bb78';
                btn.style.color = '#48bb78';
                
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.style.background = '';
                    btn.style.borderColor = '';
                    btn.style.color = '';
                }, 2000);
            });
        }
    </script>
</body>
</html>
