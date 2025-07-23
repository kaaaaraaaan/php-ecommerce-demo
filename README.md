# PHP Ecommerce Website

A full-stack ecommerce website built with PHP, JavaScript, and MySQL featuring user authentication, shopping cart functionality, and an admin panel for product management.

## Features

### Frontend (JavaScript)
- Modern responsive design with CSS Grid and Flexbox
- Product catalog with search and filtering
- User authentication (login/register)
- Shopping cart functionality
- Checkout process
- Real-time cart updates

### Backend (PHP)
- RESTful API endpoints
- User authentication with sessions
- Product management
- Cart and order processing
- Admin panel for product CRUD operations
- MySQL database integration

### Admin Panel (/admin)
- Add, edit, and delete products
- Manage product inventory
- View and update product details
- Secure admin-only access

## Setup Instructions

### 1. Database Setup
1. Create a MySQL database named `ecommerce`
2. Import the database schema:
   ```sql
   mysql -u root -p ecommerce < database.sql
   ```

### 2. Configuration
1. Update database credentials in `config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'ecommerce');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   ```

### 3. Web Server
1. Place all files in your web server directory (e.g., `htdocs` for XAMPP)
2. Start Apache and MySQL services
3. Access the website at `http://localhost/your-project-folder`

## Default Admin Account
- Username: `admin`
- Password: `admin123`

## File Structure
```
├── index.html          # Main frontend page
├── script.js           # Frontend JavaScript logic
├── api.php            # Backend API endpoints
├── admin.php          # Admin panel
├── config.php         # Database configuration
├── database.sql       # Database schema and sample data
└── README.md          # This file
```

## API Endpoints

### Public Endpoints
- `GET /api.php?action=products` - Get all products
- `POST /api.php?action=login` - User login
- `POST /api.php?action=register` - User registration
- `GET /api.php?action=user` - Get current user info

### Authenticated Endpoints
- `GET /api.php?action=cart` - Get user's cart
- `POST /api.php?action=cart` - Add item to cart
- `DELETE /api.php?action=cart` - Remove item from cart
- `POST /api.php?action=checkout` - Process checkout
- `POST /api.php?action=logout` - User logout

### Admin Endpoints
- `POST /admin.php?action=add_product` - Add new product
- `POST /admin.php?action=update_product` - Update product
- `POST /admin.php?action=delete_product` - Delete product
- `POST /admin.php?action=get_products` - Get all products (admin view)

## Database Schema

### Tables
- `users` - User accounts and authentication
- `products` - Product catalog
- `cart` - Shopping cart items
- `orders` - Order records
- `order_items` - Individual items in orders

## Security Features
- Password hashing with PHP's `password_hash()`
- SQL injection prevention with prepared statements
- Session-based authentication
- Admin role verification
- CSRF protection through session validation

## Technologies Used
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Styling**: Custom CSS with modern design principles

## Browser Support
- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

## Contributing
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License
This project is open source and available under the MIT License.
