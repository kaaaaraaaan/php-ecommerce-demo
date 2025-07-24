<?php
require_once 'config.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'add_product':
            requireAdmin();
            addProduct();
            break;
        case 'delete_product':
            requireAdmin();
            deleteProduct();
            break;
        case 'update_product':
            requireAdmin();
            updateProduct();
            break;
        case 'get_products':
            requireAdmin();
            getAllProducts();
            break;
    }
    exit;
}

// Check admin access for page view
if (!isLoggedIn() || !isAdmin()) {
    header('Location: index.html');
    exit;
}

function addProduct() {
    global $pdo;
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['name']) || !isset($data['price'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Name and price required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, image_url, stock_quantity) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['name'],
            $data['description'] ?? '',
            $data['price'],
            $data['image_url'] ?? '',
            $data['stock_quantity'] ?? 0
        ]);
        
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add product']);
    }
}

function deleteProduct() {
    global $pdo;
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Product ID required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$data['id']]);
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete product']);
    }
}

function updateProduct() {
    global $pdo;
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Product ID required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, image_url = ?, stock_quantity = ? WHERE id = ?");
        $stmt->execute([
            $data['name'],
            $data['description'],
            $data['price'],
            $data['image_url'],
            $data['stock_quantity'],
            $data['id']
        ]);
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update product']);
    }
}

