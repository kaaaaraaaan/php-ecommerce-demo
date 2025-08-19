// Admin Panel JavaScript Functions

// Tab switching functionality
function switchTab(tabName) {
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(tab => {
        tab.style.display = 'none';
    });
    
    const tabBtns = document.querySelectorAll('.tab-btn');
    tabBtns.forEach(btn => {
        btn.classList.remove('active');
    });
    
    document.getElementById(tabName + '-tab').style.display = 'block';
    event.target.classList.add('active');
    
    switch(tabName) {
        case 'products': loadProducts(); break;
        case 'categories': loadCategoriesForManagement(); break;
        case 'orders': loadOrders(); break;
        case 'users': loadUsers(); break;
        case 'contacts': loadContacts(); break;
    }
}

// Utility functions
function showAlert(message, type) {
    const alertElement = document.getElementById(type === 'success' ? 'successAlert' : 'errorAlert');
    alertElement.textContent = message;
    alertElement.style.display = 'block';
    setTimeout(() => alertElement.style.display = 'none', 5000);
}

function showSuccess(message) { showAlert(message, 'success'); }
function showError(message) { showAlert(message, 'error'); }
function showLoading() { /* Add loading indicator if needed */ }
function hideLoading() { /* Hide loading indicator if needed */ }

function logout() {
    fetch('api.php?action=logout', { method: 'POST' })
        .then(() => window.location.href = 'index.html');
}

// Product Management
async function loadProducts() {
    try {
        const response = await fetch('admin.php?action=get_products', { method: 'POST' });
        const products = await response.json();
        
        const grid = document.getElementById('productsGrid');
        grid.innerHTML = '';
        products.forEach(product => grid.appendChild(createProductCard(product)));
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
        ${product.category_name ? 
            `<div class="product-category"><i class="fas fa-tag"></i> ${product.category_name}</div>` : 
            '<div class="product-category no-category"><i class="fas fa-question"></i> No Category</div>'}
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
    fetch('admin.php?action=get_products', { method: 'POST' })
        .then(response => response.json())
        .then(products => {
            const product = products.find(p => p.id == id);
            if (product) {
                document.getElementById('editProductForm').reset();
                document.getElementById('editId').value = product.id;
                document.getElementById('editName').value = product.name;
                document.getElementById('editDescription').value = product.description;
                document.getElementById('editPrice').value = product.price;
                document.getElementById('currentImageUrl').value = product.image_url;
                document.getElementById('editStockQuantity').value = product.stock_quantity;
                document.getElementById('editCategoryId').value = product.category_id || '';
                
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
    document.getElementById('editProductForm').reset();
}

async function deleteProduct(id) {
    if (!confirm('Are you sure you want to delete this product?')) return;
    
    try {
        const response = await fetch('admin.php?action=delete_product', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
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

// Category Management
async function loadCategories() {
    try {
        const response = await fetch('api.php?action=categories');
        const categories = await response.json();
        
        const selects = ['category_id', 'editCategoryId'];
        selects.forEach(selectId => {
            const select = document.getElementById(selectId);
            select.innerHTML = '<option value="">Select a category (optional)</option>';
            categories.forEach(category => {
                const option = document.createElement('option');
                option.value = category.id;
                option.textContent = category.name;
                select.appendChild(option);
            });
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
        categories.forEach(category => grid.appendChild(createCategoryCard(category)));
    } catch (error) {
        showAlert('Error loading categories', 'error');
    }
}

function createCategoryCard(category) {
    const card = document.createElement('div');
    card.className = 'product-card';
    card.innerHTML = `
        <div class="category-icon"><i class="fas fa-tag fa-3x"></i></div>
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

function editCategory(id) {
    fetch('api.php?action=categories')
        .then(response => response.json())
        .then(categories => {
            const category = categories.find(c => c.id == id);
            if (category) {
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

async function deleteCategory(id) {
    if (!confirm('Are you sure you want to delete this category?')) return;
    
    try {
        const response = await fetch('admin.php?action=delete_category', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        
        const result = await response.json();
        if (result.success) {
            showAlert('Category deleted successfully!', 'success');
            loadCategories();
            loadCategoriesForManagement();
        } else {
            showAlert(result.error || 'Failed to delete category', 'error');
        }
    } catch (error) {
        showAlert('Error deleting category', 'error');
    }
}

// Orders Management
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
            <td><span class="status-badge status-${order.status}">${order.status}</span></td>
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
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order_id: orderId, status: newStatus })
        });
        
        const result = await response.json();
        if (result.success) {
            showAlert(`Order #${orderId} status updated to ${newStatus}`, 'success');
            loadOrders();
        } else {
            showAlert(result.error || 'Failed to update order status', 'error');
            loadOrders();
        }
    } catch (error) {
        showAlert('Error updating order status', 'error');
        loadOrders();
    }
}

// User Management
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
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId, is_admin: isAdmin == 1 })
        });
        
        const result = await response.json();
        if (response.ok) {
            showAlert('User role updated successfully!', 'success');
            loadUsers();
        } else {
            showAlert('Failed to update user role: ' + result.error, 'error');
            loadUsers();
        }
    } catch (error) {
        showAlert('Error updating user role: ' + error.message, 'error');
        loadUsers();
    }
}

async function deleteUser(userId, username) {
    if (!confirm(`Are you sure you want to delete user "${username}"? This action cannot be undone.`)) return;
    
    try {
        const response = await fetch('api.php?action=users', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId })
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

// Contact Management
function loadContacts(status = '') {
    showLoading();
    const url = status ? `api.php?action=contact&status=${status}` : 'api.php?action=contact';
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayContacts(data.contacts);
                updateContactStats(data.stats);
            } else {
                showError(data.message || 'Failed to load contacts');
            }
            hideLoading();
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
        const truncatedMessage = contact.message.length > 100 ? 
            contact.message.substring(0, 100) + '...' : contact.message;
        
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
    const statusMap = {
        'new': 'status-new',
        'read': 'status-read', 
        'replied': 'status-replied',
        'archived': 'status-archived'
    };
    return statusMap[status] || '';
}

function updateContactStatus(contactId, status) {
    showLoading();
    
    fetch('api.php?action=contact', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
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

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    loadCategories();
    loadProducts();
    
    // Product form submission
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
    
    // Image preview for add product
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
                this.disabled = false;
                this.innerHTML = 'Update Product';
            }
        })
        .catch(error => {
            showAlert('Error updating product: ' + error.message, 'error');
            this.disabled = false;
            this.innerHTML = 'Update Product';
        });
    });
    
    // Image preview for edit product
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
    
    // Category form submission
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
                loadCategories();
                loadCategoriesForManagement();
            } else {
                showAlert(result.error || 'Failed to add category', 'error');
            }
        } catch (error) {
            showAlert('Error adding category: ' + error.message, 'error');
        }
    });
    
    // Edit category form submission
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
                loadCategories();
                loadCategoriesForManagement();
            } else {
                showAlert(result.error || 'Failed to update category', 'error');
            }
        } catch (error) {
            showAlert('Error updating category: ' + error.message, 'error');
        }
    });
    
    // Modal close functionality
    window.onclick = function(event) {
        const modal = document.getElementById('editModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
});
