# MAZHALAI MART - FUNCTIONAL DEPENDENCIES

## What needs to work for each feature?

### 🏠 **HOMEPAGE** works if these files exist:
```
Essential:
  ✓ index.html
  ✓ style.css
  ✓ index.css
  ✓ js/main.js
  ✓ config/database.php
  ✓ images/home banner.jpg

Optional (for featured products):
  ✓ api/products.php
  ✓ images/product images
```

---

### 🛍️ **PRODUCT CATALOG** works if these files exist:
```
Essential:
  ✓ products.html
  ✓ products.css
  ✓ api/products.php
  ✓ config/database.php
  ✓ auth/session_config.php

JavaScript:
  ✓ js/main.js
  ✓ js/user-cart.js

Database:
  ✓ database/complete_admin_schema.sql (with products table)
  ✓ Product images in images/ or admin/uploads/products/
```

---

### 🛒 **SHOPPING CART** works if these files exist:
```
Essential:
  ✓ cart.html
  ✓ cart.css
  ✓ cart-page.css
  ✓ api/user_cart.php
  ✓ auth/auth_check.php
  ✓ config/database.php

JavaScript:
  ✓ js/user-cart.js
  ✓ js/cart.js
  ✓ js/cart-page.js
  ✓ js/main.js

Database:
  ✓ user_cart table (in complete_admin_schema.sql)
```

---

### 💳 **CHECKOUT** works if these files exist:
```
Essential:
  ✓ checkout.html
  ✓ checkout.css
  ✓ api/user_orders.php
  ✓ auth/auth_check.php
  ✓ config/database.php

JavaScript:
  ✓ js/checkout.js
  ✓ js/main.js

Database:
  ✓ orders table
  ✓ order_items table
```

---

### 👤 **USER LOGIN/SIGNUP** works if these files exist:
```
Essential:
  ✓ login.html
  ✓ signup.html
  ✓ auth.css
  ✓ auth/login.php
  ✓ auth/signup.php
  ✓ auth/session_config.php
  ✓ config/database.php

JavaScript:
  ✓ js/auth.js
  ✓ js/main.js

Database:
  ✓ users table
  ✓ sessions table
```

---

### 📦 **ORDER HISTORY** works if these files exist:
```
Essential:
  ✓ orders.html
  ✓ orders.css
  ✓ api/user_orders.php
  ✓ auth/auth_check.php
  ✓ config/database.php

JavaScript:
  ✓ js/orders.js
  ✓ js/main.js

Database:
  ✓ orders table
  ✓ order_items table
  ✓ users table (to match orders)
```

---

### ✅ **ORDER CONFIRMATION** works if these files exist:
```
Essential:
  ✓ order_confirmation.html
  ✓ order_confirmation.css
  ✓ js/main.js
  ✓ config/database.php

Optional:
  ✓ api/user_orders.php (to fetch order details)
```

---

### 🔐 **ADMIN LOGIN** works if these files exist:
```
Essential:
  ✓ admin/login.html
  ✓ auth.css
  ✓ admin/api/login.php
  ✓ config/database.php
  ✓ auth/session_config.php

JavaScript:
  ✓ js/main.js

Database:
  ✓ admins table
  ✓ sessions table
```

---

### 📊 **ADMIN DASHBOARD** works if these files exist:
```
Essential:
  ✓ admin/dashboard.html
  ✓ admin/admin-styles.css
  ✓ admin/api/dashboard_stats_simple.php
  ✓ admin/admin_check.php
  ✓ config/database.php

JavaScript:
  ✓ admin/admin-responsive.js
  ✓ js/main.js

Database:
  ✓ All tables (for statistics)
```

---

### 📦 **PRODUCT MANAGEMENT** works if these files exist:
```
View Products:
  ✓ admin/products/view_products.html
  ✓ admin/admin-styles.css
  ✓ admin/api/products.php
  ✓ admin/api/product_stats.php
  ✓ admin/admin_check.php

Add Product:
  ✓ admin/products/add_product.html
  ✓ admin/api/add_product.php
  ✓ admin/uploads/products/ (directory)

Edit Product:
  ✓ admin/products/edit_product.html
  ✓ admin/api/products.php (to fetch)
  ✓ admin/api/edit_product.php

Delete Product:
  ✓ admin/api/delete_product.php

JavaScript:
  ✓ admin/admin-responsive.js

Database:
  ✓ products table
```

---

### 📋 **ORDER MANAGEMENT** works if these files exist:
```
Essential:
  ✓ admin/orders/view_orders.html
  ✓ admin/admin-styles.css
  ✓ admin/api/orders.php
  ✓ admin/api/order_stats.php
  ✓ admin/orders/get_order_details.php
  ✓ admin/orders/update_order_status.php
  ✓ admin/admin_check.php

JavaScript:
  ✓ admin/admin-responsive.js

Database:
  ✓ orders table
  ✓ order_items table
```

---

### 👥 **USER MANAGEMENT** works if these files exist:
```
Essential:
  ✓ admin/users/view_users.html
  ✓ admin/admin-styles.css
  ✓ admin/api/users_fixed.php
  ✓ admin/api/user_stats_fixed.php
  ✓ admin/users/block_user_fixed.php
  ✓ admin/admin_check.php
  ✓ config/database.php

JavaScript:
  ✓ admin/admin-responsive.js

Database:
  ✓ users table
  ✓ orders table (for user stats)
```

---

## 🔗 DEPENDENCY CHAIN

```
Website Start
    ↓
config/database.php (loads PDO connection)
    ↓
    ├─→ All API endpoints work
    ├─→ All HTML pages can query database
    └─→ Session management works
    
User Authentication
    ├─→ auth/session_config.php
    ├─→ auth/auth_check.php
    └─→ auth/login.php, signup.php, logout.php
    
Frontend Rendering
    ├─→ HTML pages
    ├─→ CSS files
    └─→ JavaScript files
    
Backend Data
    ├─→ API endpoints
    └─→ database tables
```

---

## 🎯 QUICK START: What to check first if something breaks

1. **"Database connection error"** → Check:
   - `config/database.php` exists
   - `.env` file has correct credentials
   - MySQL server running

2. **"404 Not Found"** → Check:
   - HTML files exist in correct path
   - URLs are correct in navigation

3. **"Page looks broken"** → Check:
   - All CSS files present
   - CSS paths are correct
   - Browser cache cleared

4. **"Buttons don't work"** → Check:
   - JavaScript files present
   - API endpoint URLs correct
   - Console for errors (F12)

5. **"Can't login"** → Check:
   - `auth/login.php` exists
   - Database `users` table exists
   - Session management working

6. **"Admin dashboard empty"** → Check:
   - `admin/api/dashboard_stats_simple.php` exists
   - User logged in as admin
   - Database tables populated

---

## 📝 FILE COUNT BY FUNCTIONALITY

| Feature | Files Required | Critical |
|---------|---|---|
| Homepage | 5 files | High |
| Product Browsing | 7 files | High |
| Shopping Cart | 8 files | High |
| Checkout | 6 files | High |
| User Auth | 8 files | Critical |
| Order History | 6 files | High |
| Admin Dashboard | 6 files | Medium |
| Product Mgmt | 8 files | Medium |
| Order Mgmt | 7 files | Medium |
| User Mgmt | 7 files | Medium |

**Total unique files: 64** (some files used by multiple features)

