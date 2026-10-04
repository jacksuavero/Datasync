<?php
require_once './config/dbcon.php';
require_once './config/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$email = $_POST['email'];
	$password = $_POST['password'];

	if (login($email, $password, false)) {
		$role = $_SESSION['role'] ?? null;

		if ($role === 'admin') {
			header('Location: ./admin/index.php');
		} elseif ($role === 'judge') {
			header('Location: ./judge/index.php');
		} elseif ($role === 'superadmin') {
			header('Location: ./superadmin/index.php');
		}
		exit;
	} else {
		$error = "Invalid email or password.";
	}
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="author" content="AdminKit">

    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link rel="shortcut icon" href="./assets/img/logo-mini.svg">

    <title>DataSync - Real-Time Tabulation Platform</title>

    <link href="css2.css?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="css/light.css" rel="stylesheet">

    <!-- fontawesome cdn -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
        integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html, body {
    height: 100%;
    overflow-x: hidden;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    line-height: 1.6;
    color: #1a1a1a;
    background: #f8f9fc;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.header {
    padding: 15px 6%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    box-shadow: 0 2px 20px rgba(0,0,0,0.03);
}

.logo {
    font-size: 24px;
    font-weight: 700;
    color: #1a1a1a;
    display: flex;
    align-items: center;
    gap: 10px;
}

.logo-img {
    width: 40px;
    height: 32px;
    object-fit: contain;
}

.nav {
    display: flex;
    gap: 30px;
    align-items: center;
}

.nav-buttons {
    display: flex;
    gap: 12px;
}

.btn-login {
    padding: 10px 22px;
    background: #FFD93D;
    color: #1a1a1a;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-login:hover {
    background: #FFD93D;
    border-color: #FFB800;
}

.btn-primary {
    padding: 10px 22px;
    background: #007BC9;
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
}

.btn-primary:hover {
    background: #005A94;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 123, 201, 0.3);
}

.hero {
    padding: 100px 6% 60px;
    background: linear-gradient(180deg, #f8f9fc 0%, #E3F2FD 100%);
    position: relative;
    overflow: hidden;
    margin-top: 70px;
    flex: 1;
}

.hero-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    max-width: 1250px;
    margin: 0 auto;
    gap: 60px;
}

.hero-content {
    flex: 1;
    text-align: left;
    z-index: 1;
}

.hero-visual {
    flex: 1;
    position: relative;
    max-width: 550px;
    height: 550px;
    margin: 0 auto;
}

.hero h1 {
    font-size: 52px;
    font-weight: 800;
    margin-bottom: 16px;
    color: #1a1a1a;
    line-height: 1.15;
    letter-spacing: -0.02em;
}

