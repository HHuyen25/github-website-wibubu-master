<?php
require_once 'src/models/Database.php';

$database = new Database();
$pdo = $database->getConnection();

if (!$pdo) {
    die("Database connection failed!");
}

try {
    // Create users table
    $sql_users = "CREATE TABLE IF NOT EXISTS users (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(20) DEFAULT 'customer' CHECK (role IN ('admin', 'staff', 'customer')),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql_users);

    // Create categories table
    $sql_categories = "CREATE TABLE IF NOT EXISTS categories (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql_categories);

    // Create products table
    $sql_products = "CREATE TABLE IF NOT EXISTS products (
        id SERIAL PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        stock INTEGER DEFAULT 0,
        category_id INTEGER REFERENCES categories(id) ON DELETE CASCADE,
        image_url VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql_products);

    // Create promotions table
    $sql_promotions = "CREATE TABLE IF NOT EXISTS promotions (
        id SERIAL PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        discount_percent DECIMAL(5,2) NOT NULL,
        start_date TIMESTAMP NOT NULL,
        end_date TIMESTAMP NOT NULL,
        product_id INTEGER REFERENCES products(id) ON DELETE CASCADE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql_promotions);

    // Create orders table
    $sql_orders = "CREATE TABLE IF NOT EXISTS orders (
        id SERIAL PRIMARY KEY,
        user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
        total_amount DECIMAL(10,2) NOT NULL,
        status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'confirmed', 'shipped', 'completed', 'cancelled')),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql_orders);

    // Create order_items table
    $sql_order_items = "CREATE TABLE IF NOT EXISTS order_items (
        id SERIAL PRIMARY KEY,
        order_id INTEGER REFERENCES orders(id) ON DELETE CASCADE,
        product_id INTEGER REFERENCES products(id) ON DELETE CASCADE,
        quantity INTEGER NOT NULL,
        price DECIMAL(10,2) NOT NULL
    )";
    $pdo->exec($sql_order_items);

    // Create chatbox table
    $sql_chatbox = "CREATE TABLE IF NOT EXISTS chatbox (
        id SERIAL PRIMARY KEY,
        sender_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
        receiver_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql_chatbox);

    echo "Database tables created successfully!\n";

    // Insert some sample data
    // Insert sample categories
    $categories = [
        ['Electronics', 'Thiết bị điện tử'],
        ['Fashion', 'Thời trang'],
        ['Books', 'Sách và tài liệu'],
        ['Home & Garden', 'Nhà cửa và vườn tược']
    ];

    foreach ($categories as $category) {
        $stmt = $pdo->prepare("INSERT INTO categories (name, description) SELECT ?, ? WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = ?)");
        $stmt->execute([$category[0], $category[1], $category[0]]);
    }

    // Insert sample admin user
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) SELECT ?, ?, ?, ? WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = ?)");
    $stmt->execute(['Admin', 'admin@wibubu.com', $admin_password, 'admin', 'admin@wibubu.com']);

    echo "Sample data inserted successfully!\n";

} catch(PDOException $exception) {
    echo "Error: " . $exception->getMessage() . "\n";
}
?>