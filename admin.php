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
        case 'add_category':
            requireAdmin();
            addCategory();
            break;
        case 'delete_category':
            requireAdmin();
            deleteCategory();
            break;
        case 'update_category':
            requireAdmin();
            updateCategory();
            break;
    }
    exit;
}

// Check admin access for page view
if (!isLoggedIn() || !isAdmin()) {
    header('Location: index.html');
    exit;
}

// Helper function for file upload
function handleFileUpload($fileKey, $targetDir = "uploads/products/") {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]["error"] != 0) {
        return '';
    }
    
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileName = basename($_FILES[$fileKey]["name"]);
    $targetFilePath = $targetDir . time() . "_" . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
    $allowTypes = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    
    if (!in_array(strtolower($fileType), $allowTypes)) {
        throw new Exception('Only JPG, JPEG, PNG, GIF, & WEBP files are allowed.');
    }
    
    if (!move_uploaded_file($_FILES[$fileKey]["tmp_name"], $targetFilePath)) {
        throw new Exception('Error uploading file.');
    }
    
    return $targetFilePath;
}

function addProduct() {
    global $pdo;
    
    if (!isset($_POST['name']) || !isset($_POST['price'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Name and price required']);
        return;
    }
    
    try {
        $imageUrl = handleFileUpload('product_image');
        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, image_url, stock_quantity, category_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['name'],
            $_POST['description'] ?? '',
            $_POST['price'],
            $imageUrl,
            $_POST['stock_quantity'] ?? 0,
            $categoryId
        ]);
        
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add product: ' . $e->getMessage()]);
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
    
    if (!isset($_POST['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Product ID required']);
        return;
    }
    
    try {
        $imageUrl = $_POST['current_image_url'] ?? '';
        
        // Only update image if new one is uploaded
        if (isset($_FILES["product_image"]) && $_FILES["product_image"]["error"] == 0) {
            $imageUrl = handleFileUpload('product_image');
        }
        
        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        
        $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, image_url = ?, stock_quantity = ?, category_id = ? WHERE id = ?");
        $stmt->execute([
            $_POST['name'],
            $_POST['description'],
            $_POST['price'],
            $imageUrl,
            $_POST['stock_quantity'],
            $categoryId,
            $_POST['id']
        ]);
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update product: ' . $e->getMessage()]);
    }
}

function getAllProducts() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($products);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch products']);
    }
}

function addCategory() {
    global $pdo;
    
    if (isset($_POST['name'])) {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->execute([
                $_POST['name'],
                $_POST['description'] ?? ''
            ]);
            
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add category: ' . $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Category name required']);
    }
}

function deleteCategory() {
    global $pdo;
    
    if (isset($_POST['id'])) {
        try {
            // Check if category is being used by products
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
            $stmt->execute([$_POST['id']]);
            $productCount = $stmt->fetchColumn();
            
            if ($productCount > 0) {
                echo json_encode(['error' => 'Cannot delete category. It is being used by ' . $productCount . ' product(s).']);
                return;
            }
            
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete category']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Category ID required']);
    }
}