function getAllProducts() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($products);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch products']);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - TechMart</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-attachment: fixed;
            min-height: 100vh;
            color: #1a1a1a;
        }

        .header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(45deg, #fff, #e0e7ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .header h1::before {
            content: '🛒';
            margin-right: 0.5rem;
            -webkit-text-fill-color: white;
        }

        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
        }

        .form-group input, .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e1e1;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: transform 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .product-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .product-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 5px;
            display: none;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Tab Navigation Styles */
        .tab-navigation {
            display: flex;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 0.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .tab-btn {
            flex: 1;
            background: transparent;
            color: rgba(255, 255, 255, 0.7);
            border: none;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .tab-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.9);
        }

        .tab-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .tab-content {
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Orders Management Styles */
        .orders-section {
            margin-top: 1rem;
        }

        /* Users Management Styles */
        .users-section {
            margin-top: 1rem;
        }

        .users-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .users-table-container {
            overflow-x: auto;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
        }

        .users-table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
        }

        .users-table td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }

        .users-table tr:hover {
            background-color: #f8f9fa;
        }

        .role-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .role-admin {
            background-color: #ff6b6b;
            color: white;
        }

        .role-user {
            background-color: #51cf66;
            color: white;
        }

        .role-select {
            padding: 0.25rem 0.5rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.85rem;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #ff5252, #d84315);
        }

        .orders-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .orders-table-container {
            overflow-x: auto;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
        }

        .orders-table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
        }

        .orders-table td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }

        .orders-table tr:hover {
            background-color: #f8f9fa;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: capitalize;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-processing {
            background-color: #cce5ff;
            color: #004085;
        }

        .status-shipped {
            background-color: #d4edda;
            color: #155724;
        }

        .status-delivered {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-select {
            padding: 0.25rem 0.5rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.85rem;
        }

        .btn-small {
            padding: 0.25rem 0.75rem;
            font-size: 0.85rem;
            border-radius: 6px;
            margin-left: 0.5rem;
        }

        .items-cell {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        @media (max-width: 768px) {
            .orders-stats {
                grid-template-columns: 1fr;
            }
            
            .orders-table-container {
                font-size: 0.85rem;
            }
            
            .orders-table th,
            .orders-table td {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>TechMart Admin</h1>
        <button class="logout-btn" onclick="logout()">Logout</button>
    </div>

    <div class="container">
        <div class="alert alert-success" id="successAlert"></div>
        <div class="alert alert-error" id="errorAlert"></div>

        <!-- Tab Navigation -->
        <div class="tab-navigation">
            <button class="tab-btn active" onclick="switchTab('products')">
                <i class="fas fa-box"></i> Product Management
            </button>
            <button class="tab-btn" onclick="switchTab('orders')">
                <i class="fas fa-shopping-cart"></i> Orders
            </button>
            <button class="tab-btn" onclick="switchTab('users')">
                <i class="fas fa-users"></i> User Management
            </button>
        </div>

        <!-- Products Tab -->
        <div class="tab-content" id="products-tab">
            <div class="card">
                <h2><i class="fas fa-plus-circle"></i> Add New Product</h2>
            <form id="productForm">
                <div class="form-group">
                    <label for="name">Product Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="price">Price ($)</label>
                    <input type="number" id="price" name="price" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="image_url">Image URL</label>
                    <input type="url" id="image_url" name="image_url">
                </div>
                <div class="form-group">
                    <label for="stock_quantity">Stock Quantity</label>
                    <input type="number" id="stock_quantity" name="stock_quantity" min="0" value="0">
                </div>
                <button type="submit" class="btn">Add Product</button>
            </form>
        </div>

            <div class="card">
                <h2><i class="fas fa-cogs"></i> Manage Products</h2>
                <div class="products-grid" id="productsGrid">
                    <!-- Products will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Orders Tab -->
        <div class="tab-content" id="orders-tab" style="display: none;">
            <div class="card">
                <h2><i class="fas fa-shopping-cart"></i> Orders Management</h2>
            <div class="orders-section">
                <div class="orders-stats">
                    <div class="stat-card">
                        <div class="stat-number" id="totalOrders">0</div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="pendingOrders">0</div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="totalRevenue">$0</div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                </div>
                <div class="orders-table-container">
                    <table class="orders-table" id="ordersTable">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="ordersTableBody">
                            <!-- Orders will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Users Tab -->
        <div class="tab-content" id="users-tab" style="display: none;">
            <div class="card">
                <h2><i class="fas fa-users"></i> User Management</h2>
                <div class="users-section">
                    <div class="users-stats">
                        <div class="stat-card">
                            <div class="stat-number" id="totalUsers">0</div>
                            <div class="stat-label">Total Users</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" id="adminUsers">0</div>
                            <div class="stat-label">Administrators</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" id="activeUsers">0</div>
                            <div class="stat-label">Active Customers</div>
                        </div>
                    </div>
                    <div class="users-table-container">
                        <table class="users-table" id="usersTable">
                            <thead>
                                <tr>
                                    <th>User ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Orders</th>
                                    <th>Total Spent</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="usersTableBody">
                                <!-- Users will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Product</h2>
            <form id="editProductForm">
                <input type="hidden" id="editId" name="id">
                <div class="form-group">
                    <label for="editName">Product Name</label>
                    <input type="text" id="editName" name="name" required>
                </div>
                <div class="form-group">
                    <label for="editDescription">Description</label>
                    <textarea id="editDescription" name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="editPrice">Price ($)</label>
                    <input type="number" id="editPrice" name="price" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="editImageUrl">Image URL</label>
                    <input type="url" id="editImageUrl" name="image_url">
                </div>
                <div class="form-group">
                    <label for="editStockQuantity">Stock Quantity</label>
                    <input type="number" id="editStockQuantity" name="stock_quantity" min="0">
                </div>
                <button type="submit" class="btn">Update Product</button>
            </form>
        </div>
    </div>

    <script>
        // Tab switching functionality
        function switchTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(tab => {
                tab.style.display = 'none';
            });
            
            // Remove active class from all tab buttons
            const tabBtns = document.querySelectorAll('.tab-btn');
            tabBtns.forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName + '-tab').style.display = 'block';
            
            // Add active class to clicked tab button
            event.target.classList.add('active');
            
            // Load data for the selected tab
            if (tabName === 'products') {
                loadProducts();
            } else if (tabName === 'orders') {
                loadOrders();
            } else if (tabName === 'users') {
                loadUsers();
            }
        }

        // Load data when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadProducts();
        });

        // Add product form submission
        document.getElementById('productForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const productData = Object.fromEntries(formData);
            
            try {
                const response = await fetch('admin.php?action=add_product', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(productData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Product added successfully!', 'success');
                    e.target.reset();
                    loadProducts();
                } else {
                    showAlert(result.error || 'Failed to add product', 'error');
                }
            } catch (error) {
                showAlert('Error adding product', 'error');
            }
        });

        // Edit product form submission
        document.getElementById('editProductForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const productData = Object.fromEntries(formData);
            
            try {
                const response = await fetch('admin.php?action=update_product', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(productData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Product updated successfully!', 'success');
                    closeEditModal();
                    loadProducts();
                } else {
                    showAlert(result.error || 'Failed to update product', 'error');
                }
            } catch (error) {
                showAlert('Error updating product', 'error');
            }
        });

        async function loadProducts() {
            try {
                const response = await fetch('admin.php?action=get_products', {
                    method: 'POST'
                });
                const products = await response.json();
                
                const grid = document.getElementById('productsGrid');
                grid.innerHTML = '';
                
                products.forEach(product => {
                    const productCard = createProductCard(product);
                    grid.appendChild(productCard);
                });
            } catch (error) {
                showAlert('Error loading products', 'error');
            }
        }

        function createProductCard(product) {
            const card = document.createElement('div');
            card.className = 'product-card';
            card.innerHTML = `
                <img src="${product.image_url || 'https://via.placeholder.com/300x200?text=No+Image'}" 
                     alt="${product.name}" class="product-image">
                <h3>${product.name}</h3>
                <p>${product.description}</p>
                <p><strong>Price: $${parseFloat(product.price).toFixed(2)}</strong></p>
                <p>Stock: ${product.stock_quantity}</p>
                <div class="product-actions">
                    <button class="btn" onclick="editProduct(${product.id})">Edit</button>
                    <button class="btn btn-danger" onclick="deleteProduct(${product.id})">Delete</button>
                </div>
            `;
            return card;
        }

        function editProduct(id) {
            // Find product data
            fetch('admin.php?action=get_products', { method: 'POST' })
                .then(response => response.json())
                .then(products => {
                    const product = products.find(p => p.id == id);
                    if (product) {
                        document.getElementById('editId').value = product.id;
                        document.getElementById('editName').value = product.name;
                        document.getElementById('editDescription').value = product.description;
                        document.getElementById('editPrice').value = product.price;
                        document.getElementById('editImageUrl').value = product.image_url;
                        document.getElementById('editStockQuantity').value = product.stock_quantity;
                        
                        document.getElementById('editModal').style.display = 'block';
                    }
                });
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        async function deleteProduct(id) {
            if (!confirm('Are you sure you want to delete this product?')) {
                return;
            }
            
            try {
                const response = await fetch('admin.php?action=delete_product', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Product deleted successfully!', 'success');
                    loadProducts();
                } else {
                    showAlert(result.error || 'Failed to delete product', 'error');
                }
            } catch (error) {
                showAlert('Error deleting product', 'error');
            }
        }

        function showAlert(message, type) {
            const alertElement = document.getElementById(type === 'success' ? 'successAlert' : 'errorAlert');
            alertElement.textContent = message;
            alertElement.style.display = 'block';
            
            setTimeout(() => {
                alertElement.style.display = 'none';
            }, 5000);
        }

        function showSuccess(message) {
            showAlert(message, 'success');
        }

        function showError(message) {
            showAlert(message, 'error');
        }

        function logout() {
            fetch('api.php?action=logout', { method: 'POST' })
                .then(() => {
                    window.location.href = 'index.html';
                });
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // Orders Management Functions
        async function loadOrders() {
            try {
                const response = await fetch('api.php?action=orders');
                const orders = await response.json();
                
                displayOrders(orders);
                updateOrdersStats(orders);
            } catch (error) {
                showAlert('Error loading orders', 'error');
            }
        }

        function displayOrders(orders) {
            const tbody = document.getElementById('ordersTableBody');
            tbody.innerHTML = '';
            
            if (orders.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 2rem; color: #666;">No orders found</td></tr>';
                return;
            }
            
            orders.forEach(order => {
                const row = document.createElement('tr');
                const orderDate = new Date(order.created_at).toLocaleDateString();
                const orderTime = new Date(order.created_at).toLocaleTimeString();
                
                row.innerHTML = `
                    <td><strong>#${order.id}</strong></td>
                    <td>
                        <div><strong>${order.username}</strong></div>
                        <div style="font-size: 0.85rem; color: #666;">${order.email}</div>
                    </td>
                    <td class="items-cell" title="${order.items}">${order.items || 'No items'}</td>
                    <td><strong>$${parseFloat(order.total_amount).toFixed(2)}</strong></td>
                    <td>
                        <span class="status-badge status-${order.status}">${order.status}</span>
                    </td>
                    <td>
                        <div>${orderDate}</div>
                        <div style="font-size: 0.85rem; color: #666;">${orderTime}</div>
                    </td>
                    <td>
                        <select class="status-select" onchange="updateOrderStatus(${order.id}, this.value)">
                            <option value="pending" ${order.status === 'pending' ? 'selected' : ''}>Pending</option>
                            <option value="processing" ${order.status === 'processing' ? 'selected' : ''}>Processing</option>
                            <option value="shipped" ${order.status === 'shipped' ? 'selected' : ''}>Shipped</option>
                            <option value="delivered" ${order.status === 'delivered' ? 'selected' : ''}>Delivered</option>
                            <option value="cancelled" ${order.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                        </select>
                    </td>
                `;
                
                tbody.appendChild(row);
            });
        }

        function updateOrdersStats(orders) {
            const totalOrders = orders.length;
            const pendingOrders = orders.filter(order => order.status === 'pending').length;
            const totalRevenue = orders.reduce((sum, order) => sum + parseFloat(order.total_amount), 0);
            
            document.getElementById('totalOrders').textContent = totalOrders;
            document.getElementById('pendingOrders').textContent = pendingOrders;
            document.getElementById('totalRevenue').textContent = `$${totalRevenue.toFixed(2)}`;
        }

        async function updateOrderStatus(orderId, newStatus) {
            try {
                const response = await fetch('api.php?action=orders', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        order_id: orderId,
                        status: newStatus
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert(`Order #${orderId} status updated to ${newStatus}`, 'success');
                    loadOrders(); // Reload orders to update stats
                } else {
                    showAlert(result.error || 'Failed to update order status', 'error');
                    loadOrders(); // Reload to reset the select value
                }
            } catch (error) {
                showAlert('Error updating order status', 'error');
                loadOrders(); // Reload to reset the select value
            }
        }

        // User Management Functions
        async function loadUsers() {
            try {
                const response = await fetch('api.php?action=users');
                const users = await response.json();
                
                if (response.ok) {
                    displayUsers(users);
                    updateUserStats(users);
                } else {
                    showAlert('Failed to load users: ' + users.error, 'error');
                }
            } catch (error) {
                showAlert('Error loading users: ' + error.message, 'error');
            }
        }

        function displayUsers(users) {
            const tbody = document.getElementById('usersTableBody');
            tbody.innerHTML = '';
            
            if (users.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 2rem; color: #666;">No users found</td></tr>';
                return;
            }
            
            users.forEach(user => {
                const row = document.createElement('tr');
                const joinDate = new Date(user.created_at).toLocaleDateString();
                const roleClass = user.is_admin == 1 ? 'role-admin' : 'role-user';
                const roleName = user.is_admin == 1 ? 'Admin' : 'User';
                
                row.innerHTML = `
                    <td><strong>#${user.id}</strong></td>
                    <td>${user.username}</td>
                    <td>${user.email}</td>
                    <td><span class="role-badge ${roleClass}">${roleName}</span></td>
                    <td>${user.total_orders}</td>
                    <td>$${parseFloat(user.total_spent).toFixed(2)}</td>
                    <td>${joinDate}</td>
                    <td>
                        <select class="role-select" onchange="updateUserRole(${user.id}, this.value)">
                            <option value="0" ${user.is_admin == 0 ? 'selected' : ''}>User</option>
                            <option value="1" ${user.is_admin == 1 ? 'selected' : ''}>Admin</option>
                        </select>
                        <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id}, '${user.username}')" 
                                style="margin-left: 0.5rem; padding: 0.25rem 0.5rem;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        function updateUserStats(users) {
            const totalUsers = users.length;
            const adminUsers = users.filter(user => user.is_admin == 1).length;
            const activeUsers = users.filter(user => user.total_orders > 0).length;
            
            document.getElementById('totalUsers').textContent = totalUsers;
            document.getElementById('adminUsers').textContent = adminUsers;
            document.getElementById('activeUsers').textContent = activeUsers;
        }

        async function updateUserRole(userId, isAdmin) {
            try {
                const response = await fetch('api.php?action=users', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        is_admin: isAdmin == 1
                    })
                });
                
                const result = await response.json();
                
                if (response.ok) {
                    showAlert('User role updated successfully!', 'success');
                    loadUsers();
                } else {
                    showAlert('Failed to update user role: ' + result.error, 'error');
                    loadUsers(); // Reload to reset the dropdown
                }
            } catch (error) {
                showAlert('Error updating user role: ' + error.message, 'error');
                loadUsers();
            }
        }

        async function deleteUser(userId, username) {
            if (!confirm(`Are you sure you want to delete user "${username}"? This action cannot be undone.`)) {
                return;
            }
            
            try {
                const response = await fetch('api.php?action=users', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: userId
                    })
                });
                
                const result = await response.json();
                
                if (response.ok) {
                    showAlert('User deleted successfully!', 'success');
                    loadUsers();
                } else {
                    showAlert('Failed to delete user: ' + result.error, 'error');
                }
            } catch (error) {
                showAlert('Error deleting user: ' + error.message, 'error');
            }
        }
    </script>
</body>
</html>
