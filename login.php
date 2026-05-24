<?php
if(session_status() === PHP_SESSION_NONE){ session_start(); }
include 'db.php';

$error = "";

// Handle Login
if(isset($_POST['login'])){
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // 🔹 Image Upload
    if(isset($_FILES['profile_img']) && $_FILES['profile_img']['error'] == 0){
        $targetDir = "uploads/";
        if(!is_dir($targetDir)){
            mkdir($targetDir, 0777, true);
        }
        $fileName = time() . "_" . basename($_FILES["profile_img"]["name"]);
        $targetFile = $targetDir . $fileName;
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','svg'];
        if(in_array($imageFileType, $allowed)){
            if(move_uploaded_file($_FILES["profile_img"]["tmp_name"], $targetFile)){
                $_SESSION['profile_img'] = $targetFile;
            }
        }
    }

    // 🔹 Login Check
    $stmt = $conn->prepare("SELECT * FROM users WHERE user=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if($res && $res->num_rows === 1){
        $user = $res->fetch_assoc();
        if(isset($user['pass']) && password_verify($password, $user['pass'])){
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['user'];
            $_SESSION['role']     = $user['role'];
            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid Username or Password";
        }
    } else {
        $error = "Invalid Username or Password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — QubitSale</title>
<link rel="icon" type="image/svg+xml" href="fav.svg">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: #050d1a;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    overflow: hidden;
    position: relative;
}

/* ── Ambient background ── */
.bg-orb {
    position: fixed;
    border-radius: 50%;
    filter: blur(70px);
    pointer-events: none;
    animation: orb-pulse 7s ease-in-out infinite;
}
.bg-orb-1 { width: 420px; height: 420px; background: rgba(0,180,173,0.07); top: -120px; right: -80px; animation-delay: 0s; }
.bg-orb-2 { width: 280px; height: 280px; background: rgba(0,180,173,0.05); bottom: -80px; left: -60px; animation-delay: 3.5s; }
.bg-orb-3 { width: 200px; height: 200px; background: rgba(0,80,200,0.04); top: 45%; left: 8%; animation-delay: 1.5s; }

@keyframes orb-pulse {
    0%, 100% { transform: scale(1);    opacity: 1; }
    50%       { transform: scale(1.18); opacity: 0.65; }
}

.grid-bg {
    position: fixed;
    inset: 0;
    background-image:
        linear-gradient(rgba(0,180,173,0.035) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,180,173,0.035) 1px, transparent 1px);
    background-size: 44px 44px;
    pointer-events: none;
}

/* Floating particles */
.particle {
    position: fixed;
    width: 3px;
    height: 3px;
    border-radius: 50%;
    background: rgba(0,180,173,0.45);
    animation: particle-rise linear infinite;
    pointer-events: none;
}
@keyframes particle-rise {
    0%   { transform: translateY(0)   translateX(0);  opacity: 0; }
    10%  { opacity: 1; }
    90%  { opacity: 1; }
    100% { transform: translateY(-110vh) translateX(18px); opacity: 0; }
}

/* ── Card ── */
.login-wrap {
    position: relative;
    z-index: 10;
    width: 100%;
    max-width: 410px;
    animation: card-in 0.65s cubic-bezier(0.16, 1, 0.3, 1) both;
}
@keyframes card-in {
    from { opacity: 0; transform: translateY(32px) scale(0.98); }
    to   { opacity: 1; transform: translateY(0)    scale(1); }
}

.login-box {
    background: rgba(12, 22, 42, 0.88);
    border: 1px solid rgba(0, 180, 173, 0.18);
    border-radius: 22px;
    padding: 42px 38px;
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    box-shadow: 0 32px 80px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.04);
}

/* ── Logo ── */
.logo-area {
    text-align: center;
    margin-bottom: 28px;
    animation: logo-in 0.8s ease 0.15s both;
}
@keyframes logo-in {
    from { opacity: 0; transform: scale(0.92); }
    to   { opacity: 1; transform: scale(1); }
}
.logo-area .logo-svg { width: 220px; display: inline-block; }
.logo-sub {
    display: block;
    font-size: 10px;
    letter-spacing: 3.5px;
    text-transform: uppercase;
    color: rgba(0,180,173,0.6);
    font-weight: 600;
    margin-top: 6px;
}

