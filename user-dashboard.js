// User Dashboard script
let currentUser = null;
let cart = [];

// Boot
document.addEventListener('DOMContentLoaded', async () => {
  await checkUserStatus();
  if (!currentUser) return; // redirected if not logged in
  await Promise.all([loadProfile(), loadOrders(), loadCart()]);
});

// Auth status and nav handling
async function checkUserStatus() {
  try {
    const res = await fetch('api.php?action=user');
    const data = await res.json();
    if (data.logged_in) {
      currentUser = data.user;
      showUserSection();
    } else {
      showGuestSection();
      window.location.replace('login.html');
    }
  } catch (e) {
    console.error('Failed to check user status', e);
    showGuestSection();
    window.location.replace('login.html');
  }
}

function showUserSection() {
  const userSection = document.getElementById('userSection');
  const guestSection = document.getElementById('guestSection');
  userSection.classList.remove('hidden');
  guestSection.classList.add('hidden');
  document.getElementById('welcomeText').textContent = `Welcome, ${currentUser.username}!`;

  // Admin link
  if (currentUser.is_admin) {
    const existing = [...userSection.querySelectorAll('a')].find(a => a.getAttribute('href') === 'admin.php');
    if (!existing) {
      const adminLink = document.createElement('a');
      adminLink.href = 'admin.php';
      adminLink.textContent = 'Admin Panel';
      adminLink.style.marginRight = '1rem';
      userSection.insertBefore(adminLink, userSection.firstChild);
    }
  }

  // Dashboard link
  const dashExisting = [...userSection.querySelectorAll('a')].find(a => a.getAttribute('href') === 'user-dashboard.html');
  if (!dashExisting) {
    const dashLink = document.createElement('a');
    dashLink.href = 'user-dashboard.html';
    dashLink.textContent = 'My Dashboard';
    dashLink.style.marginRight = '1rem';
    // Insert before cart icon (if present) or at start
    const cartIcon = userSection.querySelector('.cart-icon');
    userSection.insertBefore(dashLink, cartIcon || userSection.firstChild);
  }
}

function showGuestSection() {
  document.getElementById('userSection').classList.add('hidden');
  document.getElementById('guestSection').classList.remove('hidden');
}

// Fetch and render profile
async function loadProfile() {
  const loading = document.getElementById('loadingProfile');
  const container = document.getElementById('profileContent');
  try {
    const res = await fetch('api.php?action=my_profile');
    const data = await res.json();
    if (!data.success) throw new Error(data.error || 'Failed to fetch profile');

    const u = data.user;
    const initials = (u.username || '?').slice(0, 2).toUpperCase();
    container.innerHTML = `
      <div class="profile-row">
        <div class="avatar">${initials}</div>
        <div class="profile-fields">
          <div class="field"><label>Username</label><div>${u.username}</div></div>
          <div class="field"><label>Email</label><div>${u.email}</div></div>
          <div class="field"><label>Member Since</label><div>${new Date(u.created_at).toLocaleDateString()}</div></div>
          <div class="field"><label>Role</label><div>${u.is_admin ? 'Admin' : 'Customer'}</div></div>
        </div>
      </div>`;
    container.classList.remove('hidden');
  } catch (e) {
    showAlert('Error loading profile', 'error');
    console.error(e);
  } finally {
    loading.style.display = 'none';
  }
}

// Fetch and render orders
async function loadOrders() {
  const loading = document.getElementById('loadingOrders');
  const container = document.getElementById('ordersContent');
  try {
    const res = await fetch('api.php?action=my_orders');
    const data = await res.json();
    if (!data.success) throw new Error(data.error || 'Failed to fetch orders');

    const orders = data.orders || [];
    if (orders.length === 0) {
      container.innerHTML = '<div class="orders-empty"><i class="fas fa-box-open"></i><br>No orders yet.</div>';
      return;
    }

    container.innerHTML = orders.map(o => `
      <div class="order">
        <div class="order-header">
          <div><strong>Order #${o.id}</strong> <span class="muted">• ${new Date(o.created_at).toLocaleString()}</span></div>
          <div class="status">${o.status}</div>
        </div>
        <div class="muted">${o.items || 'No items'}</div>
        <div style="margin-top:.5rem; font-weight:600;">Total: $${parseFloat(o.total_amount).toFixed(2)}</div>
      </div>`).join('');
  } catch (e) {
    showAlert('Error loading orders', 'error');
    console.error(e);
  } finally {
    loading.style.display = 'none';
  }
}

// Cart count for nav
async function loadCart() {
  try {
    const res = await fetch('api.php?action=cart');
    cart = await res.json();
    updateCartCount();
  } catch (e) {
    console.error('Failed to load cart', e);
  }
}

function updateCartCount() {
  const el = document.getElementById('cartCount');
  const total = (cart || []).reduce((s, i) => s + parseInt(i.quantity), 0);
  if (total > 0) { el.textContent = total; el.classList.remove('hidden'); }
  else { el.classList.add('hidden'); }
}

// Logout
async function logout() {
  try {
    await fetch('api.php?action=logout', { method: 'POST' });
    currentUser = null;
    cart = [];
    showGuestSection();
    updateCartCount();
    showAlert('Logged out successfully', 'success');
    window.location.replace('index.html');
  } catch (e) {
    showAlert('Error during logout', 'error');
  }
}

// Alerts
function showAlert(message, type = 'success') {
  const el = document.getElementById(type === 'success' ? 'successAlert' : 'errorAlert');
  if (!el) return;
  el.textContent = message;
  el.style.display = 'block';
  setTimeout(() => { el.style.display = 'none'; }, 5000);
}
