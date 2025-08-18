<?php
require_once 'config.php';

// Prevent any output before JSON headers
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Clean any previous output
ob_clean();

$method = $_SERVER['REQUEST_METHOD'];
$request = isset($_GET['action']) ? $_GET['action'] : '';


switch ($request) {
    case 'products':
        if ($method === 'GET') {
            getProducts();
        }
        break;
    
    case 'product':
        if ($method === 'GET') {
            getProduct();
        }
        break;
    
    case 'categories':
        if ($method === 'GET') {
            getCategories();
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
        
    case 'contact':
        if ($method === 'POST') {
            submitContact();
        } elseif ($method === 'GET') {
            getContacts();
        } elseif ($method === 'PUT') {
            updateContactStatus();
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
        $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
        
        $query = "SELECT p.*, c.name as category_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.stock_quantity > 0";
        
        if ($categoryId) {
            $query .= " AND p.category_id = :category_id";
        }
        
        $query .= " ORDER BY p.created_at DESC";
        
        $stmt = $pdo->prepare($query);
        
        if ($categoryId) {
            $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($products);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch products']);
    }
}

function getProduct() {
    global $pdo;
    try {
        $productId = isset($_GET['id']) ? (int)$_GET['id'] : null;
        
        if (!$productId) {
            http_response_code(400);
            echo json_encode(['error' => 'Product ID is required']);
            return;
        }
        
        $query = "SELECT p.*, c.name as category_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.id = :product_id";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $stmt->execute();
        
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            echo json_encode($product);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Product not found']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch product']);
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



// Submit a new contact form message
function submitContact() {
    global $pdo;
    
    // Validate required fields
    if (!isset($_POST['name']) || !isset($_POST['email']) || !isset($_POST['subject']) || !isset($_POST['message'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    // Sanitize inputs
    $name = htmlspecialchars(trim($_POST['name']));
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $subject = htmlspecialchars(trim($_POST['subject']));
    $message = htmlspecialchars(trim($_POST['message']));
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        return;
    }
    
    try {
        // Insert contact message into database
        $stmt = $pdo->prepare("INSERT INTO contacts (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $subject, $message]);
        
        echo json_encode(['success' => true, 'message' => 'Your message has been sent successfully']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to send message']);
    }
}

// Get all contact form submissions (admin only)
function getContacts() {
    requireAdmin();
    global $pdo;
    
    try {
        // Get contacts with optional status filter
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        
        if ($status) {
            $stmt = $pdo->prepare("SELECT * FROM contacts WHERE status = ? ORDER BY created_at DESC");
            $stmt->execute([$status]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM contacts ORDER BY created_at DESC");
            $stmt->execute();
        }
        
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get contact statistics
        $statsStmt = $pdo->query("SELECT status, COUNT(*) as count FROM contacts GROUP BY status");
        $statsData = $statsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stats = [
            'total' => 0,
            'new' => 0,
            'read' => 0,
            'replied' => 0,
            'archived' => 0
        ];
        
        foreach ($statsData as $row) {
            $stats[$row['status']] = (int)$row['count'];
        }
        
        $totalStmt = $pdo->query("SELECT COUNT(*) as total FROM contacts");
        $totalRow = $totalStmt->fetch(PDO::FETCH_ASSOC);
        $stats['total'] = (int)$totalRow['total'];
        
        echo json_encode([
            'success' => true, 
            'contacts' => $contacts,
            'stats' => $stats
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to fetch contacts']);
    }
}

// Update contact status (admin only)
function updateContactStatus() {
    requireAdmin();
    global $pdo;
    
    // Parse the PUT request data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['contact_id']) || !isset($data['status'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
    }
    
    $contactId = $data['contact_id'];
    $status = $data['status'];
    
    // Validate status
    $validStatuses = ['new', 'read', 'replied', 'archived'];
    if (!in_array($status, $validStatuses)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        return;
    }
    
    try {
        // Update contact status
        $stmt = $pdo->prepare("UPDATE contacts SET status = ? WHERE id = ?");
        $stmt->execute([$status, $contactId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Contact status updated successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Contact not found']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update contact status']);
    }
}

function getCategories() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($categories);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch categories']);
    }
}

?>
