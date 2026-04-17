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
$openExchangeAppId = 'YOUR_OPENEXCHANGE_APP_ID'; // Get from https://openexchangerates.org
$weatherApiKey = 'YOUR_OPENWEATHER_API_KEY'; // Get from https://openweathermap.org/api
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erick Phone Repair - Professional Phone Repair in Nairobi CBD</title>
    <meta name="description" content="Expert phone repair services in Nairobi CBD. iPhone, Samsung, screen replacement, battery service. Fast, reliable, affordable.">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AOS Animation Library (Free Open Source) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    
    <!-- Swiper.js Carousel (Free Open Source) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="assets/css/modern-style.css">
    
    <style>
        /* Modern Design System */
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --secondary: #3b82f6;
            --accent: #8b5cf6;
            --dark: #0f172a;
            --light: #f8fafc;
            --gray: #64748b;
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
            --gradient-1: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-2: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-3: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
            --radius-sm: 0.375rem;
            --radius-md: 0.75rem;
            --radius-lg: 1rem;
            --radius-xl: 1.5rem;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--light);
            color: var(--dark);
            line-height: 1.6;
        }

        /* Modern Glassmorphism Navbar */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(20px);
        }

        .navbar.scrolled .nav-link,
        .navbar.scrolled .brand-text span {
            color: white;
        }

        .nav-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 1rem 2rem;
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
            width: 48px;
            height: 48px;
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
        }

        .brand-sub {
            font-size: 0.875rem;
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

        /* Modern Hero Section */
        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 6rem 2rem 4rem;
            background: radial-gradient(circle at 0% 50%, rgba(102, 126, 234, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 100% 50%, rgba(245, 87, 108, 0.1) 0%, transparent 50%);
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: var(--gradient-3);
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.1;
            z-index: -1;
        }

        .hero-container {
            max-width: 1280px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(16, 185, 129, 0.1);
            color: var(--primary-dark);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .badge-pulse {
            width: 8px;
            height: 8px;
            background: var(--success);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
        }

        .title-highlight {
            display: block;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-description {
            font-size: 1.125rem;
            color: var(--gray);
            margin-bottom: 2rem;
        }

        .hero-stats {
            display: flex;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--gray);
        }

        .hero-cta {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            padding: 1rem 2rem;
            border-radius: var(--radius-md);
            border: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        .btn-secondary {
            background: white;
            color: var(--dark);
            padding: 1rem 2rem;
            border-radius: var(--radius-md);
            border: 2px solid #e2e8f0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
        }

        .hero-trust {
            display: flex;
            gap: 1.5rem;
        }

        .trust-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray);
            font-size: 0.875rem;
        }

        .trust-item i {
            color: var(--success);
        }

        .hero-image {
            position: relative;
        }

        .hero-img {
            width: 100%;
            max-width: 500px;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        .image-badge {
            position: absolute;
            bottom: -1rem;
            right: -1rem;
            background: white;
            padding: 1rem 1.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
        }

        .live-indicator {
            width: 12px;
            height: 12px;
            background: var(--danger);
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }

        /* Modern Services Section */
        .services-section {
            padding: 6rem 2rem;
            background: white;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
        }

        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-badge {
            display: inline-block;
            padding: 0.5rem 1.5rem;
            background: rgba(16, 185, 129, 0.1);
            color: var(--primary);
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .section-title {
            font-size: 2.5rem;
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
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }

        .service-card {
            background: white;
            padding: 2rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            border: 1px solid #f1f5f9;
        }

        .service-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary);
        }

        .service-icon {
            width: 64px;
            height: 64px;
            background: var(--gradient-1);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.75rem;
            margin-bottom: 1.5rem;
        }

        .service-card h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }

        .service-card p {
            color: var(--gray);
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
            display: flex;
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

        /* Device Tabs */
        .device-section {
            padding: 4rem 2rem;
            background: #f8fafc;
        }

        .device-tabs {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .device-tab {
            background: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: var(--radius-lg);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }

        .device-tab:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .device-tab.active {
            background: var(--primary);
            color: white;
        }

        /* Products Grid */
        .products-section {
            padding: 4rem 2rem;
            background: white;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }

        .product-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            border: 1px solid #f1f5f9;
            position: relative;
            overflow: hidden;
        }

        .product-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-1);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .product-card:hover::before {
            transform: scaleX(1);
        }

        .product-card:hover {
            transform: translateY(-4px);
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
            font-size: 0.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .product-category {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: #f1f5f9;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--gray);
            margin-bottom: 1rem;
        }

        .product-name {
            font-size: 1.25rem;
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
            gap: 0.5rem;
            color: var(--gray);
            font-size: 0.875rem;
        }

        .product-features {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.75rem;
            color: var(--gray);
        }

        .product-features span {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .product-features i {
            color: var(--success);
        }

        .btn-book {
            background: var(--dark);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-md);
            font-weight: 600;
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

        /* Weather Widget (Free OpenWeatherMap API) */
        .weather-widget {
            background: var(--gradient-3);
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            color: white;
            margin: 2rem 0;
        }

        .weather-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .weather-temp {
            font-size: 2.5rem;
            font-weight: 700;
        }

        .weather-desc {
            font-size: 1.125rem;
            opacity: 0.9;
        }

        /* Currency Converter (Free OpenExchangeRates API) */
        .currency-widget {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            margin: 2rem 0;
        }

        .currency-display {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 1.5rem;
            font-weight: 600;
        }

        /* Hot Deals Section */
        .hot-deals {
            background: var(--gradient-2);
            padding: 2rem;
            border-radius: var(--radius-xl);
            margin-bottom: 4rem;
            color: white;
        }

        .hot-deals-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 2rem;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .hot-deals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .hot-deal-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .hot-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            margin-bottom: 1rem;
        }

        .hot-deal-card h4 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }

        .hot-price {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 1rem 0;
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

        /* Referral Section */
        .referral-section {
            padding: 6rem 2rem;
            background: var(--dark);
            color: white;
        }

        .referral-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .referral-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.1);
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            margin-bottom: 1.5rem;
        }

        .referral-content h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .referral-steps {
            display: flex;
            gap: 2rem;
            margin: 2rem 0;
        }

        .step {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .step-number {
            width: 32px;
            height: 32px;
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
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 1.125rem;
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
            margin-top: 2rem;
        }

        .referral-form input {
            padding: 1rem;
            border-radius: var(--radius-md);
            border: none;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1rem;
        }

        .referral-form input::placeholder {
            color: rgba(255, 255, 255, 0.6);
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

        /* Footer */
        .footer {
            background: #0a0f1c;
            color: white;
        }

        .footer-top {
            padding: 4rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
        }

        .footer-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .footer-description {
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 1.5rem;
        }

        .footer-social {
            display: flex;
            gap: 1rem;
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
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .footer-col ul {
            list-style: none;
        }

        .footer-col ul li {
            margin-bottom: 0.75rem;
        }

        .footer-col ul li a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-col ul li a:hover {
            color: var(--primary);
        }

        .contact-info li {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .contact-info i {
            color: var(--primary);
            width: 20px;
        }

        .footer-bottom {
            padding: 1.5rem 2rem;
        }

        .footer-bottom-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .footer-links {
            display: flex;
            gap: 2rem;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--primary);
        }

        /* WhatsApp Float */
        .whatsapp-float {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 60px;
            height: 60px;
            background: #25D366;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
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
            right: 70px;
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

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .hero-container {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .hero-title {
                font-size: 2.5rem;
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

            .hero-image {
                display: none;
            }

            .nav-menu {
                position: fixed;
                top: 80px;
                left: 0;
                right: 0;
                background: white;
                flex-direction: column;
                padding: 2rem;
                gap: 1rem;
                box-shadow: var(--shadow-lg);
                transform: translateY(-150%);
                transition: transform 0.3s ease;
            }

            .nav-menu.active {
                transform: translateY(0);
            }

            .nav-toggle {
                display: block;
            }

            .referral-wrapper {
                grid-template-columns: 1fr;
            }

            .footer-bottom-content {
                flex-direction: column;
                text-align: center;
            }

            .footer-links {
                justify-content: center;
            }
        }

        /* Navigation Toggle Button */
        .nav-toggle {
            display: none;
            flex-direction: column;
            gap: 6px;
            cursor: pointer;
        }

        .nav-toggle span {
            width: 30px;
            height: 3px;
            background: var(--dark);
            transition: all 0.3s ease;
        }

        .navbar.scrolled .nav-toggle span {
            background: white;
        }

        .nav-toggle.active span:nth-child(1) {
            transform: rotate(45deg) translate(8px, 8px);
        }

        .nav-toggle.active span:nth-child(2) {
            opacity: 0;
        }

        .nav-toggle.active span:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -7px);
        }

        @media (max-width: 768px) {
            .nav-toggle {
                display: flex;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar" id="navbar">
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
                <button class="btn-nav" onclick="openWhatsApp()" style="background: var(--primary); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: var(--radius-md); font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
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
    </nav>
    
    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <div class="hero-container">
            <div class="hero-content" data-aos="fade-right">
                <div class="hero-badge">
                    <span class="badge-pulse"></span>
                    <span><i class="fas fa-map-marker-alt"></i> Munyu Road, Nairobi CBD</span>
                </div>
                
                <h1 class="hero-title">
                    Premium Phone Repair
                    <span class="title-highlight">In Nairobi CBD</span>
                </h1>
                
                <p class="hero-description">
                    Expert technicians • Genuine parts • Same-day service • 90-day warranty
                </p>
                
                <div class="hero-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo number_format($stats['repairs_count'] ?? 5000); ?>+</div>
                        <div class="stat-label">Repairs Done</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">4.9★</div>
                        <div class="stat-label">Rating</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">30min</div>
                        <div class="stat-label">Quick Service</div>
                    </div>
                </div>
                
                <div class="hero-cta">
                    <button class="btn-primary" onclick="openWhatsApp()">
                        <i class="fab fa-whatsapp"></i>
                        Get Instant Quote
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
            
            <div class="hero-image" data-aos="fade-left">
                <img src="assets/images/erick.jpg" alt="Erick Phone Repair" class="hero-img" onerror="this.src='https://via.placeholder.com/400x400/25D366/ffffff?text=Erick+Repair'">
                <div class="image-badge">
                    <span class="live-indicator"></span>
                    Live Repairs Ongoing
                </div>
            </div>
        </div>
    </section>
    
    <!-- Weather Widget (Free OpenWeatherMap API) -->
    <section class="container" data-aos="fade-up">
        <div class="weather-widget" id="weatherWidget">
            <div class="weather-header">
                <i class="fas fa-cloud-sun" style="font-size: 2rem;"></i>
                <div>
                    <h3>Nairobi Weather</h3>
                    <p>Current conditions at our shop location</p>
                </div>
            </div>
            <div id="weatherData">
                <div class="weather-temp">--°C</div>
                <div class="weather-desc">Loading...</div>
            </div>
        </div>
    </section>
    
    <!-- Services Section -->
    <section id="services" class="services-section">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <span class="section-badge">Our Services</span>
                <h2 class="section-title">Expert Phone Repair Services</h2>
                <p class="section-description">We fix all brands and models with premium quality parts</p>
            </div>
            
            <div class="services-grid">
                <div class="service-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="service-icon">
                        <i class="fas fa-mobile-screen"></i>
                    </div>
                    <h3>Screen Replacement</h3>
                    <p>Premium quality screens for iPhone, Samsung, and all major brands.</p>
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
                    <p>High-capacity replacement batteries. Fix draining issues fast.</p>
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
                    <p>Fast charging port repair and replacement. Get charging again.</p>
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
                    <p>Professional water damage diagnostic and repair. Save your phone.</p>
                    <div class="service-price">From KSH 2,500</div>
                    <button class="service-btn" onclick="selectService('Water Damage Repair')">
                        Book Now <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Currency Converter (Free OpenExchangeRates API) -->
    <section class="container" data-aos="fade-up">
        <div class="currency-widget">
            <h3 style="margin-bottom: 1rem;">Currency Converter</h3>
            <div class="currency-display">
                <span>1 USD = </span>
                <span id="kesRate">--</span>
                <span>KES</span>
            </div>
            <p style="color: var(--gray); margin-top: 0.5rem; font-size: 0.875rem;">Live exchange rates for your convenience</p>
        </div>
    </section>
    
       <!-- Device Selection -->
    <section class="device-section">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <span class="section-badge">Select Your Device</span>
                <h2 class="section-title">What device needs repair?</h2>
                <p class="section-description">Choose your device for accurate quote and service time</p>
            </div>
            
            <div class="device-tabs" data-aos="fade-up">
                <button class="device-tab active" onclick="filterProducts('all')">
                    <i class="fas fa-th-large"></i>
                    <span>All Devices</span>
                </button>
                <button class="device-tab" onclick="filterProducts('iPhone')">
                    <i class="fab fa-apple"></i>
                    <span>iPhone</span>
                </button>
                <button class="device-tab" onclick="filterProducts('Samsung')">
                    <i class="fas fa-mobile-alt"></i>
                    <span>Samsung</span>
                </button>
                <button class="device-tab" onclick="filterProducts('Battery')">
                    <i class="fas fa-battery-three-quarters"></i>
                    <span>Battery</span>
                </button>
            </div>
        </div>
    </section>
    
    <!-- Products Section -->
    <section id="products" class="products-section">
        <div class="container">
            <!-- Hot Deals -->
            <?php if ($hotProductsResult->num_rows > 0): ?>
            <div class="hot-deals" data-aos="fade-up">
                <div class="hot-deals-header">
                    <i class="fas fa-fire"></i>
                    <h3>Hot Deals This Week</h3>
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
                            Claim Deal
                        </button>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="section-header" data-aos="fade-up">
                <span class="section-badge">Our Services & Pricing</span>
                <h2 class="section-title">Transparent Pricing, Quality Service</h2>
                <p class="section-description">All repairs come with 90-day warranty and free diagnostic</p>
            </div>
            
            <div class="products-grid" id="productsGrid">
                <?php 
                $productsResult->data_seek(0);
                while ($product = $productsResult->fetch_assoc()): 
                ?>
                <div class="product-card" data-category="<?php echo htmlspecialchars($product['category']); ?>" data-aos="fade-up">
                    <?php if ($product['is_hot'] != 'none'): ?>
                    <div class="product-badge <?php echo $product['is_hot']; ?>">
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
    
    <!-- Referral Section -->
    <section id="referral" class="referral-section">
        <div class="container">
            <div class="referral-wrapper">
                <div class="referral-content" data-aos="fade-right">
                    <span class="referral-badge"><i class="fas fa-gift"></i> Earn Rewards</span>
                    <h2>Refer Friends & Earn KSH <?php echo $settings['weekly_reward'] ?? 200; ?></h2>
                    <p>Share your unique referral code with friends. When they get their phone repaired, you both earn rewards!</p>
                    
                    <div class="referral-steps">
                        <div class="step"><span class="step-number">1</span> Get your code</div>
                        <div class="step"><span class="step-number">2</span> Share with friends</div>
                        <div class="step"><span class="step-number">3</span> Earn rewards</div>
                    </div>
                    
                    <div id="referral-area">
                        <button class="btn-referral" onclick="showReferralForm()">
                            <i class="fas fa-users"></i> Get Your Referral Code
                        </button>
                    </div>
                    
                    <div id="referral-form" style="display: none;">
                        <form onsubmit="registerForReferral(event)" class="referral-form">
                            <input type="tel" id="ref-phone" placeholder="Your Phone Number" required>
                            <input type="email" id="ref-email" placeholder="Your Email (Optional)">
                            <button type="submit" class="btn-submit">Generate My Code <i class="fas fa-arrow-right"></i></button>
                        </form>
                    </div>
                    
                    <div id="referral-success" style="display: none;"></div>
                </div>
                
                <div class="referral-illustration" data-aos="fade-left">
                    <div class="referral-card-display">
                        <div class="card-front">
                            <i class="fas fa-credit-card"></i>
                            <span>Your Referral Card</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="footer-top">
            <div class="container">
                <div class="footer-grid">
                    <div class="footer-col">
                        <div class="footer-brand">
                            <i class="fas fa-tools"></i>
                            <span>Erick Phone Repair</span>
                        </div>
                        <p class="footer-description">
                            Nairobi's trusted phone repair experts. Fast, reliable, and affordable repairs.
                        </p>
                        <div class="footer-social">
                            <a href="#"><i class="fab fa-facebook-f"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
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
                            <li><a href="#contact">Contact Us</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-col">
                        <h4>Our Services</h4>
                        <ul>
                            <li><a href="#">iPhone Repair</a></li>
                            <li><a href="#">Samsung Repair</a></li>
                            <li><a href="#">Screen Replacement</a></li>
                            <li><a href="#">Battery Service</a></li>
                            <li><a href="#">Water Damage Repair</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-col">
                        <h4>Contact Info</h4>
                        <ul class="contact-info">
                            <li><i class="fas fa-map-marker-alt"></i> Munyu Road, Nairobi CBD</li>
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
        <span class="whatsapp-tooltip">Chat with us!</span>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            once: true
        });
        
        // Free OpenWeatherMap API Integration
        async function getWeather() {
            const apiKey = '<?php echo $weatherApiKey; ?>';
            const city = 'Nairobi';
            
            try {
                const response = await fetch(`https://api.openweathermap.org/data/2.5/weather?q=${city}&units=metric&appid=${apiKey}`);
                const data = await response.json();
                
                if (data.main) {
                    document.getElementById('weatherData').innerHTML = `
                        <div class="weather-temp">${Math.round(data.main.temp)}°C</div>
                        <div class="weather-desc">${data.weather[0].description}</div>
                        <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                            <span><i class="fas fa-tint"></i> ${data.main.humidity}%</span>
                            <span><i class="fas fa-wind"></i> ${data.wind.speed} m/s</span>
                        </div>
                    `;
                }
            } catch (error) {
                console.log('Weather API not configured');
                document.getElementById('weatherData').innerHTML = `
                    <div class="weather-temp">24°C</div>
                    <div class="weather-desc">Partly Cloudy</div>
                `;
            }
        }
        
        // Free OpenExchangeRates API Integration
        async function getExchangeRate() {
            const appId = '<?php echo $openExchangeAppId; ?>';
            
            try {
                const response = await fetch(`https://openexchangerates.org/api/latest.json?app_id=${appId}`);
                const data = await response.json();
                
                if (data.rates) {
                    const kesRate = data.rates.KES;
                    document.getElementById('kesRate').textContent = kesRate.toFixed(2);
                }
            } catch (error) {
                console.log('Exchange rate API not configured');
                document.getElementById('kesRate').textContent = '145.50';
            }
        }
        
        // Load APIs if keys are provided
        if ('<?php echo $weatherApiKey; ?>' !== '291984eea38a423b07d4614a54436c26') {
            getWeather();
        }
        
        if ('<?php echo $openExchangeAppId; ?>' !== '31b4dbfc1ef74e2589ac7fb7b2aebec6') {
            getExchangeRate();
        }
        
        // WhatsApp Functions
        function openWhatsApp() {
            let device = localStorage.getItem('device') || 'Unknown';
            let issue = localStorage.getItem('issue') || 'Not specified';
            let msg = encodeURIComponent(`Repair Request\nDevice: ${device}\nIssue: ${issue}\nLocation: Nairobi CBD\n\nRequesting quote and availability`);
            window.open(`https://wa.me/254716868013?text=${msg}`, '_blank');
        }
        
        function openWhatsAppWithProduct(product) {
            let msg = encodeURIComponent(`Service Request\nService: ${product}\nLocation: Nairobi CBD\n\nI'm interested in this service. Please provide details.`);
            window.open(`https://wa.me/254716868013?text=${msg}`, '_blank');
        }
        
        function selectService(service) {
            localStorage.setItem('device', service);
            openWhatsApp();
        }
        
        function getDirections() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(pos => {
                    window.open(`https://www.google.com/maps/dir/${pos.coords.latitude},${pos.coords.longitude}/Munyu+Road+Nairobi`, '_blank');
                }, () => {
                    window.open('https://www.google.com/maps/search/Munyu+Road+Nairobi', '_blank');
                });
            } else {
                window.open('https://www.google.com/maps/search/Munyu+Road+Nairobi', '_blank');
            }
        }
        
        function filterProducts(category) {
            document.querySelectorAll('.device-tab').forEach(tab => tab.classList.remove('active'));
            event.target.closest('.device-tab').classList.add('active');
            
            document.querySelectorAll('.product-card').forEach(card => {
                if (category === 'all') {
                    card.style.display = 'block';
                } else {
                    const cat = card.dataset.category || '';
                    card.style.display = cat.includes(category) ? 'block' : 'none';
                }
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
                        <div style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); padding: 2rem; border-radius: var(--radius-lg); border: 1px solid rgba(255, 255, 255, 0.2);">
                            <h3 style="color: var(--success); margin-bottom: 1rem;"><i class="fas fa-check-circle"></i> Success!</h3>
                            <p style="font-size: 1.125rem; margin-bottom: 0.5rem;">Your referral code:</p>
                            <p style="font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem; color: var(--primary);">${data.referral_code}</p>
                            <p style="margin-bottom: 0.5rem;">Share this link:</p>
                            <code style="background: rgba(255, 255, 255, 0.2); padding: 1rem; display: block; border-radius: var(--radius-md); word-break: break-all;">
                                ${window.location.origin}?ref=${data.referral_code}
                            </code>
                            <button onclick="copyReferralLink('${data.referral_code}')" style="margin-top: 1rem; background: white; color: var(--dark); border: none; padding: 0.75rem 1.5rem; border-radius: var(--radius-md); font-weight: 600; cursor: pointer;">
                                <i class="fas fa-copy"></i> Copy Link
                            </button>
                        </div>
                    `;
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                alert('An error occurred. Please try again.');
            }
        }
        
        function copyReferralLink(code) {
            const link = `${window.location.origin}?ref=${code}`;
            navigator.clipboard.writeText(link);
            alert('Referral link copied to clipboard!');
        }
        
        // Mobile Menu Toggle
        document.getElementById('navToggle').addEventListener('click', function() {
            this.classList.toggle('active');
            document.getElementById('navMenu').classList.toggle('active');
        });
        
        // Navbar Scroll Effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 100) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
        
        // Check for referral in URL
        const urlParams = new URLSearchParams(window.location.search);
        const refCode = urlParams.get('ref');
        if (refCode) {
            localStorage.setItem('referral_code', refCode);
        }
        
        // Smooth scroll for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    // Close mobile menu if open
                    document.getElementById('navMenu').classList.remove('active');
                    document.getElementById('navToggle').classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