.hero h1 .highlight {
    background: linear-gradient(135deg, #007BC9 0%, #0091EA 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    display: block;
    margin-top: 8px;
}

.hero-subtitle {
    font-size: 18px;
    color: #4a5568;
    margin-bottom: 35px;
    max-width: 600px;
    line-height: 1.7;
}

.cta-buttons {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.btn-demo, .btn-integrate {
    padding: 16px 36px;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 10px;
    border: none;
}

.btn-demo {
    background: white;
    color: #1a1a1a;
    border: 2px solid #E8E8E8;
}

.btn-demo:hover {
    background: #ffffffff;
    transform: translateY(-2px);
}

.btn-integrate {
    background: #007BC9;
    color: white;
    box-shadow: 0 8px 25px rgba(0, 123, 201, 0.35);
}

.btn-integrate:hover {
    background: #005A94;
    transform: translateY(-2px);
    box-shadow: 0 12px 35px rgba(0, 123, 201, 0.45);
}

.hero::before {
    content: '';
    position: absolute;
    top: -30%;
    left: 50%;
    transform: translateX(-50%);
    width: 800px;
    height: 800px;
    background: radial-gradient(circle, rgba(0, 123, 201, 0.08) 0%, transparent 60%);
    border-radius: 50%;
}

.visual-3d {
    position: absolute;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.center-table {
    width: 360px;
    height: 400px;
    background: linear-gradient(135deg, rgba(0, 123, 201, 0.95) 0%, rgba(0, 145, 234, 0.95) 100%);
    border-radius: 20px;
    position: relative;
    transform: perspective(1000px) rotateY(-15deg) rotateX(5deg);
    box-shadow: 
        0 30px 90px rgba(0, 123, 201, 0.4),
        0 10px 30px rgba(0, 0, 0, 0.15),
        inset 0 -2px 20px rgba(255, 255, 255, 0.2);
    animation: float 6s ease-in-out infinite;
    padding: 30px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.table-header {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 10px;
    padding: 15px;
    background: rgba(255, 255, 255, 0.25);
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(10px);
}

.header-cell {
    color: white;
    font-weight: 700;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 10px;
    padding: 12px 15px;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    animation: slideIn 0.5s ease-out backwards;
}

.table-row:nth-child(2) { animation-delay: 0.1s; }
.table-row:nth-child(3) { animation-delay: 0.2s; }
.table-row:nth-child(4) { animation-delay: 0.3s; }
.table-row:nth-child(5) { animation-delay: 0.4s; }

.cell {
    color: white;
    font-size: 13px;
    font-weight: 500;
    display: flex;
    align-items: center;
}

.score-badge {
    background: #FFD93D;
    color: #1a1a1a;
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: 700;
    font-size: 12px;
    box-shadow: 0 2px 8px rgba(255, 217, 61, 0.3);
}

.floating-node {
    position: absolute;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    box-shadow: 0 4px 12px rgba(0, 123, 201, 0.4);
}

.node-1 { top: 10%; left: 15%; width: 24px; height: 24px; background: #0091EA; animation: float-node 4s ease-in-out infinite; }
.node-2 { top: 25%; left: 8%; width: 18px; height: 18px; background: #FFD93D; animation: float-node 5s ease-in-out infinite 1s; }
.node-3 { top: 45%; left: 5%; width: 16px; height: 16px; background: #40C4FF; animation: float-node 4.5s ease-in-out infinite 0.5s; }
.node-4 { top: 65%; left: 12%; width: 20px; height: 20px; background: #FFC425; animation: float-node 5.5s ease-in-out infinite 1.5s; }
.node-5 { top: 15%; right: 12%; width: 22px; height: 22px; background: #007BC9; animation: float-node 4.8s ease-in-out infinite 0.8s; }
.node-6 { top: 35%; right: 8%; width: 18px; height: 18px; background: #FFE17B; animation: float-node 5.2s ease-in-out infinite 2s; }
.node-7 { top: 55%; right: 15%; width: 20px; height: 20px; background: #0091EA; animation: float-node 4.3s ease-in-out infinite 1.2s; }
.node-8 { bottom: 15%; left: 20%; width: 24px; height: 24px; background: #005A94; animation: float-node 5s ease-in-out infinite 0.3s; }
.node-9 { bottom: 18%; right: 18%; width: 22px; height: 22px; background: #FFD93D; animation: float-node 4.7s ease-in-out infinite 1.8s; }

.connection-line {
    position: absolute;
    height: 2px;
    background: linear-gradient(90deg, transparent, rgba(0, 123, 201, 0.3), transparent);
    transform-origin: left center;
}

.line-1 { top: 12%; left: 15%; width: 180px; transform: rotate(-25deg); }
.line-2 { top: 47%; left: 5%; width: 220px; transform: rotate(15deg); }
.line-3 { top: 67%; left: 12%; width: 200px; transform: rotate(-8deg); }
.line-4 { top: 18%; right: 12%; width: 160px; transform: rotate(35deg); }
.line-5 { top: 38%; right: 8%; width: 190px; transform: rotate(-20deg); }

.platform-card {
    position: absolute;
    background: white;
    border-radius: 16px;
    padding: 16px 24px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 600;
    font-size: 14px;
    animation: float-card 4s ease-in-out infinite;
    border: 2px solid #E8E8E8;
    transition: all 0.3s;
}

.platform-card:hover {
    border-color: #FFD93D;
    box-shadow: 0 12px 50px rgba(255, 217, 61, 0.2);
}

.card-icon {
    width: 36px;
    height: 36px;
    background: #007BC9;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 16px;
}

.card-1 { top: 8%; left: 5%; animation-delay: 0s; }
.card-2 { bottom: 12%; right: 8%; animation-delay: 1s; }

@keyframes float {
    0%, 100% { transform: perspective(1000px) rotateY(-15deg) rotateX(5deg) translateY(0px); }
    50% { transform: perspective(1000px) rotateY(-15deg) rotateX(5deg) translateY(-20px); }
}

@keyframes float-node {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-15px); }
}

@keyframes float-card {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-12px); }
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Tablet Responsive */
@media (max-width: 1024px) {
    .hero-inner {
        flex-direction: column;
        gap: 40px;
    }

    .hero-content {
        text-align: center;
    }

    .hero h1 { 
        font-size: 44px; 
    }

    .hero-subtitle {
        max-width: 100%;
        margin-left: auto;
        margin-right: auto;
    }

    .cta-buttons {
        justify-content: center;
    }

    .hero-visual { 
        max-width: 450px;
        height: 450px;
    }

    .center-table { 
        width: 320px; 
        height: 360px; 
        padding: 25px; 
    }

    .platform-card {
        padding: 12px 18px;
        font-size: 13px;
    }

    .card-icon {
        width: 32px;
        height: 32px;
        font-size: 14px;
    }
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .header {
        padding: 12px 5%;
    }

    .logo {
        font-size: 20px;
    }

    .logo-img {
        width: 32px;
        height: 26px;
    }

    .nav {
        gap: 0;
    }

    .nav-buttons {
        gap: 8px;
    }

    .btn-login, .btn-primary {
        padding: 8px 14px;
        font-size: 13px;
    }

    .hero {
        padding: 80px 5% 50px;
        margin-top: 60px;
    }

    .hero-inner {
        flex-direction: column;
        gap: 0;
    }

    .hero-content {
        text-align: center;
        width: 100%;
    }

    .hero h1 { 
        font-size: 32px;
        margin-bottom: 12px;
    }

    .hero-subtitle { 
        font-size: 16px;
        margin-bottom: 0;
    }

    .cta-buttons {
        display: none;
    }

    .hero-visual { 
        width: 100%;
        height: auto;
        max-width: 100%;
        margin-top: 30px;
    }

    .visual-3d {
        position: relative;
        height: auto;
        flex-direction: column;
        gap: 20px;
    }

    .center-table { 
        width: 280px; 
        height: 320px;
        padding: 20px;
        gap: 10px;
        margin: 0 auto 20px;
        transform: none;
    }

    .table-header,
    .table-row {
        padding: 10px 12px;
        gap: 8px;
    }

    .header-cell {
        font-size: 10px;
    }

    .cell {
        font-size: 11px;
    }

    .score-badge {
        padding: 3px 8px;
        font-size: 11px;
    }

    .floating-node {
        display: none;
    }

    .connection-line {
        display: none;
    }

    .platform-card {
        position: relative;
        top: auto;
        left: auto;
        right: auto;
        bottom: auto;
        margin: 0 auto 12px;
        max-width: 280px;
        animation: none;
    }
}

/* Small Mobile */
@media (max-width: 480px) {
    .header {
        padding: 10px 4%;
    }

    .logo {
        font-size: 18px;
    }

    .logo-img {
        width: 28px;
        height: 24px;
    }

    .btn-login, .btn-primary {
        padding: 7px 12px;
        font-size: 12px;
    }

    .hero {
        padding: 70px 4% 40px;
    }

    .hero h1 { 
        font-size: 28px;
    }

    .hero-subtitle { 
        font-size: 15px;
    }

    .center-table { 
        width: 260px; 
        height: 300px;
        padding: 18px;
        gap: 8px;
    }

    .table-header,
    .table-row {
        padding: 9px 11px;
        gap: 7px;
    }

    .header-cell {
        font-size: 9px;
    }

    .cell {
        font-size: 10px;
    }

    .score-badge {
        padding: 3px 7px;
        font-size: 10px;
    }

    .platform-card {
        max-width: 260px;
        padding: 14px 20px;
        font-size: 13px;
    }

    .card-icon {
        width: 30px;
        height: 30px;
        font-size: 13px;
    }
}

/* Extra Small Devices */
@media (max-width: 360px) {
    .hero h1 { 
        font-size: 24px;
    }

    .hero-subtitle { 
        font-size: 14px;
    }

    .center-table { 
        width: 240px; 
        height: 280px;
        padding: 15px;
    }

    .table-header,
    .table-row {
        padding: 8px 10px;
    }

    .platform-card {
        max-width: 240px;
        padding: 12px 18px;
        font-size: 12px;
    }
}

/* Modal Styles */
#forgotPasswordModal .modal-content {
    border-radius: 1.25rem !important;
    overflow: hidden;
}

#forgotPasswordModal .icon-wrapper {
    width: 80px;
    height: 80px;
    background: rgba(0, 123, 201, 0.1);
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

#forgotPasswordModal .icon-wrapper i {
    font-size: 36px;
    color: #007BC9;
}

.modal-content {
    border-radius: 1rem !important;
}

.form-control:focus {
    border-color: #007BC9;
    box-shadow: 0 0 0 0.25rem rgba(0, 123, 201, 0.15);
}

.text-primary {
    color: #007BC9 !important;
}

.link-primary {
    color: #007BC9 !important;
}

.link-primary:hover {
    color: #FFD93D !important;
}
</style>
</head>
<body>
<header class="header">
    <div class="logo">
        <img src="./admin/assets/img/logo-mini.svg" alt="DataSync Logo" class="logo-img">
        <span>DataSync</span>
    </div>
    <nav class="nav">
        <div class="nav-buttons">
            <button type="button" class="btn-login btn" data-bs-toggle="modal" data-bs-target="#loginModal">Log in</button>
            <button class="btn btn-primary" onclick="window.location.href='request_account.php'">
                Request Access
            </button>
        </div>
    </nav>
</header>

<section class="hero">
    <div class="hero-inner">
        <div class="hero-content">
            <h1>
                Real-Time
                <span class="highlight">Data Tabulation Platform</span>
            </h1>
            <p class="hero-subtitle">
                DataSync helps teams process, update, and visualize tabular data <strong>instantly</strong>
                with secure, collaborative, and real-time synchronization.
            </p>
            <div class="cta-buttons">
                <button class="btn-demo" data-bs-toggle="modal" data-bs-target="#demoVideoModal">
                    <i class="fa-solid fa-play-circle"></i> Watch Demo
                </button>        
                <button class="btn-integrate" onclick="window.location.href='request_account.php'">
    Get Started 
    <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                        <path d="M7 13L11 9L7 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="hero-visual">
            <div class="visual-3d">
                <div class="connection-line line-1"></div>
                <div class="connection-line line-2"></div>
                <div class="connection-line line-3"></div>
                <div class="connection-line line-4"></div>
                <div class="connection-line line-5"></div>

                <div class="floating-node node-1"></div>
                <div class="floating-node node-2"></div>
                <div class="floating-node node-3"></div>
                <div class="floating-node node-4"></div>
                <div class="floating-node node-5"></div>
                <div class="floating-node node-6"></div>
                <div class="floating-node node-7"></div>
                <div class="floating-node node-8"></div>
                <div class="floating-node node-9"></div>

                <div class="center-table">
                    <div class="table-header">
                        <div class="header-cell">Contestant</div>
                        <div class="header-cell">Judge</div>
                        <div class="header-cell">Score</div>
                    </div>
                    <div class="table-row">
                        <div class="cell">Team Alpha</div>
                        <div class="cell">J1</div>
                        <div class="cell"><span class="score-badge">95</span></div>
                    </div>
                    <div class="table-row">
                        <div class="cell">Team Beta</div>
                        <div class="cell">J2</div>
                        <div class="cell"><span class="score-badge">92</span></div>
                    </div>
                    <div class="table-row">
                        <div class="cell">Team Gamma</div>
                        <div class="cell">J3</div>
                        <div class="cell"><span class="score-badge">88</span></div>
                    </div>
                    <div class="table-row">
                        <div class="cell">Team Delta</div>
                        <div class="cell">J1</div>
                        <div class="cell"><span class="score-badge">91</span></div>
                    </div>
                </div>

                <div class="platform-card card-1">
                    <div class="card-icon">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <span>Multi-Judge Support</span>
                </div>
                <div class="platform-card card-2">
                    <div class="card-icon">
                        <i class="fa-solid fa-bolt"></i>
                    </div>
                    <span>Real-time Sync</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Login Modal -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-5 pt-0">
                <div class="text-center mb-4">
                    <img src="./admin/assets/img/logo-mini.svg" alt="Datasync" width="80" class="mb-3">
                    <p class="text-muted">Sign in to your account</p>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center py-2 px-3" role="alert" style="font-size: 0.95rem;">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>
                        <div class="flex-grow-1"><?php echo htmlspecialchars($error); ?></div>
                        <button type="button" class="btn-close ms-2" data-bs-dismiss="alert" aria-label="Close" style="font-size: 0.7rem;"></button>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" novalidate>
                    <div class="mb-3">
                        <label for="email" class="form-label fw-medium">Email Address</label>
                        <input 
                            class="form-control form-control-lg" 
                            type="email" 
                            id="email"
                            name="email"
                            placeholder="Enter your email"
                            required
                            autocomplete="email"
                        >
                        <div class="invalid-feedback">
                            Please enter a valid email address.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-medium">Password</label>
                        <div class="input-group">
                            <input 
                                class="form-control form-control-lg" 
                                type="password" 
                                id="password"
                                name="password"
                                placeholder="Enter your password"
                                required
                                autocomplete="current-password"
                            >
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="fa-solid fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">
                            Please enter your password.
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="rememberMe" name="remember">
                            <label class="form-check-label" for="rememberMe">
                                Remember me
                            </label>
                        </div>
                        <a href="#" class="text-decoration-none small" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal" data-bs-dismiss="modal">
                            Forgot password?
                        </a>
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary btn-lg w-100 d-flex align-items-center justify-content-center gap-2">
                            <i class="fa-solid fa-right-to-bracket"></i>
                            <span>Sign In</span>
                        </button>
                    </div>

                    <div class="text-center">
                        <p class="text-muted mb-0">
                            Don't have an account? 
                            <a href="request_account.php" class="link-primary text-decoration-none fw-medium">
                                Request here
                            </a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Forgot Password Modal -->
<div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 pt-2 text-center">
                <div class="icon-container mb-3">
                    <div class="icon-wrapper">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                </div>
                
                <h4 class="fw-bold mb-3">Password Reset Required</h4>
                
                <p class="text-muted mb-4 px-3">
                    Password reset requests must be handled by the <strong class="text-dark">system administrator</strong>. 
                    Please contact your admin or event organizer to recover your account credentials.
                </p>
                <h6 class="fw-bold mb-3 d-flex justify-content-center align-items-center">
                    <i class="fa-solid fa-headset me-2 text-primary"></i>
                    Contact Information
                </h6>
                <div class="alert alert-light border mb-0 text-start p-3">
                    <div class="contact-item mb-2 me-5 ms-5">
                        <i class="fa-solid fa-envelope text-primary "></i>
                        <div class="d-inline-block">
                            <small class="text-muted d-block" style="font-size: 11px;">Email</small>
                            <a href="mailto:datasyncteam@datasyncv1.online" class="text-decoration-none fw-medium text-dark">datasyncteam@datasyncv1.online</a>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fa-solid fa-phone text-primary me-3"></i>
                        <div class="d-inline-block">
                            <small class="text-muted d-block" style="font-size: 11px;">Phone</small>
                            <a href="tel:09355987056" class="text-decoration-none fw-medium text-dark">09355987056</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center pb-4">
                <button type="button" class="btn btn-primary px-5 py-2" data-bs-dismiss="modal">
                    Got it
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Demo Video Modal -->
<div class="modal fade" id="demoVideoModal" tabindex="-1" aria-labelledby="demoVideoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header">
                <h5 class="modal-title" id="demoVideoLabel">Demo Video</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="ratio ratio-16x9">
                    <iframe id="demoVideo" src="https://www.youtube.com/embed/YOUR_VIDEO_ID" 
                            title="Demo Video" allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="js/app.js"></script>
<script src="js/datatables.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="ajax.js"></script>

<script>
// Stop video when modal is closed
var demoModal = document.getElementById('demoVideoModal');
demoModal.addEventListener('hidden.bs.modal', function () {
    var iframe = demoModal.querySelector('iframe');
    iframe.src = iframe.src;
});

// Toggle password visibility
document.getElementById('togglePassword')?.addEventListener('click', function() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
});

// Form validation
(function() {
    'use strict';
    const form = document.querySelector('form[method="post"]');
    
    if (form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    }
})();

// DataTables Responsive
document.addEventListener("DOMContentLoaded", function () {
    $("#datatables-reponsive").DataTable({
        responsive: true
    });
});

// SweetAlert notifications
<?php if (isset($_SESSION['login_error'])): ?>
    Swal.fire({
        icon: 'error',
        title: 'Login Failed',
        text: '<?php echo $_SESSION['login_error']; ?>',
        confirmButtonColor: '#007BC9'
    });
    <?php unset($_SESSION['login_error']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['login_success']) && isset($_SESSION['redirect_to'])): ?>
    Swal.fire({
        icon: 'success',
        title: 'Login Successful',
        text: 'Welcome back!',
        showConfirmButton: false,
        timer: 1500
    }).then(() => {
        window.location.href = '<?php echo $_SESSION['redirect_to']; ?>';
    });
    <?php unset($_SESSION['login_success'], $_SESSION['redirect_to']); ?>
<?php endif; ?>
</script>

</body>
</html>