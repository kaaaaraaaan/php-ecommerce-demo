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

function addProduct() {
    global $pdo;
    
    // Check if form data was submitted
    if (isset($_POST['name']) && isset($_POST['price'])) {
        // Handle file upload
        $targetDir = "uploads/products/";
        $imageUrl = "";
        
        // Create directory if it doesn't exist
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        if (isset($_FILES["product_image"]) && $_FILES["product_image"]["error"] == 0) {
            $fileName = basename($_FILES["product_image"]["name"]);
            $targetFilePath = $targetDir . time() . "_" . $fileName; // Add timestamp to avoid duplicate filenames
            $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
            
            // Allow certain file formats
            $allowTypes = array('jpg', 'jpeg', 'png', 'gif', 'webp');
            if (in_array(strtolower($fileType), $allowTypes)) {
                // Upload file to server
                if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $targetFilePath)) {
                    $imageUrl = $targetFilePath;
                } else {
                    echo json_encode(['error' => 'Sorry, there was an error uploading your file.']);
                    return;
                }
            } else {
                echo json_encode(['error' => 'Sorry, only JPG, JPEG, PNG, GIF, & WEBP files are allowed.']);
                return;
            }
        }
        
        try {
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
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Name and price required']);
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
    
    // Check if form data was submitted
    if (isset($_POST['id'])) {
        // Handle file upload
        $targetDir = "uploads/products/";
        $imageUrl = $_POST['current_image_url'] ?? '';
        
        // Create directory if it doesn't exist
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        if (isset($_FILES["product_image"]) && $_FILES["product_image"]["error"] == 0) {
            $fileName = basename($_FILES["product_image"]["name"]);
            $targetFilePath = $targetDir . time() . "_" . $fileName; // Add timestamp to avoid duplicate filenames
            $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
            
            // Allow certain file formats
            $allowTypes = array('jpg', 'jpeg', 'png', 'gif', 'webp');
            if (in_array(strtolower($fileType), $allowTypes)) {
                // Upload file to server
                if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $targetFilePath)) {
                    $imageUrl = $targetFilePath;
                } else {
                    echo json_encode(['error' => 'Sorry, there was an error uploading your file.']);
                    return;
                }
            } else {
                echo json_encode(['error' => 'Sorry, only JPG, JPEG, PNG, GIF, & WEBP files are allowed.']);
                return;
            }
        }
        
        try {
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
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Product ID required']);
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
            <button class="tab-btn" onclick="switchTab('users')">
                <i class="fas fa-users"></i> User Management
            </button>
            <button class="tab-btn" onclick="switchTab('contacts')">
                <i class="fas fa-envelope"></i> Contact Messages
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
            } else if (tabName === 'categories') {
                loadCategoriesForManagement();
            } else if (tabName === 'orders') {
                loadOrders();
            } else if (tabName === 'users') {
                loadUsers();
            } else if (tabName === 'contacts') {
                loadContacts();
            }
        }

        // Load data when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadCategories();
            loadProducts();
        });

        // Add product form submission
        document.getElementById('productForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            
            try {
                const response = await fetch('admin.php?action=add_product', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Product added successfully!', 'success');
                    e.target.reset();
                    document.getElementById('previewImg').src = 'https://via.placeholder.com/300x200?text=No+Image';
                    loadProducts();
                } else {
                    showAlert(result.error || 'Failed to add product', 'error');
                }
            } catch (error) {
                showAlert('Error adding product', 'error');
            }
        });
        
        // Image preview functionality for add product
        document.getElementById('product_image').addEventListener('change', function() {
            const file = this.files[0];
            const previewImg = document.getElementById('previewImg');
            const previewText = previewImg.nextElementSibling;
            
            if (file) {
                const reader = new FileReader();
                
                reader.addEventListener('load', function() {
                    previewImg.src = this.result;
                    previewText.style.display = 'none';
                });
                
                reader.readAsDataURL(file);
            } else {
                previewImg.src = 'https://via.placeholder.com/300x200?text=No+Image';
                previewText.style.display = 'block';
            }
        });

        // Edit product form submission
        document.getElementById('updateProductBtn').addEventListener('click', function(e) {
            e.preventDefault();
            
            const form = document.getElementById('editProductForm');
            const formData = new FormData(form);
            
            // Disable button and show loading state
            this.disabled = true;
            this.innerHTML = 'Updating...';
            
            fetch('admin.php?action=update_product', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showAlert('Product updated successfully!', 'success');
                    closeEditModal();
                    loadProducts();
                } else {
                    showAlert(result.error || 'Failed to update product', 'error');
                    // Re-enable button
                    this.disabled = false;
                    this.innerHTML = 'Update Product';
                }
            })
            .catch(error => {
                showAlert('Error updating product: ' + error.message, 'error');
                // Re-enable button
                this.disabled = false;
                this.innerHTML = 'Update Product';
            });
        });
        
        // Also add the form submission handler as a backup
        document.getElementById('editProductForm').addEventListener('submit', function(e) {
            e.preventDefault();
            document.getElementById('updateProductBtn').click();
        });
        
        // Image preview functionality for edit product
        document.getElementById('editProductImage').addEventListener('change', function() {
            const file = this.files[0];
            const previewImg = document.getElementById('editPreviewImg');
            const previewText = previewImg.nextElementSibling;
            
            if (file) {
                const reader = new FileReader();
                
                reader.addEventListener('load', function() {
                    previewImg.src = this.result;
                    previewText.style.display = 'none';
                });
                
                reader.readAsDataURL(file);
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

        async function loadCategories() {
            try {
                const response = await fetch('api.php?action=categories');
                const categories = await response.json();
                
                // Populate category dropdowns
                const categorySelect = document.getElementById('category_id');
                const editCategorySelect = document.getElementById('editCategoryId');
                
                // Clear existing options (except the first one)
                categorySelect.innerHTML = '<option value="">Select a category (optional)</option>';
                editCategorySelect.innerHTML = '<option value="">Select a category (optional)</option>';
                
                categories.forEach(category => {
                    const option1 = document.createElement('option');
                    option1.value = category.id;
                    option1.textContent = category.name;
                    categorySelect.appendChild(option1);
                    
                    const option2 = document.createElement('option');
                    option2.value = category.id;
                    option2.textContent = category.name;
                    editCategorySelect.appendChild(option2);
                });
            } catch (error) {
                console.error('Error loading categories:', error);
            }
        }

        async function loadCategoriesForManagement() {
            try {
                const response = await fetch('api.php?action=categories');
                const categories = await response.json();
                
                const grid = document.getElementById('categoriesGrid');
                grid.innerHTML = '';
                
                categories.forEach(category => {
                    const categoryCard = createCategoryCard(category);
                    grid.appendChild(categoryCard);
                });
            } catch (error) {
                showAlert('Error loading categories', 'error');
            }
        }

        function createCategoryCard(category) {
            const card = document.createElement('div');
            card.className = 'product-card';
            card.innerHTML = `
                <div class="category-icon">
                    <i class="fas fa-tag fa-3x"></i>
                </div>
                <h3>${category.name}</h3>
                <p>${category.description || 'No description provided'}</p>
                <p><small>Created: ${new Date(category.created_at).toLocaleDateString()}</small></p>
                <div class="product-actions">
                    <button class="btn" onclick="editCategory(${category.id})">Edit</button>
                    <button class="btn btn-danger" onclick="deleteCategory(${category.id})">Delete</button>
                </div>
            `;
            return card;
        }

        function createProductCard(product) {
            const card = document.createElement('div');
            card.className = 'product-card';
            card.innerHTML = `
                <img src="${product.image_url || 'https://via.placeholder.com/300x200?text=No+Image'}" 
                     alt="${product.name}" class="product-image">
                ${product.category_name ? `<div class="product-category"><i class="fas fa-tag"></i> ${product.category_name}</div>` : '<div class="product-category no-category"><i class="fas fa-question"></i> No Category</div>'}
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
                        // Reset the form first to clear any previous data
                        document.getElementById('editProductForm').reset();
                        
                        document.getElementById('editId').value = product.id;
                        document.getElementById('editName').value = product.name;
                        document.getElementById('editDescription').value = product.description;
                        document.getElementById('editPrice').value = product.price;
                        document.getElementById('currentImageUrl').value = product.image_url;
                        document.getElementById('editStockQuantity').value = product.stock_quantity;
                        document.getElementById('editCategoryId').value = product.category_id || '';
                        
                        // Set the preview image
                        const previewImg = document.getElementById('editPreviewImg');
                        if (product.image_url) {
                            previewImg.src = product.image_url;
                            previewImg.nextElementSibling.style.display = 'none';
                        } else {
                            previewImg.src = 'https://via.placeholder.com/300x200?text=No+Image';
                            previewImg.nextElementSibling.style.display = 'block';
                        }
                        
                        document.getElementById('editModal').style.display = 'block';
                    }
                });
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
            // Reset the form when closing the modal
            document.getElementById('editProductForm').reset();
            // Reset the submit button state
            const submitBtn = document.querySelector('#editProductForm button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Update Product';
            }
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
        
        // Contact Management Functions
        function loadContacts(status = '') {
            showLoading();
            const url = status ? `api.php?action=contact&status=${status}` : 'api.php?action=contact';
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayContacts(data.contacts);
                        updateContactStats(data.stats);
                        hideLoading();
                    } else {
                        showError(data.message || 'Failed to load contacts');
                        hideLoading();
                    }
                })
                .catch(error => {
                    showError('Error loading contacts: ' + error.message);
                    hideLoading();
                });
        }
        
        function displayContacts(contacts) {
            const tbody = document.getElementById('contactsTableBody');
            tbody.innerHTML = '';
            
            if (contacts.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 2rem; color: #666;">No contact messages found</td></tr>';
                return;
            }
            
            contacts.forEach(contact => {
                const row = document.createElement('tr');
                const date = new Date(contact.created_at).toLocaleString();
                const statusClass = getStatusClass(contact.status);
                
                // Truncate message for display
                const truncatedMessage = contact.message.length > 100 
                    ? contact.message.substring(0, 100) + '...' 
                    : contact.message;
                
                row.innerHTML = `
                    <td><strong>#${contact.id}</strong></td>
                    <td>${contact.name}</td>
                    <td><a href="mailto:${contact.email}">${contact.email}</a></td>
                    <td>${contact.subject}</td>
                    <td title="${contact.message}">${truncatedMessage}</td>
                    <td>${date}</td>
                    <td><span class="status-badge ${statusClass}">${contact.status}</span></td>
                    <td>
                        <select class="status-select" onchange="updateContactStatus(${contact.id}, this.value)">
                            <option value="new" ${contact.status === 'new' ? 'selected' : ''}>New</option>
                            <option value="read" ${contact.status === 'read' ? 'selected' : ''}>Read</option>
                            <option value="replied" ${contact.status === 'replied' ? 'selected' : ''}>Replied</option>
                            <option value="archived" ${contact.status === 'archived' ? 'selected' : ''}>Archived</option>
                        </select>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }
        
        function updateContactStats(stats) {
            document.getElementById('totalContacts').textContent = stats.total || 0;
            document.getElementById('newContacts').textContent = stats.new || 0;
            document.getElementById('repliedContacts').textContent = stats.replied || 0;
            document.getElementById('archivedContacts').textContent = stats.archived || 0;
        }
        
        function getStatusClass(status) {
            switch(status) {
                case 'new': return 'status-new';
                case 'read': return 'status-read';
                case 'replied': return 'status-replied';
                case 'archived': return 'status-archived';
                default: return '';
            }
        }
        
        function updateContactStatus(contactId, status) {
            showLoading();
            
            fetch('api.php?action=contact', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `contact_id=${contactId}&status=${status}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess(data.message || 'Contact status updated');
                    loadContacts(document.getElementById('contactStatusFilter').value);
                } else {
                    showError(data.message || 'Failed to update contact status');
                    hideLoading();
                }
            })
            .catch(error => {
                showError('Error updating contact status: ' + error.message);
                hideLoading();
            });
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

        // Category management functions
        document.getElementById('categoryForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            
            try {
                const response = await fetch('admin.php?action=add_category', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Category added successfully!', 'success');
                    e.target.reset();
                    loadCategories(); // Refresh dropdowns
                    loadCategoriesForManagement(); // Refresh category grid
                } else {
                    showAlert(result.error || 'Failed to add category', 'error');
                }
            } catch (error) {
                showAlert('Error adding category: ' + error.message, 'error');
            }
        });

        function editCategory(id) {
            // Find category data
            fetch('api.php?action=categories')
                .then(response => response.json())
                .then(categories => {
                    const category = categories.find(c => c.id == id);
                    if (category) {
                        // Reset the form first
                        document.getElementById('editCategoryForm').reset();
                        
                        document.getElementById('editCategoryId').value = category.id;
                        document.getElementById('editCategoryName').value = category.name;
                        document.getElementById('editCategoryDescription').value = category.description || '';
                        
                        document.getElementById('editCategoryModal').style.display = 'block';
                    }
                });
        }

        function closeEditCategoryModal() {
            document.getElementById('editCategoryModal').style.display = 'none';
            document.getElementById('editCategoryForm').reset();
        }

        document.getElementById('editCategoryForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            
            try {
                const response = await fetch('admin.php?action=update_category', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Category updated successfully!', 'success');
                    closeEditCategoryModal();
                    loadCategories(); // Refresh dropdowns
                    loadCategoriesForManagement(); // Refresh category grid
                } else {
                    showAlert(result.error || 'Failed to update category', 'error');
                }
            } catch (error) {
                showAlert('Error updating category: ' + error.message, 'error');
            }
        });

        async function deleteCategory(id) {
            if (!confirm('Are you sure you want to delete this category? This action cannot be undone.')) {
                return;
            }
            
            try {
                const response = await fetch('admin.php?action=delete_category', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Category deleted successfully!', 'success');
                    loadCategories(); // Refresh dropdowns
                    loadCategoriesForManagement(); // Refresh category grid
                } else {
                    showAlert(result.error || 'Failed to delete category', 'error');
                }
            } catch (error) {
                showAlert('Error deleting category: ' + error.message, 'error');
            }
        }
    </script>
</body>
</html>
