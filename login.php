<?php
session_start();
require 'config.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: profile.php');
    } else {
        header('Location: client_dashboard.php');
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($email && $password) {
        $stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                if ($user['role'] === 'admin') {
                    header('Location: profile.php');
                } else {
                    header('Location: client_dashboard.php');
                }
                exit();
            }
        }
        $error = "Email ou mot de passe incorrect";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - EKOLED</title>
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
            --bg-card: rgba(26, 26, 26, 0.8);
            --text-primary: #ffffff;
            --text-secondary: #b3b3b3;
            --accent-gold: #d4af37;
            --accent-gold-hover: #f0c947;
            --border-color: rgba(212, 175, 55, 0.3);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-dark);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
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
        
        /* Floating Particles */
        .particle {
            position: fixed;
            width: 3px;
            height: 3px;
            background: var(--accent-gold);
            border-radius: 50%;
            opacity: 0.3;
            animation: float 15s infinite ease-in-out;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0) translateX(0); }
            50% { transform: translateY(-100px) translateX(50px); }
        }
        
        .particle:nth-child(1) { top: 20%; left: 20%; animation-delay: 0s; }
        .particle:nth-child(2) { top: 40%; left: 80%; animation-delay: 2s; }
        .particle:nth-child(3) { top: 60%; left: 30%; animation-delay: 4s; }
        .particle:nth-child(4) { top: 80%; left: 70%; animation-delay: 6s; }
        .particle:nth-child(5) { top: 30%; left: 50%; animation-delay: 8s; }
        
        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }
        
        /* Glassmorphism Card */
        .login-card {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 50px 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
            animation: fadeInUp 0.8s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .logo {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo-text {
            font-size: 48px;
            font-weight: 900;
            color: var(--accent-gold);
            letter-spacing: 4px;
            text-transform: uppercase;
            margin-bottom: 10px;
            text-shadow: 0 0 30px rgba(212,175,55,0.5);
        }
        
        .logo-subtitle {
            font-size: 14px;
            color: var(--text-secondary);
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        
        h2 {
            text-align: center;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 30px;
            color: var(--text-primary);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 10px;
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--accent-gold);
            font-size: 18px;
        }
        
        input {
            width: 100%;
            padding: 16px 20px 16px 50px;
            background: rgba(0,0,0,0.4);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 15px;
            transition: all 0.3s ease;
        }
        
        input:focus {
            outline: none;
            border-color: var(--accent-gold);
            background: rgba(0,0,0,0.6);
            box-shadow: 0 0 20px rgba(212,175,55,0.2);
        }
        
        input::placeholder {
            color: var(--text-secondary);
        }
        
        .btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--accent-gold) 0%, var(--accent-gold-hover) 100%);
            border: none;
            border-radius: 12px;
            color: var(--bg-darker);
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(212,175,55,0.3);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(212,175,55,0.5);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.5);
            color: #fca5a5;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: center;
            font-size: 14px;
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .register-link {
            text-align: center;
            margin-top: 30px;
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        .register-link a {
            color: var(--accent-gold);
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
        }
        
        .register-link a:hover {
            color: var(--accent-gold-hover);
            text-shadow: 0 0 15px rgba(212,175,55,0.5);
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 30px 0;
            color: var(--text-secondary);
            font-size: 12px;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border-color);
        }
        
        .divider span {
            padding: 0 15px;
        }
        
        .back-home {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-home a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .back-home a:hover {
            color: var(--accent-gold);
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .login-card {
                padding: 30px 25px;
            }
            
            .logo-text {
                font-size: 36px;
            }
            
            h2 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <!-- Particles -->
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>

    <div class="login-container">
        <div class="login-card">
            <div class="logo">
                <div class="logo-text">EKOLED</div>
                <div class="logo-subtitle">Éclairage Premium</div>
            </div>
            
            <h2>Bienvenue</h2>
            
            <?php if($error): ?>
                <div class="error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="votre@email.com" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="••••••••" required>
                    </div>
                </div>
                
                <button type="submit" class="btn">
                    <i class="fas fa-sign-in-alt"></i> Connexion
                </button>
            </form>
            
            <div class="divider">
                <span>OU</span>
            </div>
            
            <div class="register-link">
                Vous n'avez pas de compte ? <a href="register.php">Créer un compte</a>
            </div>
            
            <div class="back-home">
                <a href="client_dashboard.php">
                    <i class="fas fa-arrow-left"></i> Retour à l'accueil
                </a>
            </div>
        </div>
    </div>
</body>
</html>