.divider {
    width: 100%;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(0,180,173,0.25), transparent);
    margin-bottom: 28px;
}

/* ── Error ── */
.error-box {
    background: rgba(255, 60, 60, 0.07);
    border: 1px solid rgba(255, 80, 80, 0.2);
    color: #ff8080;
    padding: 11px 16px;
    border-radius: 10px;
    font-size: 13px;
    text-align: center;
    margin-bottom: 18px;
    animation: shake 0.4s ease;
}
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    20%       { transform: translateX(-6px); }
    40%       { transform: translateX(6px); }
    60%       { transform: translateX(-4px); }
    80%       { transform: translateX(4px); }
}

/* ── Form ── */
.form-group { margin-bottom: 18px; }

label {
    display: block;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    color: rgba(148,163,184,0.75);
    margin-bottom: 7px;
}

.input-wrap { position: relative; }

input[type="text"],
input[type="password"] {
    width: 100%;
    padding: 12px 16px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(0,180,173,0.12);
    border-radius: 11px;
    color: #e2e8f0;
    font-size: 14px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    outline: none;
    transition: border-color 0.25s, background 0.25s, box-shadow 0.25s;
}
input[type="text"]:focus,
input[type="password"]:focus {
    border-color: rgba(0,180,173,0.5);
    background: rgba(0,180,173,0.05);
    box-shadow: 0 0 0 3px rgba(0,180,173,0.08);
}
input::placeholder { color: rgba(148,163,184,0.3); }

/* Password toggle */
.pw-wrap { position: relative; }
.pw-wrap input { padding-right: 46px; }

.toggle-pw {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    color: rgba(148,163,184,0.4);
    display: flex;
    align-items: center;
    padding: 4px;
    transition: color 0.2s;
}
.toggle-pw:hover { color: rgba(0,180,173,0.85); }
.toggle-pw svg { width: 18px; height: 18px; display: block; }

/* ── Button ── */
.btn-login {
    width: 100%;
    padding: 13px;
    margin-top: 6px;
    background: linear-gradient(135deg, #00b4ad 0%, #008f89 100%);
    border: none;
    border-radius: 11px;
    color: #fff;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 0.5px;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    transition: transform 0.15s, box-shadow 0.25s;
}
.btn-login::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.12), transparent);
    opacity: 0;
    transition: opacity 0.25s;
}
.btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 28px rgba(0,180,173,0.38);
}
.btn-login:hover::before { opacity: 1; }
.btn-login:active { transform: translateY(0); }

/* Shimmer sweep */
.btn-login .shimmer {
    position: absolute;
    top: 0;
    left: -100%;
    width: 55%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.13), transparent);
    animation: btn-shimmer 3.2s ease-in-out infinite 1.2s;
    pointer-events: none;
}
@keyframes btn-shimmer {
    0%   { left: -100%; }
    100% { left: 200%; }
}

/* ── Footer ── */
.login-footer {
    text-align: center;
    margin-top: 22px;
    font-size: 11px;
    color: rgba(148,163,184,0.28);
    letter-spacing: 0.3px;
}
</style>
</head>
<body>

<!-- Background -->
<div class="grid-bg"></div>
<div class="bg-orb bg-orb-1"></div>
<div class="bg-orb bg-orb-2"></div>
<div class="bg-orb bg-orb-3"></div>

<!-- Floating particles -->
<div class="particle" style="left:14%;top:95%;animation-duration:9s;animation-delay:0s;"></div>
<div class="particle" style="left:72%;top:92%;animation-duration:12s;animation-delay:2s;background:rgba(0,180,173,0.3);"></div>
<div class="particle" style="left:42%;top:98%;animation-duration:10s;animation-delay:4s;"></div>
<div class="particle" style="left:85%;top:90%;animation-duration:13s;animation-delay:1s;background:rgba(0,100,220,0.3);"></div>
<div class="particle" style="left:28%;top:96%;animation-duration:8s;animation-delay:3s;"></div>
<div class="particle" style="left:58%;top:93%;animation-duration:11s;animation-delay:5s;background:rgba(0,180,173,0.25);"></div>

