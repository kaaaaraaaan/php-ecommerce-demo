<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];
$request = isset($_GET['action']) ? $_GET['action'] : '';

switch ($request) {
    case 'products':
        if ($method === 'GET') {
            getProducts();
        }
        break;
    
    case 'login':
        if ($method === 'POST') {
            login();
        }
        break;
    
    case 'register':
        if ($method === 'POST') {
            register();
        }
        break;
    
    case 'logout':
        if ($method === 'POST') {
            logout();
        }
        break;
    
    case 'cart':
        if ($method === 'GET') {
            getCart();
        } elseif ($method === 'POST') {
            addToCart();
        } elseif ($method === 'DELETE') {
            removeFromCart();
        }
        break;
    
    case 'checkout':
        if ($method === 'POST') {
            checkout();
        }
        break;
    
    case 'user':
        if ($method === 'GET') {
            getCurrentUser();
        }
        break;
    
    case 'orders':
        if ($method === 'GET') {
            getOrders();
        } elseif ($method === 'PUT') {
            updateOrderStatus();
        }
        break;
    
    case 'users':
        if ($method === 'GET') {
            getUsers();
        } elseif ($method === 'DELETE') {
            deleteUser();
        } elseif ($method === 'PUT') {
            updateUserRole();
        }
        break;
    
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
}

function getProducts() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM products WHERE stock_quantity > 0 ORDER BY created_at DESC");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($products);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch products']);
    }
}

function login() {
    global $pdo;
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['username']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Username and password required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id, username, password, is_admin FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$data['username'], $data['username']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if password matches - either with password_verify or direct comparison for admin
        $passwordMatches = password_verify($data['password'], $user['password']) || 
                          ($user['username'] === 'admin' && $data['password'] === $user['password']);
        
        if ($user && $passwordMatches) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'];
            
            echo json_encode([
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'is_admin' => $user['is_admin']
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Login failed']);
    }
}

function register() {
    global $pdo;
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Username, email, and password required']);
        return;
    }
    
    try {
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$data['username'], $data['email'], $hashedPassword]);
        
        echo json_encode(['success' => true, 'message' => 'User registered successfully']);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            http_response_code(409);
            echo json_encode(['error' => 'Username or email already exists']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Registration failed']);
        }
    }
}

function logout() {
    session_destroy();
    echo json_encode(['success' => true]);
}

function addToCart() {
    requireLogin();
    global $pdo;
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['product_id']) || !isset($data['quantity'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Product ID and quantity required']);
        return;
    }
    
    try {
        // Check if item already in cart
        $stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['user_id'], $data['product_id']]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update quantity
            $stmt = $pdo->prepare("UPDATE cart SET quantity = quantity + ? WHERE id = ?");
            $stmt->execute([$data['quantity'], $existing['id']]);
        } else {
            // Add new item
            $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $data['product_id'], $data['quantity']]);
        }
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add to cart']);
    }
}

function getCart() {
    requireLogin();
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT c.id, c.quantity, p.name, p.price, p.image_url, (c.quantity * p.price) as total
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($cartItems);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch cart']);
    }
}

function removeFromCart() {
    requireLogin();
    global $pdo;
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['cart_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Cart ID required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$data['cart_id'], $_SESSION['user_id']]);
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to remove from cart']);
    }
}

function checkout() {
    requireLogin();
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Get cart items
        $stmt = $pdo->prepare("
            SELECT c.product_id, c.quantity, p.price 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($cartItems)) {
            http_response_code(400);
            echo json_encode(['error' => 'Cart is empty']);
            return;
        }
        
        // Calculate total
        $total = 0;
        foreach ($cartItems as $item) {
            $total += $item['quantity'] * $item['price'];
        }
        
        // Create order
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $total]);
        $orderId = $pdo->lastInsertId();
        
        // Add order items
        foreach ($cartItems as $item) {
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
        }
        
        // Clear cart
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'order_id' => $orderId]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Checkout failed']);
    }
}

function getCurrentUser() {
    if (isLoggedIn()) {
        echo json_encode([
            'logged_in' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'is_admin' => $_SESSION['is_admin'] ?? false
            ]
        ]);
    } else {
        echo json_encode(['logged_in' => false]);
    }
}

function getOrders() {
    requireAdmin();
    global $pdo;
    
    try {
        $stmt = $pdo->query("
            SELECT 
                o.id,
                o.user_id,
                u.username,
                u.email,
                o.total_amount,
                o.status,
                o.created_at,
                GROUP_CONCAT(
                    CONCAT(p.name, ' (', oi.quantity, 'x @$', oi.price, ')') 
                    SEPARATOR ', '
                ) as items
            FROM orders o
            JOIN users u ON o.user_id = u.id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.id
            GROUP BY o.id
            ORDER BY o.created_at DESC
        ");
        
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($orders);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch orders']);
    }
}

function updateOrderStatus() {
    requireAdmin();
    global $pdo;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['order_id']) || !isset($data['status'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Order ID and status required']);
        return;
    }
    
    $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (!in_array($data['status'], $validStatuses)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid status']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$data['status'], $data['order_id']]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Order not found']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update order status']);
    }
}

function getUsers() {
    requireAdmin();
    global $pdo;
    
    try {
        $stmt = $pdo->query("
            SELECT 
                u.id,
                u.username,
                u.email,
                u.is_admin,
                u.created_at,
                COUNT(o.id) as total_orders,
                COALESCE(SUM(o.total_amount), 0) as total_spent
            FROM users u
            LEFT JOIN orders o ON u.id = o.user_id
            GROUP BY u.id
            ORDER BY u.created_at DESC
        ");
        
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($users);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch users']);
    }
}

function deleteUser() {
    requireAdmin();
    global $pdo;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['user_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID required']);
        return;
    }
    
    // Prevent deleting the current admin user
    if ($data['user_id'] == $_SESSION['user_id']) {
        http_response_code(400);
        echo json_encode(['error' => 'Cannot delete your own account']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$data['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete user']);
    }
}

function updateUserRole() {
    requireAdmin();
    global $pdo;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['user_id']) || !isset($data['is_admin'])) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID and admin status required']);
        return;
    }
    
    // Prevent removing admin role from current user
    if ($data['user_id'] == $_SESSION['user_id'] && !$data['is_admin']) {
        http_response_code(400);
        echo json_encode(['error' => 'Cannot remove admin role from your own account']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
        $stmt->execute([$data['is_admin'] ? 1 : 0, $data['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update user role']);
    }
}
?>
