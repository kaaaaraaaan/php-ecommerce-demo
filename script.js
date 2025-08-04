// Global variables
let currentUser = null;
let products = [];
let cart = [];

// Initialize app
document.addEventListener('DOMContentLoaded', async () => {
    await checkUserStatus();
    await loadProducts();
});

// Check if user is logged in
async function checkUserStatus() {
    try {
        const response = await fetch('api.php?action=user');
        const data = await response.json();
        
        if (data.logged_in) {
            currentUser = data.user;
            showUserSection();
            await loadCart();
        } else {
            showGuestSection();
        }
    } catch (error) {
        console.error('Error checking user status:', error);
        showGuestSection();
    }
}

// Show user section in navigation
function showUserSection() {
    document.getElementById('userSection').classList.remove('hidden');
    document.getElementById('guestSection').classList.add('hidden');
    document.getElementById('welcomeText').textContent = `Welcome, ${currentUser.username}!`;
    
    // Add admin link if user is admin
    if (currentUser.is_admin) {
        const adminLink = document.createElement('a');
        adminLink.href = 'admin.php';
        adminLink.textContent = 'Admin Panel';
        adminLink.style.marginRight = '1rem';
        document.getElementById('userSection').insertBefore(adminLink, document.getElementById('userSection').firstChild);
    }
}

// Show guest section in navigation
function showGuestSection() {
    document.getElementById('userSection').classList.add('hidden');
    document.getElementById('guestSection').classList.remove('hidden');
}

// Load products from API
async function loadProducts() {
    try {
        const response = await fetch('api.php?action=products');
        products = await response.json();
        displayProducts();
    } catch (error) {
        console.error('Error loading products:', error);
        showAlert('Error loading products', 'error');
    } finally {
        document.getElementById('loadingProducts').style.display = 'none';
    }
}

// Display products in grid
function displayProducts() {
    const grid = document.getElementById('productsGrid');
    grid.innerHTML = '';
    
    products.forEach(product => {
        const productCard = createProductCard(product);
        grid.appendChild(productCard);
    });
}

// Create product card element
function createProductCard(product) {
    const card = document.createElement('div');
    card.className = 'product-card';
    card.innerHTML = `
        <img src="${product.image_url || 'https://via.placeholder.com/300x200?text=No+Image'}" 
             alt="${product.name}" class="product-image">
        <div class="product-info">
            <div class="product-name">${product.name}</div>
            <div class="product-description">${product.description}</div>
            <div class="product-price">$${parseFloat(product.price).toFixed(2)}</div>
            <button class="add-to-cart" onclick="addToCart(${product.id})">
                Add to Cart
            </button>
        </div>
    `;
    return card;
}

// Authentication functions - now handled by dedicated pages



// Logout function
async function logout() {
    try {
        await fetch('api.php?action=logout', { method: 'POST' });
        currentUser = null;
        cart = [];
        showGuestSection();
        updateCartCount();
        showAlert('Logged out successfully', 'success');
    } catch (error) {
        showAlert('Error during logout', 'error');
    }
}

// Cart functions
async function addToCart(productId) {
    if (!currentUser) {
        showAlert('Please login to add items to cart', 'error');
        window.location.href = 'login.html';
        return;
    }
    
    try {
        const response = await fetch('api.php?action=cart', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: 1
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('Item added to cart!', 'success');
            await loadCart();
        } else {
            showAlert(result.error || 'Failed to add to cart', 'error');
        }
    } catch (error) {
        showAlert('Error adding to cart', 'error');
    }
}

async function loadCart() {
    if (!currentUser) return;
    
    try {
        const response = await fetch('api.php?action=cart');
        cart = await response.json();
        updateCartCount();
    } catch (error) {
        console.error('Error loading cart:', error);
    }
}

function updateCartCount() {
    const cartCount = document.getElementById('cartCount');
    const totalItems = cart.reduce((sum, item) => sum + parseInt(item.quantity), 0);
    
    if (totalItems > 0) {
        cartCount.textContent = totalItems;
        cartCount.classList.remove('hidden');
    } else {
        cartCount.classList.add('hidden');
    }
}

// Cart modal functions removed - now using separate cart.html page

async function removeFromCart(cartId) {
    try {
        const response = await fetch('api.php?action=cart', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ cart_id: cartId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            await loadCart();
            showAlert('Item removed from cart', 'success');
        } else {
            showAlert(result.error || 'Failed to remove item', 'error');
        }
    } catch (error) {
        showAlert('Error removing item', 'error');
    }
}

// Checkout function moved to checkout.html page

// Utility functions
function showAlert(message, type) {
    const alertElement = document.getElementById(type === 'success' ? 'successAlert' : 'errorAlert');
    alertElement.textContent = message;
    alertElement.style.display = 'block';
    
    setTimeout(() => {
        alertElement.style.display = 'none';
    }, 5000);
}

function scrollToProducts() {
    document.getElementById('productsSection').scrollIntoView({ behavior: 'smooth' });
}

// Modal handling removed - now using separate pages