function updateCategory() {
    global $pdo;
    
    if (isset($_POST['id']) && isset($_POST['name'])) {
        try {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            $stmt->execute([
                $_POST['name'],
                $_POST['description'] ?? '',
                $_POST['id']
            ]);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update category: ' . $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Category ID and name required']);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - ProTechMate</title>
    <link rel="icon" type="image/png" href="uploads/icons8-cart-100.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="header">
        <h1><a href="index.html" class="logo"><img src="uploads/ProTechMate (1000 x 500 px).png" alt="ProTechMate Logo" class="logo-img"></a></h1>
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
            <button class="tab-btn" onclick="switchTab('categories')">
                <i class="fas fa-tags"></i> Category Management
            </button>
            <button class="tab-btn" onclick="switchTab('contacts')">
                <i class="fas fa-envelope"></i> Contact Messages
            </button>
            <button class="tab-btn" onclick="switchTab('users')">
                <i class="fas fa-users"></i> Users
            </button>
        </div>

        <!-- Products Tab -->
        <div class="tab-content" id="products-tab">
            <div class="card">
                <h2><i class="fas fa-plus-circle"></i> Add New Product</h2>
            <form id="productForm" enctype="multipart/form-data">
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
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id">
                        <option value="">Select a category (optional)</option>
                        <!-- Categories will be loaded dynamically -->
                    </select>
                </div>
                <div class="form-group">
                    <label for="product_image">Product Image</label>
                    <input type="file" id="product_image" name="product_image" accept="image/*">
                    <div class="image-preview" id="imagePreview">
                        <img src="https://via.placeholder.com/300x200?text=No+Image" alt="Image Preview" id="previewImg">
                        <p>Select an image to preview</p>
                    </div>
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
        
        <!-- Users Tab -->
        <div class="tab-content" id="users-tab" style="display: none;">
            <div class="card">
                <h2><i class="fas fa-users"></i> Users</h2>
                <div class="users-table-container">
                    <table class="users-table" id="usersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Admin</th>
                                <th>Joined</th>
                                <th>Total Orders</th>
                                <th>Total Spent</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <!-- Users will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Contacts Tab -->
        <div class="tab-content" id="contacts-tab" style="display: none;">
            <div class="card">
                <h2><i class="fas fa-envelope"></i> Contact Messages</h2>
                <div class="contacts-section">
                    <div class="contacts-stats">
                        <div class="stat-card">
                            <div class="stat-number" id="totalContacts">0</div>
                            <div class="stat-label">Total Messages</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" id="newContacts">0</div>
                            <div class="stat-label">New Messages</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" id="repliedContacts">0</div>
                            <div class="stat-label">Replied Messages</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" id="archivedContacts">0</div>
                            <div class="stat-label">Archived Messages</div>
                        </div>
                    </div>
                    <div class="contacts-filter">
                        <select id="contactStatusFilter" onchange="loadContacts(this.value)">
                            <option value="">All Messages</option>
                            <option value="new">New Messages</option>
                            <option value="read">Read Messages</option>
                            <option value="replied">Replied Messages</option>
                            <option value="archived">Archived Messages</option>
                        </select>
                    </div>
                    <div class="contacts-table-container">
                        <table class="contacts-table" id="contactsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Subject</th>
                                    <th>Message</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="contactsTableBody">
                                <!-- Contact messages will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- Categories Tab -->
        <div class="tab-content" id="categories-tab" style="display: none;">
            <div class="card">
                <h2><i class="fas fa-plus-circle"></i> Add New Category</h2>
                <form id="categoryForm">
                    <div class="form-group">
                        <label for="categoryName">Category Name</label>
                        <input type="text" id="categoryName" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="categoryDescription">Description</label>
                        <textarea id="categoryDescription" name="description" rows="3" placeholder="Optional description for this category"></textarea>
                    </div>
                    <button type="submit" class="btn">Add Category</button>
                </form>
            </div>

            <div class="card">
                <h2><i class="fas fa-tags"></i> Manage Categories</h2>
                <div class="categories-grid" id="categoriesGrid">
                    <!-- Categories will be loaded here -->
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

    </div>

    <!-- Edit Product Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Product</h2>
            <form id="editProductForm" enctype="multipart/form-data" method="post">
                <input type="hidden" id="editId" name="id">
                <input type="hidden" id="currentImageUrl" name="current_image_url">
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
                    <label for="editCategoryId">Category</label>
                    <select id="editCategoryId" name="category_id">
                        <option value="">Select a category (optional)</option>
                        <!-- Categories will be loaded dynamically -->
                    </select>
                </div>
                <div class="form-group">
                    <label for="editProductImage">Product Image</label>
                    <input type="file" id="editProductImage" name="product_image" accept="image/*">
                    <div class="image-preview" id="editImagePreview">
                        <img src="https://via.placeholder.com/300x200?text=No+Image" alt="Image Preview" id="editPreviewImg">
                        <p>Current image or select new to replace</p>
                    </div>
                </div>
                <div class="form-group">
                    <label for="editStockQuantity">Stock Quantity</label>
                    <input type="number" id="editStockQuantity" name="stock_quantity" min="0">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn" id="updateProductBtn">Update Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div id="editCategoryModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditCategoryModal()">&times;</span>
            <h2>Edit Category</h2>
            <form id="editCategoryForm">
                <input type="hidden" id="editCategoryId" name="id">
                <div class="form-group">
                    <label for="editCategoryName">Category Name</label>
                    <input type="text" id="editCategoryName" name="name" required>
                </div>
                <div class="form-group">
                    <label for="editCategoryDescription">Description</label>
                    <textarea id="editCategoryDescription" name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn" id="updateCategoryBtn">Update Category</button>
                </div>
            </form>
        </div>
    </div>

    <script src="admin.js"></script>
</body>
</html>
