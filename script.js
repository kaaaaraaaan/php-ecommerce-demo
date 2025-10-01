// Global variables
let currentUser = null;
let products = [];
let allProducts = []; // Store all products for filtering
let categories = [];
let cart = [];
let currentCategoryId = null;

// Initialize app
document.addEventListener('DOMContentLoaded', async () => {
    await checkUserStatus();
    await loadCategories();
    await loadProducts();
    initializeStatsAnimation();
    initMobileNav();
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

    // Add dashboard link for all logged-in users (avoid duplicates)
    const userSection = document.getElementById('userSection');
    const existingDashboard = [...userSection.querySelectorAll('a')].find(a => a.getAttribute('href') === 'user-dashboard.html');
    if (!existingDashboard) {
        const dashboardLink = document.createElement('a');
        dashboardLink.href = 'user-dashboard.html';
        dashboardLink.textContent = 'My Dashboard';
        dashboardLink.style.marginRight = '1rem';
        const cartIcon = userSection.querySelector('.cart-icon');
        userSection.insertBefore(dashboardLink, cartIcon || userSection.firstChild);
    }
}

// Show guest section in navigation
function showGuestSection() {
    document.getElementById('userSection').classList.add('hidden');
    document.getElementById('guestSection').classList.remove('hidden');
}

// Load categories from API
async function loadCategories() {
    try {
        const response = await fetch('api.php?action=categories');
        categories = await response.json();
        displayCategoryButtons();
    } catch (error) {
        console.error('Error loading categories:', error);
    }
}

// Display category filter buttons
function displayCategoryButtons() {
    const categoryButtons = document.getElementById('categoryButtons');
    
    // Add category buttons
    categories.forEach(category => {
        const button = document.createElement('button');
        button.className = 'category-btn';
        button.setAttribute('data-category', category.id);
        button.innerHTML = `<i class="fas fa-tag"></i> ${category.name}`;
        button.addEventListener('click', () => filterByCategory(category.id));
        categoryButtons.appendChild(button);
    });
    
    // Add event listener for "All Products" button
    const allButton = categoryButtons.querySelector('[data-category="all"]');
    allButton.addEventListener('click', () => filterByCategory('all'));
}

// Load products from API
async function loadProducts(categoryId = null) {
    try {
        let url = 'api.php?action=products';
        if (categoryId && categoryId !== 'all') {
            url += `&category_id=${categoryId}`;
        }
        
        const response = await fetch(url);
        products = await response.json();
        
        // Store all products if loading without filter
        if (!categoryId || categoryId === 'all') {
            allProducts = [...products];
        }
        
        displayProducts();
    } catch (error) {
        console.error('Error loading products:', error);
        showAlert('Error loading products', 'error');
    } finally {
        document.getElementById('loadingProducts').style.display = 'none';
    }
}

// Filter products by category
function filterByCategory(categoryId) {
    currentCategoryId = categoryId;
    
    // Update active button
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`[data-category="${categoryId}"]`).classList.add('active');
    
    // Load products for selected category
    loadProducts(categoryId);
}

// Display products in grid
function displayProducts() {
    const grid = document.getElementById('productsGrid');
    grid.innerHTML = '';
    
    // Check if we're on the homepage by looking for the hero section
    const isHomepage = document.querySelector('.hero-section') !== null;
    
    // Limit to 6 products on homepage, show all on products page
    const productsToShow = isHomepage ? products.slice(0, 6) : products;
    
    productsToShow.forEach(product => {
        const productCard = createProductCard(product);
        grid.appendChild(productCard);
    });
    
    // Add "View All Products" button on homepage if there are more than 6 products
    if (isHomepage && products.length > 6) {
        addViewAllProductsButton();
    }
}

// Add "View All Products" button to homepage
function addViewAllProductsButton() {
    const productsSection = document.getElementById('productsSection');
    const container = productsSection.querySelector('.container');
    
    // Remove existing button if it exists
    const existingButton = container.querySelector('.view-all-products-btn');
    if (existingButton) {
        existingButton.remove();
    }
    
    // Create button container
    const buttonContainer = document.createElement('div');
    buttonContainer.className = 'view-all-container';
    buttonContainer.style.textAlign = 'center';
    buttonContainer.style.marginTop = '2rem';
    
    // Create the button
    const viewAllBtn = document.createElement('a');
    viewAllBtn.href = 'products.html';
    viewAllBtn.className = 'view-all-products-btn btn-primary';
    viewAllBtn.innerHTML = '<i class="fas fa-arrow-right"></i> View All Products';
    viewAllBtn.style.display = 'inline-block';
    viewAllBtn.style.padding = '12px 24px';
    viewAllBtn.style.textDecoration = 'none';
    
    buttonContainer.appendChild(viewAllBtn);
    container.appendChild(buttonContainer);
}