<!-- Card -->
<div class="login-wrap">
  <div class="login-box">

    <div class="logo-area">
      <!-- QubitSale-01.svg (dark background version) -->
      <svg class="logo-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 595.28 226.77">
        <path d="M189.9,120.89c5.66,3,11.21,5.94,17.2,9.11-2.38,4.56-4.62,8.87-6.92,13.28,5.04.76,10.05.63,14.7-1.98,4.97-2.8,5.81-7.58,1.98-11.85-1.81-2.02-3.98-3.85-6.28-5.24-4.56-2.77-9.36-5.13-14-7.76-3.36-1.9-6.76-3.77-9.92-5.99-14.55-10.23-11.78-30.05-1.45-38.93,7.27-6.25,15.76-8.26,24.99-7.65,5.43.36,10.56,1.8,15.15,4.95,4.22,2.9,7.15,6.83,8.97,11.8-4.53,3.26-9,6.48-13.62,9.81-1.8-3.41-4.34-5.78-7.8-6.99-4.25-1.49-8.45-1.35-12.49.74-1.09.56-2.13,1.36-2.97,2.26-3.2,3.41-2.9,7.88.96,10.45,2.87,1.91,6.02,3.41,9.14,4.88,6.21,2.94,12.44,5.81,18.02,9.95,5.81,4.3,10.3,9.59,11.74,17.08,2.74,14.2-4.4,26.93-18.14,31.55-11.9,4-23.67,3.6-34.98-2.52-.51-.28-1.4-.18-1.96.09-26.24,12.75-56.88-.52-66.3-28.74-9.65-28.95,9.29-60.71,38.65-64.83,8.63-1.21,17.8-.08,24.67,3.13-4.59,4.77-7.59,10.38-8.88,17.02-7.81-2.3-15.31-1.84-22.46,2-5.51,2.96-9.71,7.33-12.56,13.05-5.67,11.38-3.8,24.98,4.69,34.37,7.88,8.72,19.71,11.1,27.73,8.6-5.54-6-11.1-11.76-19.84-12.61.32-5.4.63-10.72.98-16.56,3.02.65,5.95,1.09,8.77,1.91,6.19,1.82,11.18,5.67,15.73,10.23,2.92,2.92,5.94,5.72,9,8.65,2.26-1.57,5.13-6.44,7.5-13.29Z" style="fill:#00b4ad;"/>
        <path d="M275.15,82.03c9.73,0,17.19,7.46,17.19,17.08,0,4.39-1.56,8.33-4.17,11.33l3.39,3.82c.64.73.6,1.49-.11,2.15l-2.13,1.94c-.71.66-1.44.59-2.08-.12l-3.64-4.13c-2.47,1.34-5.36,2.1-8.45,2.1-9.73,0-17.19-7.46-17.19-17.08s7.46-17.08,17.19-17.08ZM275.15,110.34c1.53,0,3-.33,4.33-.92l-3.32-3.77c-.64-.73-.6-1.49.11-2.15l2.13-1.94c.71-.66,1.44-.59,2.08.12l3.41,3.89c1.19-1.79,1.88-4.01,1.88-6.46,0-6.37-4.67-11.23-10.62-11.23s-10.62,4.86-10.62,11.23,4.67,11.23,10.62,11.23Z" style="fill:#00b4ad;"/>
        <path d="M296.66,106.31v-13.61c0-.97.53-1.51,1.46-1.51h3.36c.94,0,1.46.54,1.46,1.51v12.93c0,2.71,2.06,4.84,4.49,4.84,2.98,0,5.38-2.41,5.38-6.25v-11.51c0-.97.53-1.51,1.46-1.51h3.36c.94,0,1.46.54,1.46,1.51v21.42c0,.97-.53,1.51-1.46,1.51h-3.36c-.94,0-1.46-.54-1.46-1.51v-1.2c-1.67,2.05-4.05,3.28-6.75,3.28-5.56,0-9.41-3.96-9.41-9.89Z" style="fill:#00b4ad;"/>
        <path d="M339.32,116.19c-3.32,0-6.02-1.32-7.9-3.49v1.42c0,.97-.53,1.51-1.46,1.51h-3.3c-.94,0-1.46-.54-1.46-1.51v-31.43c0-.97.53-1.51,1.46-1.51h3.3c.94,0,1.46.54,1.46,1.51v11.42c1.88-2.17,4.58-3.49,7.9-3.49,6.62,0,11.44,5.64,11.44,12.79s-4.83,12.79-11.44,12.79ZM337.97,96.35c-3.66,0-6.55,3.09-6.55,7.05s2.88,7.06,6.55,7.06,6.48-3.07,6.48-7.06-2.84-7.05-6.48-7.05Z" style="fill:#00b4ad;"/>
        <path d="M358.57,80.92c2.11,0,3.73,1.63,3.73,3.75s-1.62,3.75-3.73,3.75-3.73-1.63-3.73-3.75,1.62-3.75,3.73-3.75ZM356.88,91.18h3.36c.94,0,1.46.54,1.46,1.51v21.42c0,.97-.53,1.51-1.46,1.51h-3.36c-.94,0-1.46-.54-1.46-1.51v-21.42c0-.97.53-1.51,1.46-1.51Z" style="fill:#00b4ad;"/>
        <path d="M379.47,110.55c.23,0,.48,0,.73-.02.98-.02,1.51.47,1.51,1.46v1.93c0,.83-.3,1.46-1.08,1.75-.89.33-1.99.52-3.11.52-5.13,0-7.96-2.9-7.96-8.64v-10.74h-2.68c-.94,0-1.46-.54-1.46-1.51v-2.62c0-.97.53-1.51,1.46-1.51h2.68v-4.44c0-.92.43-1.44,1.3-1.63l3.39-.69c.98-.21,1.6.31,1.6,1.35v5.4h4.12c.94,0,1.46.54,1.46,1.51v2.62c0,.97-.53,1.51-1.46,1.51h-4.12v10.34c0,2.34.98,3.4,3.62,3.4Z" style="fill:#00b4ad;"/>
        <path d="M389.44,107.42c2.11,1.93,4.33,3.04,7.21,3.04,3.09,0,5.33-1.58,5.33-4.04,0-2.19-1.35-3.99-6.5-5.12-6.41-1.39-9.82-4.81-9.82-9.74,0-5.59,4.42-9.53,10.67-9.53,5.54,0,9.91,2.71,11.21,8.35.25,1.04-.3,1.65-1.3,1.65h-3.64c-.87,0-1.33-.45-1.65-1.3-.73-1.98-2.27-3.02-4.35-3.02-2.5,0-4.37,1.35-4.37,3.35,0,2.24,1.6,3.78,6.11,4.84,7.16,1.7,10.21,5.14,10.21,10.34,0,5.78-4.46,9.96-11.77,9.96-3.59,0-7.32-1.77-10.39-5.64-.57-.76-.5-1.49.11-2.15l.87-.94c.64-.71,1.35-.73,2.06-.05Z" style="fill:#fff;"/>
        <path d="M423.16,90.62c3.32,0,6,1.32,7.9,3.49v-1.41c0-.97.53-1.51,1.46-1.51h3.3c.94,0,1.46.54,1.46,1.51v21.42c0,.97-.53,1.51-1.46,1.51h-3.3c-.94,0-1.46-.54-1.46-1.51v-1.42c-1.9,2.17-4.58,3.49-7.9,3.49-6.61,0-11.44-5.64-11.44-12.79s4.83-12.79,11.44-12.79ZM424.51,110.46c3.66,0,6.55-3.07,6.55-7.06s-2.88-7.05-6.55-7.05-6.48,3.09-6.48,7.05,2.84,7.06,6.48,7.06Z" style="fill:#fff;"/>
        <path d="M444.95,81.18h3.36c.94,0,1.46.54,1.46,1.51v31.43c0,.97-.53,1.51-1.46,1.51h-3.36c-.94,0-1.46-.54-1.46-1.51v-31.43c0-.97.53-1.51,1.46-1.51Z" style="fill:#fff;"/>
        <path d="M467.9,111.03c2.72,0,4.83-.8,6.73-2.34.73-.59,1.44-.57,2.08.09l1.12,1.16c.66.68.71,1.46.05,2.15-2.47,2.43-5.63,4.11-9.96,4.11-8.1,0-13.48-5.64-13.48-12.79s5.54-12.79,12.89-12.79,12.43,5.54,12.43,12.36c0,.31-.02.66-.05.99-.09.92-.69,1.37-1.58,1.37h-17.33c.55,3.4,3.3,5.69,7.1,5.69ZM460.97,100.71h12.31c-.69-2.9-2.93-4.93-6-4.93s-5.47,1.96-6.32,4.93Z" style="fill:#fff;"/>
        <line x1="255.55" y1="122.15" x2="481.89" y2="122.15" style="fill:none;stroke:#fff;stroke-miterlimit:10;"/>
        <path d="M200.19,143.31c1.76.31,4.75.61,8.28-.18,1.37-.31,9.91-2.23,10.73-7.31.4-2.47-1.01-4.87-2.33-6.34-1.81-2.02-3.98-3.85-6.28-5.24-4.56-2.77-9.36-5.13-14-7.76-3.36-1.9-6.76-3.77-9.92-5.99-14.55-10.23-11.78-30.05-1.45-38.93,7.27-6.25,15.76-8.26,24.99-7.65,5.43.36,10.56,1.8,15.15,4.95,4.22,2.9,7.15,6.83,8.97,11.8-4.53,3.26-9,6.48-13.62,9.81-1.8-3.41-4.34-5.78-7.8-6.99-4.25-1.49-8.45-1.35-12.49.74-1.09.56-2.13,1.36-2.97,2.26-3.2,3.41-2.9,7.88.96,10.45,2.87,1.91,6.02,3.41,9.14,4.88,6.21,2.94,12.44,5.81,18.02,9.95,5.81,4.3,10.3,9.59,11.74,17.08,2.74,14.2-4.4,26.93-18.14,31.55-9.86,3.32-18.18,2.69-22.05,2.02-6.14-1.05-17.13-3.43-13.97-4.93,6.8-3.24,12.68-7.71,17.05-14.19Z" style="fill:#fff;"/>
      </svg>
      <span class="logo-sub">Point of Sale</span>
    </div>

    <div class="divider"></div>

    <?php if(!empty($error)): ?>
      <div class="error-box"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">

      <div class="form-group">
        <label for="username">Username</label>
        <div class="input-wrap">
          <input type="text" id="username" name="username" placeholder="Enter your username" required autocomplete="off">
        </div>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <div class="pw-wrap">
          <input type="password" id="password" name="password" placeholder="Enter your password" required>
          <button type="button" class="toggle-pw" id="togglePw" title="Show / hide password">
            <!-- Eye icon (shown by default) -->
            <svg id="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>
      </div>

      <button type="submit" name="login" class="btn-login">
        <span class="shimmer"></span>
        Sign In
      </button>

    </form>

    <p class="login-footer">QubitSale &copy; <?php echo date('Y'); ?> &mdash; All rights reserved</p>
  </div>
</div>

<script>
(function () {
    const toggleBtn = document.getElementById('togglePw');
    const pwInput   = document.getElementById('password');
    const eyeIcon   = document.getElementById('eye-icon');

    const EYE_OPEN  = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    const EYE_SLASH = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';

    toggleBtn.addEventListener('click', function () {
        const isHidden = pwInput.type === 'password';
        pwInput.type        = isHidden ? 'text' : 'password';
        eyeIcon.innerHTML   = isHidden ? EYE_SLASH : EYE_OPEN;
        toggleBtn.title     = isHidden ? 'Hide password' : 'Show password';
    });
})();
</script>

</body>
</html>