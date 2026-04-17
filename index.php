<?php
session_start();
require_once 'config/db.php';
require_once 'config/app.php';

// Track analytics
$ip = $_SERVER['REMOTE_ADDR'];
$page = $_SERVER['REQUEST_URI'];
$referrer = $_SERVER['HTTP_REFERER'] ?? '';
$ua = $_SERVER['HTTP_USER_AGENT'];

$trackStmt = $conn->prepare("INSERT INTO analytics (page_url, visitor_ip, referrer, user_agent) VALUES (?, ?, ?, ?)");
$trackStmt->bind_param("ssss", $page, $ip, $referrer, $ua);
$trackStmt->execute();

// Capture referral
if (isset($_GET['ref'])) {
    $_SESSION['referral_code'] = $_GET['ref'];
    setcookie('referral_code', $_GET['ref'], time() + (86400 * 30), "/");
}

// Get settings
$settingsResult = $conn->query("SELECT * FROM settings WHERE id = 1");
$settings = $settingsResult->fetch_assoc();

// Get products
$productsResult = $conn->query("SELECT * FROM products WHERE stock_status = 'available' ORDER BY category, name");
$hotProductsResult = $conn->query("SELECT * FROM products WHERE is_hot != 'none' ORDER BY FIELD(is_hot, 'day', 'week', 'month') LIMIT 3");