// Create product card element
function createProductCard(product) {
    const card = document.createElement('div');
    card.className = 'product-card';
    card.style.cursor = 'pointer';
    card.innerHTML = `
        <img src="${product.image_url || 'https://via.placeholder.com/300x200?text=No+Image'}" 
             alt="${product.name}" class="product-image">
        <div class="product-info">
            ${product.category_name ? `<div class="product-category"><i class="fas fa-tag"></i> ${product.category_name}</div>` : ''}
            <div class="product-name">${product.name}</div>
            <div class="product-description">${product.description}</div>
            <div class="product-price">$${parseFloat(product.price).toFixed(2)}</div>
            <div class="product-actions">
                <button class="view-product" onclick="event.stopPropagation(); window.location.href='product.html?id=${product.id}'">
                    <i class="fas fa-eye"></i> View Details
                </button>
                <button class="add-to-cart" onclick="event.stopPropagation(); addToCart(${product.id})">
                    <i class="fas fa-shopping-cart"></i> Add to Cart
                </button>
            </div>
        </div>
    `;
    
    // Make the entire card clickable to view product details
    card.addEventListener('click', () => {
        window.location.href = `product.html?id=${product.id}`;
    });
    
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
        const response = await securityManager.secureFetch('api.php?action=cart', {
            method: 'POST',
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
        const response = await securityManager.secureFetch('api.php?action=cart', {
            method: 'DELETE',
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

// Load categories for homepage display
async function loadCategoriesForHomepage() {
    try {
        const response = await fetch('api.php?action=categories');
        const categories = await response.json();
        displayCategoriesGrid(categories);
    } catch (error) {
        console.error('Error loading categories for homepage:', error);
    }
}

// Display categories in homepage grid
function displayCategoriesGrid(categories) {
    const categoriesGrid = document.getElementById('categoriesGrid');
    if (!categoriesGrid) return;
    
    categoriesGrid.innerHTML = '';
    
    // Category icons mapping
    const categoryIcons = {
        'Laptops': 'fas fa-laptop',
        'Smartphones': 'fas fa-mobile-alt',
        'Audio': 'fas fa-headphones',
        'Tablets': 'fas fa-tablet-alt',
        'Gaming': 'fas fa-gamepad',
        'Drones': 'fas fa-helicopter',
        'default': 'fas fa-tag'
    };
    
    categories.forEach(category => {
        const categoryCard = document.createElement('div');
        categoryCard.className = 'category-card';
        categoryCard.onclick = () => {
            // Filter products by this category and scroll to products section
            filterByCategory(category.id);
            scrollToProducts();
        };
        
        const iconClass = categoryIcons[category.name] || categoryIcons['default'];
        
        categoryCard.innerHTML = `
            <div class="category-icon">
                <i class="${iconClass}"></i>
            </div>
            <h3>${category.name}</h3>
            <p>${category.description || 'Explore our ' + category.name.toLowerCase() + ' collection'}</p>
        `;
        
        categoriesGrid.appendChild(categoryCard);
    });
}

// Initialize statistics animation
function initializeStatsAnimation() {
    const statNumbers = document.querySelectorAll('.stat-number[data-target]');
    
    // Create intersection observer for stats animation
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateStatNumber(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });
    
    statNumbers.forEach(stat => {
        observer.observe(stat);
    });
}

// Animate stat number counting up
function animateStatNumber(element) {
    const target = parseInt(element.getAttribute('data-target'));
    const duration = 2000; // 2 seconds
    const increment = target / (duration / 16); // 60fps
    let current = 0;
    
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            current = target;
            clearInterval(timer);
        }
        
        // Format number with commas
        element.textContent = Math.floor(current).toLocaleString();
        
        // Add + for certain stats
        if (target >= 1000) {
            element.textContent += '+';
        }
    }, 16);
}

// Newsletter section removed
// Utility functions
function showAlert(message, type = 'success') {
    const alertDiv = document.getElementById(type === 'success' ? 'successAlert' : 'errorAlert');
    alertDiv.textContent = message;
    alertDiv.style.display = 'block';
    
    setTimeout(() => {
        alertDiv.style.display = 'none';
    }, 5000);
}

function scrollToProducts() {
    document.getElementById('productsSection').scrollIntoView({ behavior: 'smooth' });
}

// Categories section removed
function scrollToCategories() {
    // Scroll to products section instead since categories section was removed
    scrollToProducts();
}

// Modal handling removed - now using separate pages

// Initialize mobile navigation (hamburger menu)
function initMobileNav() {
    const nav = document.querySelector('.nav');
    const navLinks = nav ? nav.querySelector('.nav-links') : null;
    if (!nav || !navLinks) return; // Not present on this page

    // Ensure nav-links has an id for aria-controls
    if (!navLinks.id) navLinks.id = 'primary-navigation';

    // Avoid duplicate hamburger
    if (nav.querySelector('.hamburger')) return;

    // Create hamburger button
    const btn = document.createElement('button');
    btn.className = 'hamburger';
    btn.setAttribute('aria-label', 'Toggle navigation');
    btn.setAttribute('aria-expanded', 'false');
    btn.setAttribute('aria-controls', navLinks.id);
    btn.innerHTML = '<span></span><span></span><span></span>';

    // Insert button right before nav-links
    nav.insertBefore(btn, navLinks);

    const closeMenu = () => {
        nav.classList.remove('mobile-open');
        btn.classList.remove('active');
        btn.setAttribute('aria-expanded', 'false');
    };

    const openMenu = () => {
        nav.classList.add('mobile-open');
        btn.classList.add('active');
        btn.setAttribute('aria-expanded', 'true');
    };

    // Toggle on click
    btn.addEventListener('click', () => {
        if (nav.classList.contains('mobile-open')) {
            closeMenu();
        } else {
            openMenu();
        }
    });

    // Close when clicking a nav link (use event delegation)
    navLinks.addEventListener('click', (e) => {
        const target = e.target;
        if (target && (target.tagName === 'A' || target.closest('a'))) {
            closeMenu();
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && nav.classList.contains('mobile-open')) {
            closeMenu();
        }
    });

    // Reset state on resize to desktop
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            closeMenu();
        }
    });
}

document.addEventListener('DOMContentLoaded', initMobileNav);
