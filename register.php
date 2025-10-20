<?php
session_start();
require 'config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: client_dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $role = 'client'; // All new users are clients by default
    
    // Validate password strength
    if (strlen($password) < 8) {
        $error = "Le mot de passe doit contenir au moins 8 caractères";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = "Le mot de passe doit contenir au moins une lettre majuscule";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $error = "Le mot de passe doit contenir au moins une lettre minuscule";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = "Le mot de passe doit contenir au moins un chiffre";
    } elseif ($password !== $confirm) {
        $error = "Les mots de passe ne correspondent pas";
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = "Cet email est déjà utilisé";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $email, $hashed, $role);
            
            if ($stmt->execute()) {
                $success = "Compte créé avec succès! Vous pouvez maintenant vous connecter.";
                header("refresh:2;url=login.php");
            } else {
                $error = "Erreur lors de la création du compte";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - EKOLED</title>
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
            overflow-x: hidden;
            padding: 40px 0;
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
        
        .register-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 500px;
            padding: 20px;
        }
        
        /* Glassmorphism Card */
        .register-card {
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
            margin-bottom: 35px;
        }
        
        .logo-text {
            font-size: 42px;
            font-weight: 900;
            color: var(--accent-gold);
            letter-spacing: 4px;
            text-transform: uppercase;
            margin-bottom: 8px;
            text-shadow: 0 0 30px rgba(212,175,55,0.5);
        }
        
        .logo-subtitle {
            font-size: 13px;
            color: var(--text-secondary);
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        
        h2 {
            text-align: center;
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 30px;
            color: var(--text-primary);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--accent-gold);
            font-size: 16px;
        }
        
        input, select {
            width: 100%;
            padding: 14px 18px 14px 45px;
            background: rgba(0,0,0,0.4);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23d4af37' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: var(--accent-gold);
            background: rgba(0,0,0,0.6);
            box-shadow: 0 0 20px rgba(212,175,55,0.2);
        }
        
        input::placeholder {
            color: var(--text-secondary);
        }
        
        .password-strength {
            margin-top: 10px;
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        .strength-bar {
            height: 4px;
            background: rgba(255,255,255,0.1);
            border-radius: 2px;
            margin-top: 5px;
            overflow: hidden;
        }
        
        .strength-fill {
            height: 100%;
            width: 0;
            background: var(--accent-gold);
            transition: width 0.3s ease;
        }
        
        .btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--accent-gold) 0%, var(--accent-gold-hover) 100%);
            border: none;
            border-radius: 12px;
            color: var(--bg-darker);
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(212,175,55,0.3);
            margin-top: 10px;
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
            padding: 15px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 13px;
            animation: shake 0.5s ease-in-out;
        }
        
        .success {
            background: rgba(0, 255, 136, 0.2);
            border: 1px solid rgba(0, 255, 136, 0.5);
            color: #6ee7b7;
            padding: 15px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 13px;
            animation: fadeInUp 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .login-link {
            text-align: center;
            margin-top: 25px;
            color: var(--text-secondary);
            font-size: 13px;
        }
        
        .login-link a {
            color: var(--accent-gold);
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
        }
        
        .login-link a:hover {
            color: var(--accent-gold-hover);
            text-shadow: 0 0 15px rgba(212,175,55,0.5);
        }
        
        .back-home {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-home a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 13px;
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
            .register-card {
                padding: 30px 25px;
            }
            
            .logo-text {
                font-size: 34px;
            }
            
            h2 {
                font-size: 22px;
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

    <div class="register-container">
        <div class="register-card">
            <div class="logo">
                <div class="logo-text">EKOLED</div>
                <div class="logo-subtitle">Éclairage Premium</div>
            </div>
            
            <h2>Créer un compte</h2>
            
            <?php if($error): ?>
                <div class="error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Nom d'utilisateur</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" placeholder="Votre nom" required>
                    </div>
                </div>
                
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
                    <div class="password-strength">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strength-fill"></div>
                        </div>
                        <span id="strength-text">Minimum 8 caractères, 1 majuscule, 1 minuscule, 1 chiffre</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmer le mot de passe</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required>
                    </div>
                </div>
                
                <button type="submit" class="btn">
                    <i class="fas fa-user-plus"></i> Créer mon compte
                </button>
            </form>
            
            <div class="login-link">
                Vous avez déjà un compte ? <a href="login.php">Se connecter</a>
            </div>
            
            <div class="back-home">
                <a href="client_dashboard.php">
                    <i class="fas fa-arrow-left"></i> Retour à l'accueil
                </a>
            </div>
        </div>
    </div>
    
    <script>
        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const strengthFill = document.getElementById('strength-fill');
        const strengthText = document.getElementById('strength-text');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            if (password.length >= 8) strength += 25;
            if (/[A-Z]/.test(password)) strength += 25;
            if (/[a-z]/.test(password)) strength += 25;
            if (/[0-9]/.test(password)) strength += 25;
            
            strengthFill.style.width = strength + '%';
            
            if (strength === 0) {
                strengthText.textContent = 'Minimum 8 caractères, 1 majuscule, 1 minuscule, 1 chiffre';
                strengthFill.style.background = '#666';
            } else if (strength === 25) {
                strengthText.textContent = 'Faible';
                strengthFill.style.background = '#ff4444';
            } else if (strength === 50) {
                strengthText.textContent = 'Moyen';
                strengthFill.style.background = '#fbbf24';
            } else if (strength === 75) {
                strengthText.textContent = 'Bon';
                strengthFill.style.background = '#00ff88';
            } else if (strength === 100) {
                strengthText.textContent = 'Excellent!';
                strengthFill.style.background = '#d4af37';
            }
        });
    </script>
</body>
</html>