// Get stats
$statsResult = $conn->query("SELECT 
    (SELECT COUNT(*) FROM users) as users_count,
    (SELECT COUNT(*) FROM referrals WHERE status = 'completed') as referrals_count,
    (SELECT COUNT(*) FROM repair_requests) as repairs_count");
$stats = $statsResult->fetch_assoc();

// Free Open Source APIs Configuration
$openExchangeAppId = 'YOUR_OPENEXCHANGE_APP_ID';
$weatherApiKey = 'YOUR_OPENWEATHER_API_KEY';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Erick Phone Repair - Professional Phone Repair in Nairobi CBD</title>
    <meta name="description" content="Expert phone repair services in Nairobi CBD. iPhone, Samsung, screen replacement, battery service. Fast, reliable, affordable.">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AOS Animation Library -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    
    <!-- Swiper.js Carousel -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    
    <style>
        /* Modern Design System */
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --primary-light: #34d399;
            --secondary: #3b82f6;
            --accent: #8b5cf6;
            --dark: #0f172a;
            --darker: #020617;
            --light: #f8fafc;
            --gray: #64748b;
            --gray-light: #94a3b8;
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
            --gradient-1: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-2: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-3: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --gradient-4: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --gradient-5: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --gradient-dark: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
            --shadow-2xl: 0 25px 50px -12px rgb(0 0 0 / 0.25);
            --radius-sm: 0.375rem;
            --radius-md: 0.75rem;
            --radius-lg: 1rem;
            --radius-xl: 1.5rem;
            --radius-2xl: 2rem;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
            overflow-x: hidden;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--light);
            color: var(--dark);
            line-height: 1.6;
            overflow-x: hidden;
            width: 100%;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        /* Navigation */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            padding: 0.75rem 0;
        }

        .navbar.scrolled {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(20px);
            padding: 0.5rem 0;
        }

        .navbar.scrolled .nav-link,
        .navbar.scrolled .brand-name {
            color: white;
        }

        .navbar.scrolled .brand-sub {
            color: var(--gray-light);
        }

        .nav-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .brand-icon {
            width: 45px;
            height: 45px;
            background: var(--gradient-1);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            box-shadow: var(--shadow-lg);
        }

        .brand-text {
            display: flex;
            flex-direction: column;
        }

        .brand-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark);
            line-height: 1.2;
        }

        .brand-sub {
            font-size: 0.75rem;
            color: var(--gray);
            font-weight: 500;
        }

        .nav-menu {
            display: flex;
            gap: 2rem;
        }

        .nav-link {
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            position: relative;
            padding: 0.5rem 0;
            font-size: 0.95rem;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: width 0.3s ease;
        }

        .nav-link:hover::after {
            width: 100%;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .btn-quick-quote {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.625rem 1.25rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .btn-quick-quote:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .nav-toggle {
            display: none;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
            padding: 0.5rem;
        }

        .nav-toggle span {
            width: 25px;
            height: 2px;
            background: var(--dark);
            transition: all 0.3s ease;
        }

        .navbar.scrolled .nav-toggle span {
            background: white;
        }

        /* Enhanced Hero Section */
        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 6rem 0 4rem;
            position: relative;
            overflow: hidden;
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
        }

        .hero-bg-animation {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 0;
        }

        .hero-bg-animation::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 30% 50%, rgba(102, 126, 234, 0.08) 0%, transparent 50%),
                        radial-gradient(circle at 70% 30%, rgba(245, 87, 108, 0.08) 0%, transparent 50%),
                        radial-gradient(circle at 50% 70%, rgba(79, 172, 254, 0.06) 0%, transparent 50%);
            animation: bgMove 20s ease-in-out infinite;
        }

        @keyframes bgMove {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(2%, 1%) rotate(1deg); }
            66% { transform: translate(-1%, 2%) rotate(-1deg); }
        }

        .hero-container {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 3rem;
            align-items: center;
        }

        .hero-badge-group {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(16, 185, 129, 0.1);
            color: var(--primary-dark);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            backdrop-filter: blur(4px);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .badge-pulse {
            width: 8px;
            height: 8px;
            background: var(--success);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        .hero-badge.offer {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
            border-color: rgba(245, 158, 11, 0.2);
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(1.3); }
        }

        .hero-title {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
        }

        .title-main {
            display: block;
        }

        .title-highlight {
            display: inline-block;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            position: relative;
        }

        .title-highlight::after {
            content: '';
            position: absolute;
            bottom: 5px;
            left: 0;
            right: 0;
            height: 8px;
            background: var(--primary);
            opacity: 0.2;
            border-radius: 4px;
            z-index: -1;
        }

        .hero-description {
            font-size: clamp(1rem, 1.5vw, 1.125rem);
            color: var(--gray);
            margin-bottom: 2rem;
            max-width: 540px;
        }

        .hero-stats {
            display: flex;
            gap: 2rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
        }

        .stat-value {
            font-size: 2.25rem;
            font-weight: 800;
            color: var(--primary);
            line-height: 1.2;
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--gray);
            font-weight: 500;
        }

        .hero-cta {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            padding: 1rem 2rem;
            border-radius: var(--radius-lg);
            border: none;
            font-weight: 600;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.25);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(16, 185, 129, 0.35);
        }

        .btn-secondary {
            background: white;
            color: var(--dark);
            padding: 1rem 2rem;
            border-radius: var(--radius-lg);
            border: 2px solid #e2e8f0;
            font-weight: 600;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            border-color: var(--primary);
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .hero-trust {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .trust-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray);
            font-size: 0.875rem;
            background: white;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            box-shadow: var(--shadow-sm);
        }

        .trust-item i {
            color: var(--success);
        }

        .hero-image-wrapper {
            position: relative;
        }

        .hero-image-main {
            position: relative;
            border-radius: var(--radius-2xl);
            overflow: hidden;
            box-shadow: var(--shadow-2xl);
        }

        .hero-img {
            width: 100%;
            height: auto;
            display: block;
            transition: transform 0.5s ease;
        }

        .hero-image-main:hover .hero-img {
            transform: scale(1.02);
        }

        .floating-card {
            position: absolute;
            bottom: -1.5rem;
            left: -1.5rem;
            background: white;
            padding: 1rem 1.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 600;
            animation: float 4s ease-in-out infinite;
        }

        .floating-card-2 {
            position: absolute;
            top: 2rem;
            right: -1rem;
            background: var(--gradient-4);
            padding: 0.75rem 1.25rem;
            border-radius: 50px;
            box-shadow: var(--shadow-lg);
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
            animation: float 5s ease-in-out infinite 1s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .live-indicator {
            width: 12px;
            height: 12px;
            background: var(--danger);
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }

        /* Services Section */
        .services-section {
            padding: 5rem 0;
            background: white;
        }

        .section-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-badge {
            display: inline-block;
            padding: 0.4rem 1.25rem;
            background: rgba(16, 185, 129, 0.1);
            color: var(--primary);
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.8rem;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .section-title {
            font-size: clamp(2rem, 4vw, 2.5rem);
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .section-description {
            font-size: 1.125rem;
            color: var(--gray);
            max-width: 600px;
            margin: 0 auto;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.5rem;
        }

        .service-card {
            background: white;
            padding: 2rem 1.5rem;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            border: 1px solid #f1f5f9;
            text-align: center;
        }

        .service-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary-light);
        }

        .service-icon {
            width: 70px;
            height: 70px;
            background: var(--gradient-1);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.75rem;
            margin: 0 auto 1.5rem;
        }

        .service-card h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }

        .service-card p {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .service-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1.5rem;
        }

        .service-btn {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            justify-content: center;
        }

        .service-btn:hover {
            background: var(--primary);
            color: white;
        }

        /* Weather & Currency Widgets */
        .widgets-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .weather-widget {
            background: var(--gradient-3);
            padding: 1.5rem;
            border-radius: var(--radius-xl);
            color: white;
        }

        .currency-widget {
            background: var(--gradient-dark);
            padding: 1.5rem;
            border-radius: var(--radius-xl);
            color: white;
        }

        .widget-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .widget-header i {
            font-size: 1.75rem;
        }

        .widget-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .weather-temp {
            font-size: 2.5rem;
            font-weight: 700;
        }

        .weather-desc {
            font-size: 1rem;
            opacity: 0.9;
            text-transform: capitalize;
        }

        .currency-display {
            font-size: 2rem;
            font-weight: 700;
        }

        .currency-note {
            font-size: 0.875rem;
            opacity: 0.8;
            margin-top: 0.5rem;
        }

        /* Device Tabs */
        .device-section {
            padding: 3rem 0;
            background: #f8fafc;
        }

        .device-tabs {
            display: flex;
            justify-content: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .device-tab {
            background: white;
            border: none;
            padding: 0.875rem 1.75rem;
            border-radius: var(--radius-lg);
            font-weight: 600;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
            border: 1px solid #e2e8f0;
        }

        .device-tab:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .device-tab.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        /* Products Grid */
        .products-section {
            padding: 4rem 0;
            background: white;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .product-card {
            background: white;
            border-radius: var(--radius-xl);
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            border: 1px solid #f1f5f9;
            position: relative;
            overflow: hidden;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }

        .product-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--danger);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            z-index: 1;
        }

        .product-category {
            display: inline-block;
            padding: 0.25rem 0.875rem;
            background: #f1f5f9;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--gray);
            margin-bottom: 1rem;
        }

        .product-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .product-description {
            color: var(--gray);
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .product-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .product-price .currency {
            font-size: 0.875rem;
            font-weight: 500;
        }

        .product-time {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            color: var(--gray);
            font-size: 0.8rem;
        }

        .product-features {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
            font-size: 0.75rem;
            color: var(--gray);
            flex-wrap: wrap;
        }

        .product-features span {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .product-features i {
            color: var(--success);
            font-size: 0.7rem;
        }

        .btn-book {
            background: var(--dark);
            color: white;
            border: none;
            padding: 0.75rem 1.25rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-book:hover {
            background: var(--primary);
            transform: translateY(-2px);
        }

        /* Hot Deals */
        .hot-deals {
            background: var(--gradient-2);
            padding: 2rem;
            border-radius: var(--radius-2xl);
            margin-bottom: 3rem;
            color: white;
        }

        .hot-deals-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .hot-deals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
        }

        .hot-deal-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            padding: 1.5rem;
            border-radius: var(--radius-xl);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .hot-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.25rem 0.875rem;
            border-radius: 50px;
            font-size: 0.75rem;
            margin-bottom: 1rem;
        }

        .hot-deal-card h4 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }

        .hot-price {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 1rem 0;
        }

        .hot-time {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            opacity: 0.9;
            margin-bottom: 1rem;
        }

        .btn-hot {
            background: white;
            color: var(--dark);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-hot:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* Flip Card - Referral Card */
        .referral-section {
            padding: 5rem 0;
            background: var(--dark);
            color: white;
        }

        .referral-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
        }

        .referral-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.1);
            padding: 0.4rem 1.25rem;
            border-radius: 50px;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }

        .referral-content h2 {
            font-size: clamp(1.75rem, 3vw, 2.25rem);
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .referral-content p {
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 1.5rem;
        }

        .referral-steps {
            display: flex;
            gap: 1.5rem;
            margin: 2rem 0;
            flex-wrap: wrap;
        }

        .step {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .step-number {
            width: 36px;
            height: 36px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .btn-referral {
            background: var(--primary);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: var(--radius-lg);
            font-weight: 600;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-referral:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .referral-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .referral-form input {
            padding: 1rem;
            border-radius: var(--radius-md);
            border: none;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .referral-form input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .referral-form input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .btn-submit {
            background: white;
            color: var(--dark);
            border: none;
            padding: 1rem 2rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background: var(--primary);
            color: white;
        }

        /* Flip Card Styling */
        .flip-card {
            background-color: transparent;
            width: 100%;
            max-width: 300px;
            height: 190px;
            perspective: 1000px;
            margin: 0 auto;
        }

        .flip-card-inner {
            position: relative;
            width: 100%;
            height: 100%;
            text-align: center;
            transition: transform 0.6s;
            transform-style: preserve-3d;
        }

        .flip-card:hover .flip-card-inner {
            transform: rotateY(180deg);
        }

        .flip-card-front, .flip-card-back {
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
            position: absolute;
            display: flex;
            flex-direction: column;
            justify-content: center;
            width: 100%;
            height: 100%;
            -webkit-backface-visibility: hidden;
            backface-visibility: hidden;
            border-radius: 1rem;
            padding: 1.5rem;
        }

        .flip-card-front {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
            color: white;
        }

        .flip-card-back {
            background: linear-gradient(135deg, #312e81 0%, #1e1b4b 100%);
            color: white;
            transform: rotateY(180deg);
        }

        .card-logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .card-logo i {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .card-logo span {
            font-weight: 700;
            font-size: 1rem;
        }

        .card-code-display {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: 0.25rem;
            margin: 0.5rem 0;
            color: var(--primary-light);
        }

        .card-label {
            font-size: 0.6rem;
            opacity: 0.7;
            letter-spacing: 1px;
            margin-bottom: 0.25rem;
        }

        .card-value {
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .card-reward {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--warning);
        }

        .strip {
            background: #000;
            height: 40px;
            margin: 0 -1.5rem 1rem;
        }

        .card-cvv {
            display: flex;
            justify-content: flex-end;
            margin-top: 1rem;
        }

        .cvv-code {
            background: white;
            color: var(--dark);
            padding: 0.25rem 1rem;
            border-radius: 4px;
            font-weight: 700;
            letter-spacing: 2px;
        }

        /* Footer */
        .footer {
            background: var(--darker);
            color: white;
        }

        .footer-top {
            padding: 3rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2.5rem;
        }

        .footer-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .footer-description {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
        }

        .footer-social {
            display: flex;
            gap: 0.75rem;
        }

        .footer-social a {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer-social a:hover {
            background: var(--primary);
            transform: translateY(-2px);
        }

        .footer-col h4 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
        }

        .footer-col ul {
            list-style: none;
        }

        .footer-col ul li {
            margin-bottom: 0.625rem;
        }

        .footer-col ul li a {
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.3s ease;
        }

        .footer-col ul li a:hover {
            color: var(--primary);
        }

        .contact-info li {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.875rem;
            margin-bottom: 0.75rem;
        }

        .contact-info i {
            color: var(--primary);
            margin-top: 0.2rem;
        }

        .footer-bottom {
            padding: 1.5rem 0;
        }

        .footer-bottom-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.5);
        }

        .footer-links {
            display: flex;
            gap: 1.5rem;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.5);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--primary);
        }

        /* WhatsApp Float */
        .whatsapp-float {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            width: 55px;
            height: 55px;
            background: #25D366;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.75rem;
            box-shadow: 0 4px 20px rgba(37, 211, 102, 0.4);
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 999;
        }

        .whatsapp-float:hover {
            transform: scale(1.1);
        }

        .whatsapp-tooltip {
            position: absolute;
            right: 65px;
            background: #1f2937;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            white-space: nowrap;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        .whatsapp-float:hover .whatsapp-tooltip {
            opacity: 1;
        }

        /* Success Message */
        .referral-success-box {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .copy-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .copy-btn:hover {
            background: var(--primary-dark);
        }

        /* Mobile Responsive */
        @media (max-width: 1024px) {
            .hero-container {
                gap: 2rem;
            }
        }

        @media (max-width: 768px) {
            .nav-menu {
                position: fixed;
                top: 70px;
                left: 0;
                right: 0;
                background: white;
                flex-direction: column;
                padding: 1.5rem;
                gap: 1rem;
                box-shadow: var(--shadow-lg);
                transform: translateY(-150%);
                transition: transform 0.3s ease;
                z-index: 999;
            }

            .nav-menu.active {
                transform: translateY(0);
            }

            .navbar.scrolled .nav-menu {
                background: var(--dark);
            }

            .nav-toggle {
                display: flex;
            }

            .nav-toggle.active span:nth-child(1) {
                transform: rotate(45deg) translate(6px, 6px);
            }

            .nav-toggle.active span:nth-child(2) {
                opacity: 0;
            }

            .nav-toggle.active span:nth-child(3) {
                transform: rotate(-45deg) translate(6px, -6px);
            }

            .hero-container {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 2rem;
            }

            .hero-content {
                order: 2;
            }

            .hero-image-wrapper {
                order: 1;
            }

            .hero-badge-group {
                justify-content: center;
            }

            .hero-stats {
                justify-content: center;
            }

            .hero-cta {
                justify-content: center;
            }

            .hero-trust {
                justify-content: center;
            }

            .hero-description {
                margin-left: auto;
                margin-right: auto;
            }

            .floating-card {
                display: none;
            }

            .widgets-row {
                grid-template-columns: 1fr;
            }

            .referral-wrapper {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .referral-steps {
                justify-content: center;
            }

            .btn-referral {
                margin: 0 auto;
            }

            .footer-bottom-content {
                flex-direction: column;
                text-align: center;
            }

            .footer-links {
                justify-content: center;
                flex-wrap: wrap;
            }

            .btn-quick-quote span {
                display: none;
            }

            .btn-quick-quote {
                padding: 0.625rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 1rem;
            }

            .hero-title {
                font-size: 2rem;
            }

            .hero-stats {
                gap: 1rem;
            }

            .stat-value {
                font-size: 1.75rem;
            }

            .hero-cta {
                flex-direction: column;
                width: 100%;
            }

            .btn-primary, .btn-secondary {
                width: 100%;
                justify-content: center;
            }

            .services-grid {
                grid-template-columns: 1fr;
            }

            .products-grid {
                grid-template-columns: 1fr;
            }

            .device-tab {
                padding: 0.625rem 1.25rem;
                font-size: 0.875rem;
            }

            .section-title {
                font-size: 1.75rem;
            }

            .flip-card {
                max-width: 100%;
                height: 180px;
            }

            .footer-grid {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .footer-social {
                justify-content: center;
            }

            .contact-info li {
                justify-content: center;
            }

            .footer-brand {
                justify-content: center;
            }
        }

        /* Animation classes */
        [data-aos] {
            pointer-events: none;
        }

        [data-aos].aos-animate {
            pointer-events: auto;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar" id="navbar">
        <div class="container">
            <div class="nav-container">
                <div class="nav-brand">
                    <div class="brand-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <div class="brand-text">
                        <span class="brand-name">Erick Phone</span>
                        <span class="brand-sub">Repair</span>
                    </div>
                </div>
                
                <div class="nav-menu" id="navMenu">
                    <a href="#home" class="nav-link">Home</a>
                    <a href="#services" class="nav-link">Services</a>
                    <a href="#products" class="nav-link">Products</a>
                    <a href="#referral" class="nav-link">Refer & Earn</a>
                    <a href="#contact" class="nav-link">Contact</a>
                </div>
                
                <div class="nav-actions">
                    <button class="btn-quick-quote" onclick="openWhatsApp()">
                        <i class="fab fa-whatsapp"></i>
                        <span>Quick Quote</span>
                    </button>
                    <div class="nav-toggle" id="navToggle">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Enhanced Hero Section -->
    <section id="home" class="hero-section">
        <div class="hero-bg-animation"></div>
        <div class="container">
            <div class="hero-container">
                <div class="hero-content" data-aos="fade-right" data-aos-duration="800">
                    <div class="hero-badge-group">
                        <div class="hero-badge">
                            <span class="badge-pulse"></span>
                            <span><i class="fas fa-map-marker-alt"></i> Munyu Road, CBD</span>
                        </div>
                        <div class="hero-badge offer">
                            <i class="fas fa-gift"></i>
                            <span>20% OFF First Repair</span>
                        </div>
                    </div>
                    
                    <h1 class="hero-title">
                        <span class="title-main">Premium Phone</span>
                        <span class="title-highlight">Repair Experts</span>
                        <span class="title-main">In Nairobi</span>
                    </h1>
                    
                    <p class="hero-description">
                        <i class="fas fa-check-circle" style="color: var(--success);"></i> 
                        Same-day service • Genuine parts • 90-day warranty • Free diagnostic
                    </p>
                    
                    <div class="hero-stats">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo number_format($stats['repairs_count'] ?? 5000); ?>+</div>
                            <div class="stat-label">Repairs Done</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">4.95★</div>
                            <div class="stat-label">Customer Rating</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">30min</div>
                            <div class="stat-label">Quick Service</div>
                        </div>
                    </div>
                    
                    <div class="hero-cta">
                        <button class="btn-primary" onclick="openWhatsApp()">
                            <i class="fab fa-whatsapp"></i>
                            Get Free Quote
                            <i class="fas fa-arrow-right"></i>
                        </button>
                        <button class="btn-secondary" onclick="getDirections()">
                            <i class="fas fa-map-marker-alt"></i>
                            Find Our Shop
                        </button>
                    </div>
                    
                    <div class="hero-trust">
                        <div class="trust-item">
                            <i class="fas fa-shield-alt"></i>
                            <span>90-Day Warranty</span>
                        </div>
                        <div class="trust-item">
                            <i class="fas fa-bolt"></i>
                            <span>Same Day Repair</span>
                        </div>
                        <div class="trust-item">
                            <i class="fas fa-medal"></i>
                            <span>Certified Techs</span>
                        </div>
                    </div>
                </div>
                
                <div class="hero-image-wrapper" data-aos="fade-left" data-aos-duration="800" data-aos-delay="200">
                    <div class="hero-image-main">
                        <img src="assets/images/erick.jpg" alt="Erick Phone Repair Expert" class="hero-img" onerror="this.src='https://placehold.co/600x600/10b981/white?text=Phone+Repair+Expert'">
                    </div>
                    <div class="floating-card">
                        <span class="live-indicator"></span>
                        <span><strong>Live:</strong> 3 repairs ongoing</span>
                    </div>
                    <div class="floating-card-2">
                        <i class="fas fa-star" style="color: #FFD700;"></i>
                        <span>Top Rated 2024</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Weather & Currency Widgets -->
    <section class="container" data-aos="fade-up">
        <div class="widgets-row">
            <div class="weather-widget" id="weatherWidget">
                <div class="widget-header">
                    <i class="fas fa-cloud-sun"></i>
                    <div>
                        <h3>Nairobi Weather</h3>
                        <p style="opacity: 0.8; font-size: 0.875rem;">Current conditions</p>
                    </div>
                </div>
                <div id="weatherData">
                    <div class="weather-temp">--°C</div>
                    <div class="weather-desc">Loading...</div>
                    <div style="display: flex; gap: 1.5rem; margin-top: 1rem; font-size: 0.875rem;">
                        <span><i class="fas fa-tint"></i> <span id="humidity">--</span>%</span>
                        <span><i class="fas fa-wind"></i> <span id="windSpeed">--</span> m/s</span>
                    </div>
                </div>
            </div>
            
            <div class="currency-widget">
                <div class="widget-header">
                    <i class="fas fa-dollar-sign"></i>
                    <div>
                        <h3>Currency Converter</h3>
                        <p style="opacity: 0.8; font-size: 0.875rem;">Live Exchange Rate</p>
                    </div>
                </div>
                <div class="currency-display">
                    <span>1 USD = </span>
                    <span id="kesRate">--</span>
                    <span>KES</span>
                </div>
                <p class="currency-note">Updated hourly • Source: OpenExchangeRates</p>
            </div>
        </div>
    </section>
    
    <!-- Services Section -->
    <section id="services" class="services-section">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <span class="section-badge">Our Services</span>
                <h2 class="section-title">Expert Phone Repair Services</h2>
                <p class="section-description">Premium quality repairs with genuine parts and warranty</p>
            </div>
            
            <div class="services-grid">
                <div class="service-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="service-icon">
                        <i class="fas fa-mobile-screen"></i>
                    </div>
                    <h3>Screen Replacement</h3>
                    <p>Premium OLED/LCD screens for all major brands</p>
                    <div class="service-price">From KSH 3,500</div>
                    <button class="service-btn" onclick="selectService('Screen Replacement')">
                        Book Now <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
                
                <div class="service-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="service-icon">
                        <i class="fas fa-battery-full"></i>
                    </div>
                    <h3>Battery Replacement</h3>
                    <p>High-capacity batteries with 1-year warranty</p>
                    <div class="service-price">From KSH 2,000</div>
                    <button class="service-btn" onclick="selectService('Battery Replacement')">
                        Book Now <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
                
                <div class="service-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="service-icon">
                        <i class="fas fa-charging-station"></i>
                    </div>
                    <h3>Charging Port Fix</h3>
                    <p>Fast and reliable charging port repair</p>
                    <div class="service-price">From KSH 1,500</div>
                    <button class="service-btn" onclick="selectService('Charging Port Fix')">
                        Book Now <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
                
                <div class="service-card" data-aos="fade-up" data-aos-delay="400">
                    <div class="service-icon">
                        <i class="fas fa-water"></i>
                    </div>
                    <h3>Water Damage</h3>
                    <p>Professional ultrasonic cleaning and repair</p>
                    <div class="service-price">From KSH 2,500</div>
                    <button class="service-btn" onclick="selectService('Water Damage Repair')">
                        Book Now <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Device Selection -->
    <section class="device-section">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <span class="section-badge">Select Your Device</span>
                <h2 class="section-title">What device needs repair?</h2>
                <p class="section-description">Choose for accurate pricing and service time</p>
            </div>
            
            <div class="device-tabs" data-aos="fade-up">
                <button class="device-tab active" onclick="filterProducts('all')">
                    <i class="fas fa-th-large"></i> All Devices
                </button>
                <button class="device-tab" onclick="filterProducts('iPhone')">
                    <i class="fab fa-apple"></i> iPhone
                </button>
                <button class="device-tab" onclick="filterProducts('Samsung')">
                    <i class="fas fa-mobile-alt"></i> Samsung
                </button>
                <button class="device-tab" onclick="filterProducts('Battery')">
                    <i class="fas fa-battery-three-quarters"></i> Battery
                </button>
            </div>
        </div>
    </section>
    
    <!-- Products Section -->
    <section id="products" class="products-section">
        <div class="container">
            <?php if ($hotProductsResult->num_rows > 0): ?>
            <div class="hot-deals" data-aos="fade-up">
                <div class="hot-deals-header">
                    <i class="fas fa-fire"></i>
                    <h3>🔥 Hot Deals This Week</h3>
                </div>
                <div class="hot-deals-grid">
                    <?php while ($hot = $hotProductsResult->fetch_assoc()): ?>
                    <div class="hot-deal-card">
                        <div class="hot-badge">
                            <i class="fas fa-fire"></i>
                            <?php echo ucfirst($hot['is_hot']); ?> Deal
                        </div>
                        <h4><?php echo htmlspecialchars($hot['name']); ?></h4>
                        <p><?php echo htmlspecialchars(substr($hot['description'], 0, 50)) . '...'; ?></p>
                        <div class="hot-price">KSH <?php echo number_format($hot['price']); ?></div>
                        <div class="hot-time"><i class="far fa-clock"></i> <?php echo htmlspecialchars($hot['repair_time']); ?></div>
                        <button class="btn-hot" onclick="openWhatsAppWithProduct('<?php echo addslashes($hot['name']); ?>')">
                            Claim Deal <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="section-header" data-aos="fade-up">
                <span class="section-badge">Pricing</span>
                <h2 class="section-title">Transparent Pricing, Quality Service</h2>
                <p class="section-description">All repairs include free diagnostic and 90-day warranty</p>
            </div>
            
            <div class="products-grid" id="productsGrid">
                <?php 
                $productsResult->data_seek(0);
                while ($product = $productsResult->fetch_assoc()): 
                ?>
                <div class="product-card" data-category="<?php echo htmlspecialchars($product['category']); ?>" data-aos="fade-up">
                    <?php if ($product['is_hot'] != 'none'): ?>
                    <div class="product-badge">
                        <i class="fas fa-fire"></i> Hot
                    </div>
                    <?php endif; ?>
                    
                    <div class="product-category">
                        <i class="fas fa-tag"></i>
                        <?php echo htmlspecialchars($product['category']); ?>
                    </div>
                    
                    <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p class="product-description"><?php echo htmlspecialchars(substr($product['description'], 0, 60)) . '...'; ?></p>
                    
                    <div class="product-meta">
                        <div class="product-price">
                            <span class="currency">KSH</span>
                            <span class="amount"><?php echo number_format($product['price']); ?></span>
                        </div>
                        <div class="product-time">
                            <i class="far fa-clock"></i>
                            <?php echo htmlspecialchars($product['repair_time']); ?>
                        </div>
                    </div>
                    
                    <div class="product-features">
                        <span><i class="fas fa-check-circle"></i> Free Diagnostic</span>
                        <span><i class="fas fa-shield-alt"></i> 90-Day Warranty</span>
                    </div>
                    
                    <button class="btn-book" onclick="openWhatsAppWithProduct('<?php echo addslashes($product['name']); ?>')">
                        Book Service <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>
    
    <!-- Referral Section with Flip Card -->
    <section id="referral" class="referral-section">
        <div class="container">
            <div class="referral-wrapper">
                <div class="referral-content" data-aos="fade-right">
                    <span class="referral-badge"><i class="fas fa-gift"></i> Earn Rewards</span>
                    <h2>Refer Friends & Earn KSH <?php echo $settings['weekly_reward'] ?? 200; ?></h2>
                    <p>Share your unique referral code. When friends get their phone repaired, you both earn rewards!</p>
                    
                    <div class="referral-steps">
                        <div class="step"><span class="step-number">1</span> Get code</div>
                        <div class="step"><span class="step-number">2</span> Share</div>
                        <div class="step"><span class="step-number">3</span> Earn</div>
                    </div>
                    
                    <div id="referral-area">
                        <button class="btn-referral" onclick="showReferralForm()">
                            <i class="fas fa-users"></i> Get Your Referral Code
                        </button>
                    </div>
                    
                    <div id="referral-form" style="display: none;">
                        <form onsubmit="registerForReferral(event)" class="referral-form">
                            <input type="tel" id="ref-phone" placeholder="Your Phone Number (e.g., 0712345678)" required>
                            <input type="email" id="ref-email" placeholder="Your Email (Optional)">
                            <button type="submit" class="btn-submit">Generate My Code <i class="fas fa-arrow-right"></i></button>
                        </form>
                    </div>
                    
                    <div id="referral-success" style="display: none;"></div>
                </div>
                
                <div class="referral-illustration" data-aos="fade-left">
                    <div class="flip-card">
                        <div class="flip-card-inner">
                            <div class="flip-card-front">
                                <div class="card-logo">
                                    <i class="fas fa-tools"></i>
                                    <span>ERICK REPAIR</span>
                                </div>
                                <div class="card-label">REFERRAL CODE</div>
                                <div class="card-code-display" id="displayCode">----</div>
                                <div class="card-label">REWARDS BALANCE</div>
                                <div class="card-reward">KSH 0</div>
                                <div style="margin-top: auto; font-size: 0.6rem; opacity: 0.7;">
                                    <i class="far fa-credit-card"></i> Tap to flip
                                </div>
                            </div>
                            <div class="flip-card-back">
                                <div class="strip"></div>
                                <div style="text-align: left;">
                                    <div class="card-label">SHARE YOUR LINK</div>
                                    <div style="background: rgba(255,255,255,0.1); padding: 0.5rem; border-radius: 4px; font-size: 0.55rem; word-break: break-all; margin: 0.5rem 0;" id="displayLink">
                                        erickrepair.co.ke/?ref=----
                                    </div>
                                </div>
                                <div class="card-cvv">
                                    <div class="cvv-code" id="cvvCode">TAP</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="footer" id="contact">
        <div class="footer-top">
            <div class="container">
                <div class="footer-grid">
                    <div class="footer-col">
                        <div class="footer-brand">
                            <i class="fas fa-tools"></i>
                            <span>Erick Phone Repair</span>
                        </div>
                        <p class="footer-description">
                            Nairobi's most trusted phone repair experts. Fast, reliable, and affordable.
                        </p>
                        <div class="footer-social">
                            <a href="#"><i class="fab fa-facebook-f"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-tiktok"></i></a>
                            <a href="#"><i class="fab fa-whatsapp"></i></a>
                        </div>
                    </div>
                    
                    <div class="footer-col">
                        <h4>Quick Links</h4>
                        <ul>
                            <li><a href="#home">Home</a></li>
                            <li><a href="#services">Services</a></li>
                            <li><a href="#products">Products</a></li>
                            <li><a href="#referral">Referral Program</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-col">
                        <h4>Services</h4>
                        <ul>
                            <li><a href="#">iPhone Repair</a></li>
                            <li><a href="#">Samsung Repair</a></li>
                            <li><a href="#">Screen Replacement</a></li>
                            <li><a href="#">Battery Service</a></li>
                            <li><a href="#">Water Damage</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-col">
                        <h4>Contact Info</h4>
                        <ul class="contact-info">
                            <li><i class="fas fa-map-marker-alt"></i> Munyu Road, Opposite Jamia Mall, Nairobi CBD</li>
                            <li><i class="fas fa-phone"></i> <?php echo $settings['whatsapp_number'] ?? '+254 716 868 013'; ?></li>
                            <li><i class="fab fa-whatsapp"></i> <?php echo $settings['whatsapp_number'] ?? '+254 716 868 013'; ?></li>
                            <li><i class="fas fa-clock"></i> Mon-Sat: 8AM - 7PM</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <div class="container">
                <div class="footer-bottom-content">
                    <p>&copy; <?php echo date('Y'); ?> Erick Phone Repair. All rights reserved.</p>
                    <div class="footer-links">
                        <a href="#">Privacy Policy</a>
                        <a href="#">Terms of Service</a>
                        <a href="/admin/">Admin Login</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- WhatsApp Float -->
    <div class="whatsapp-float" onclick="openWhatsApp()">
        <i class="fab fa-whatsapp"></i>
        <span class="whatsapp-tooltip">Chat with us on WhatsApp!</span>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            once: true,
            offset: 100
        });
        
        // Weather API
        async function getWeather() {
            const apiKey = '<?php echo $weatherApiKey; ?>';
            if (apiKey === '291984eea38a423b07d4614a54436c26') {
                document.getElementById('weatherData').innerHTML = `
                    <div class="weather-temp">24°C</div>
                    <div class="weather-desc">Partly Cloudy</div>
                    <div style="display: flex; gap: 1.5rem; margin-top: 1rem; font-size: 0.875rem;">
                        <span><i class="fas fa-tint"></i> 65%</span>
                        <span><i class="fas fa-wind"></i> 3.5 m/s</span>
                    </div>
                `;
                return;
            }
            
            try {
                const response = await fetch(`https://api.openweathermap.org/data/2.5/weather?q=Nairobi&units=metric&appid=${apiKey}`);
                const data = await response.json();
                if (data.main) {
                    document.getElementById('weatherData').innerHTML = `
                        <div class="weather-temp">${Math.round(data.main.temp)}°C</div>
                        <div class="weather-desc">${data.weather[0].description}</div>
                        <div style="display: flex; gap: 1.5rem; margin-top: 1rem; font-size: 0.875rem;">
                            <span><i class="fas fa-tint"></i> ${data.main.humidity}%</span>
                            <span><i class="fas fa-wind"></i> ${data.wind.speed} m/s</span>
                        </div>
                    `;
                }
            } catch (error) {
                console.log('Weather API error:', error);
            }
        }
        
        // Currency API
        async function getExchangeRate() {
            const appId = '<?php echo $openExchangeAppId; ?>';
            if (appId === '31b4dbfc1ef74e2589ac7fb7b2aebec6') {
                document.getElementById('kesRate').textContent = '145.50';
                return;
            }
            
            try {
                const response = await fetch(`https://openexchangerates.org/api/latest.json?app_id=${appId}`);
                const data = await response.json();
                if (data.rates) {
                    document.getElementById('kesRate').textContent = data.rates.KES.toFixed(2);
                }
            } catch (error) {
                console.log('Exchange rate API error:', error);
                document.getElementById('kesRate').textContent = '145.50';
            }
        }
        
        getWeather();
        getExchangeRate();
        
        // WhatsApp Functions
        function openWhatsApp() {
            const msg = encodeURIComponent(`Hello Erick Phone Repair,\n\nI'd like to get a quote for phone repair services.\nLocation: Nairobi CBD`);
            window.open(`https://wa.me/254716868013?text=${msg}`, '_blank');
        }
        
        function openWhatsAppWithProduct(product) {
            const msg = encodeURIComponent(`Hello,\n\nI'm interested in: ${product}\n\nPlease provide pricing and availability.`);
            window.open(`https://wa.me/254716868013?text=${msg}`, '_blank');
        }
        
        function selectService(service) {
            openWhatsAppWithProduct(service);
        }
        
        function getDirections() {
            window.open('https://www.google.com/maps/search/Munyu+Road+Nairobi', '_blank');
        }
        
        function filterProducts(category) {
            document.querySelectorAll('.device-tab').forEach(tab => tab.classList.remove('active'));
            event.target.closest('.device-tab').classList.add('active');
            
            document.querySelectorAll('.product-card').forEach(card => {
                card.style.display = (category === 'all' || card.dataset.category?.includes(category)) ? 'block' : 'none';
            });
        }
        
        function showReferralForm() {
            document.getElementById('referral-area').style.display = 'none';
            document.getElementById('referral-form').style.display = 'block';
        }
        
        async function registerForReferral(event) {
            event.preventDefault();
            const phone = document.getElementById('ref-phone').value;
            const email = document.getElementById('ref-email').value;
            
            try {
                const response = await fetch('/api/users.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'register', phone: phone, email: email})
                });
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('referral-form').style.display = 'none';
                    document.getElementById('referral-success').style.display = 'block';
                    document.getElementById('referral-success').innerHTML = `
                        <div class="referral-success-box">
                            <h3 style="color: var(--success); margin-bottom: 1rem;"><i class="fas fa-check-circle"></i> Success!</h3>
                            <p style="margin-bottom: 0.5rem;">Your referral code:</p>
                            <p style="font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem; color: var(--primary-light);">${data.referral_code}</p>
                            <p style="margin-bottom: 0.5rem;">Share this link:</p>
                            <code style="background: rgba(255,255,255,0.1); padding: 0.75rem; display: block; border-radius: var(--radius-md); word-break: break-all; font-size: 0.8rem;">
                                ${window.location.origin}?ref=${data.referral_code}
                            </code>
                            <button onclick="copyReferralLink('${data.referral_code}')" class="copy-btn">
                                <i class="fas fa-copy"></i> Copy Link
                            </button>
                        </div>
                    `;
                    
                    // Update flip card
                    document.getElementById('displayCode').textContent = data.referral_code;
                    document.getElementById('displayLink').textContent = `${window.location.origin}?ref=${data.referral_code}`;
                    document.getElementById('cvvCode').textContent = data.referral_code;
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                alert('Network error. Please try again.');
            }
        }
        
        function copyReferralLink(code) {
            const link = `${window.location.origin}?ref=${code}`;
            navigator.clipboard.writeText(link).then(() => {
                alert('✅ Referral link copied to clipboard!');
            });
        }
        
        // Mobile Menu
        const navToggle = document.getElementById('navToggle');
        const navMenu = document.getElementById('navMenu');
        
        navToggle.addEventListener('click', function() {
            this.classList.toggle('active');
            navMenu.classList.toggle('active');
        });
        
        // Close menu on link click
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                navToggle.classList.remove('active');
                navMenu.classList.remove('active');
            });
        });
        
        // Navbar scroll
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });
        
        // Referral from URL
        const urlParams = new URLSearchParams(window.location.search);
        const refCode = urlParams.get('ref');
        if (refCode) {
            localStorage.setItem('referral_code', refCode);
            document.getElementById('displayCode').textContent = refCode;
            document.getElementById('displayLink').textContent = `${window.location.origin}?ref=${refCode}`;
            document.getElementById('cvvCode').textContent = refCode;
        }
        
        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
</body>
</html>
