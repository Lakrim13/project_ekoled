<?php
// verify_cart_tables.php - Verify and fix cart tables
require 'config.php';

echo "<h1>Vérification et correction des tables du panier</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

// Check if cart table exists
echo "<h2>1. Vérification de la table 'cart'</h2>";
$result = $conn->query("SHOW TABLES LIKE 'cart'");
if ($result->num_rows > 0) {
    echo "<p class='success'>✅ Table 'cart' existe</p>";
    
    // Show structure
    $cols = $conn->query("SHOW COLUMNS FROM cart");
    echo "<h3>Structure actuelle:</h3><ul>";
    while ($col = $cols->fetch_assoc()) {
        echo "<li>" . $col['Field'] . " (" . $col['Type'] . ")</li>";
    }
    echo "</ul>";
} else {
    echo "<p class='error'>❌ Table 'cart' n'existe pas. Création...</p>";
    $sql = "CREATE TABLE cart (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user (user_id)
    )";
    if ($conn->query($sql)) {
        echo "<p class='success'>✅ Table 'cart' créée avec succès</p>";
    } else {
        echo "<p class='error'>❌ Erreur: " . $conn->error . "</p>";
    }
}

// Check if cart_items table exists
echo "<h2>2. Vérification de la table 'cart_items'</h2>";
$result = $conn->query("SHOW TABLES LIKE 'cart_items'");
if ($result->num_rows > 0) {
    echo "<p class='success'>✅ Table 'cart_items' existe</p>";
    
    // Show structure
    $cols = $conn->query("SHOW COLUMNS FROM cart_items");
    echo "<h3>Structure actuelle:</h3><ul>";
    $hasAddedAt = false;
    while ($col = $cols->fetch_assoc()) {
        echo "<li>" . $col['Field'] . " (" . $col['Type'] . ")</li>";
        if ($col['Field'] == 'added_at') {
            $hasAddedAt = true;
        }
    }
    echo "</ul>";
    
    // Check if added_at column exists, if not add it
    if (!$hasAddedAt) {
        echo "<p class='info'>⚠️ Colonne 'added_at' manquante. Ajout...</p>";
        if ($conn->query("ALTER TABLE cart_items ADD COLUMN added_at DATETIME DEFAULT CURRENT_TIMESTAMP")) {
            echo "<p class='success'>✅ Colonne 'added_at' ajoutée</p>";
        } else {
            echo "<p class='error'>❌ Erreur: " . $conn->error . "</p>";
        }
    }
    
} else {
    echo "<p class='error'>❌ Table 'cart_items' n'existe pas. Création...</p>";
    $sql = "CREATE TABLE cart_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cart_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (cart_id) REFERENCES cart(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        INDEX idx_cart (cart_id),
        INDEX idx_product (product_id)
    )";
    if ($conn->query($sql)) {
        echo "<p class='success'>✅ Table 'cart_items' créée avec succès</p>";
    } else {
        echo "<p class='error'>❌ Erreur: " . $conn->error . "</p>";
    }
}

// Test query
echo "<h2>3. Test de requête</h2>";
try {
    $test_user_id = 1; // Change this to your user ID
    $stmt = $conn->prepare("SELECT id FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $test_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    echo "<p class='success'>✅ Requête de test réussie</p>";
    echo "<p>Nombre de paniers trouvés pour user_id=$test_user_id: " . $result->num_rows . "</p>";
    $stmt->close();
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur de test: " . $e->getMessage() . "</p>";
}

echo "<h2>4. Actions</h2>";
echo "<p><a href='test_cart_debug.php'>Aller au test de panier complet</a></p>";
echo "<p><a href='client_dashboard.php'>Retour au tableau de bord</a></p>";
?>
