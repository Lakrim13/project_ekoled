<?php
// test_cart_debug.php - Debugging script for cart functionality
session_start();
require 'config.php';

echo "<h1>Cart System Debug</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} pre{background:#f4f4f4;padding:10px;}</style>";

// 1. Check if user is logged in
echo "<h2>1. Session Check</h2>";
if (isset($_SESSION['user_id'])) {
    echo "<p class='success'>✅ User logged in: ID = " . $_SESSION['user_id'] . "</p>";
    echo "<p>Username: " . ($_SESSION['username'] ?? 'Not set') . "</p>";
    echo "<p>Role: " . ($_SESSION['role'] ?? 'Not set') . "</p>";
    $user_id = intval($_SESSION['user_id']);
} else {
    echo "<p class='error'>❌ No user logged in!</p>";
    echo "<p><a href='login.php'>Go to login</a></p>";
    exit();
}

// 2. Check database connection
echo "<h2>2. Database Connection</h2>";
if ($conn->ping()) {
    echo "<p class='success'>✅ Database connected</p>";
} else {
    echo "<p class='error'>❌ Database connection failed</p>";
    exit();
}

// 3. Check if products exist with stock
echo "<h2>3. Products with Stock</h2>";
$products = $conn->query("SELECT id, name, price, stock FROM products WHERE stock > 0 LIMIT 5");
if ($products && $products->num_rows > 0) {
    echo "<p class='success'>✅ Found " . $products->num_rows . " products with stock:</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Price</th><th>Stock</th><th>Test Add</th></tr>";
    while ($p = $products->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $p['id'] . "</td>";
        echo "<td>" . htmlspecialchars($p['name']) . "</td>";
        echo "<td>" . $p['price'] . " DT</td>";
        echo "<td>" . $p['stock'] . "</td>";
        echo "<td><button onclick='testAddToCart(" . $p['id'] . ")'>Test Add</button></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>❌ No products with stock found!</p>";
}

// 4. Check if cart exists for user
echo "<h2>4. User Cart Status</h2>";
$stmt = $conn->prepare("SELECT id, created_at FROM cart WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $cart = $result->fetch_assoc();
    echo "<p class='success'>✅ Cart exists: ID = " . $cart['id'] . "</p>";
    echo "<p>Created: " . $cart['created_at'] . "</p>";
    $cart_id = $cart['id'];
} else {
    echo "<p class='error'>❌ No cart exists for this user</p>";
    echo "<p class='info'>Cart will be created when first product is added</p>";
    $cart_id = null;
}
$stmt->close();

// 5. Check cart items
echo "<h2>5. Current Cart Items</h2>";
if ($cart_id) {
    $stmt = $conn->prepare("
        SELECT ci.id, ci.quantity, ci.added_at, p.name, p.price, p.stock
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.cart_id = ?
    ");
    $stmt->bind_param("i", $cart_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<p class='success'>✅ Found " . $result->num_rows . " items in cart:</p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Item ID</th><th>Product</th><th>Price</th><th>Quantity</th><th>Stock</th><th>Added</th></tr>";
        while ($item = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $item['id'] . "</td>";
            echo "<td>" . htmlspecialchars($item['name']) . "</td>";
            echo "<td>" . $item['price'] . " DT</td>";
            echo "<td>" . $item['quantity'] . "</td>";
            echo "<td>" . $item['stock'] . "</td>";
            echo "<td>" . $item['added_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='info'>ℹ️ Cart is empty</p>";
    }
    $stmt->close();
} else {
    echo "<p class='info'>ℹ️ No cart to check items for</p>";
}

// 6. Test add_to_list.php file
echo "<h2>6. File Check</h2>";
if (file_exists('add_to_list.php')) {
    echo "<p class='success'>✅ add_to_list.php exists</p>";
} else {
    echo "<p class='error'>❌ add_to_list.php NOT FOUND!</p>";
}

// 7. Check table structure
echo "<h2>7. Database Tables</h2>";
$tables = ['cart', 'cart_items', 'products', 'users'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "<p class='success'>✅ Table '$table' exists</p>";
        
        // Show columns
        $cols = $conn->query("SHOW COLUMNS FROM $table");
        if ($cols) {
            echo "<details><summary>View columns</summary><pre>";
            while ($col = $cols->fetch_assoc()) {
                echo $col['Field'] . " (" . $col['Type'] . ")\n";
            }
            echo "</pre></details>";
        }
    } else {
        echo "<p class='error'>❌ Table '$table' NOT FOUND!</p>";
    }
}

?>

<h2>8. Manual Test</h2>
<p>Click button to test adding product to cart:</p>
<div id="testResult"></div>

<script>
function testAddToCart(productId) {
    console.log('Testing add to cart for product ID:', productId);
    
    fetch('add_to_list.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId + '&quantity=1'
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        return response.text();
    })
    .then(text => {
        console.log('Raw response:', text);
        document.getElementById('testResult').innerHTML = 
            '<h3>Response from add_to_list.php:</h3><pre>' + text + '</pre>';
        
        // Try to parse as JSON
        try {
            const data = JSON.parse(text);
            console.log('Parsed JSON:', data);
            if (data.success) {
                alert('✅ Success! Product added to cart.');
                location.reload();
            } else {
                alert('❌ Error: ' + (data.message || 'Unknown error'));
            }
        } catch (e) {
            console.error('JSON parse error:', e);
            alert('⚠️ Response is not valid JSON. Check console for details.');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('❌ Network error: ' + error.message);
    });
}

// Log current session info
console.log('Session User ID:', <?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null' ?>);
console.log('Page loaded, cart debug ready');
</script>

<h2>9. Quick Links</h2>
<ul>
    <li><a href="cart.php">View Cart</a></li>
    <li><a href="client_dashboard.php">Dashboard</a></li>
    <li><a href="checkout.php">Checkout</a></li>
    <li><a href="logout.php">Logout</a></li>
</ul>

<h2>10. Browser Console Instructions</h2>
<div style="background: #fffacd; padding: 15px; border-left: 4px solid #ffa500;">
    <p><strong>Open Browser Console (F12):</strong></p>
    <ol>
        <li>Press F12 to open Developer Tools</li>
        <li>Go to "Console" tab</li>
        <li>Click a "Test Add" button above</li>
        <li>Check for any errors in the console</li>
        <li>Check "Network" tab for the add_to_list.php request</li>
    </ol>
</div>
