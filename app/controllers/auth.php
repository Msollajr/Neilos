<?php
// ============================================================
// Auth Controller — login, logout, change_password, otp_verify
// ============================================================

$action = $_GET['page'] ?? 'login';

// --- LOGOUT ---
if ($action === 'logout') {
    logoutUser();
    header('Location: ' . APP_URL . '/?page=login');
    exit;
}

// --- CHANGE PASSWORD ---
if ($action === 'change_password') {
    requireLogin();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrf();
        $newPass  = $_POST['new_password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';
        $errors   = [];
        if (strlen($newPass) < 8)            $errors[] = 'Password must be at least 8 characters.';
        if ($newPass !== $confirm)            $errors[] = 'Passwords do not match.';
        if (!preg_match('/[A-Z]/', $newPass)) $errors[] = 'Password must contain at least one uppercase letter.';
        if (!preg_match('/[0-9]/', $newPass)) $errors[] = 'Password must contain at least one number.';
        if (empty($errors)) {
            $db   = getDB();
            $hash = password_hash($newPass, PASSWORD_BCRYPT);
            $stmt = $db->prepare('UPDATE users SET password = ?, is_first_login = 0 WHERE id = ?');
            $stmt->execute([$hash, $_SESSION['user_id']]);
            $_SESSION['is_first_login'] = false;
            auditLog('Changed password', 'users', $_SESSION['user_id']);
            setFlash('success', 'Password changed successfully.');
            header('Location: ' . APP_URL . '/?page=otp_verify');
            exit;
        }
    }
    $pageTitle = 'Change Password';
    include APP_DIR . '/views/auth/change_password.php';
    exit;
}

// --- OTP VERIFY ---
if ($action === 'otp_verify') {
    requireLogin();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrf();
        $entered = trim($_POST['otp_code'] ?? '');
        $db = getDB();
        $stmt = $db->prepare('SELECT otp_code FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $stored = $stmt->fetchColumn();
        if ($entered === $stored) {
            $db->prepare('UPDATE users SET otp_verified = 1 WHERE id = ?')->execute([$_SESSION['user_id']]);
            $_SESSION['otp_verified'] = true;
            setFlash('success', 'OTP verified. Welcome to Neilos Partner Portal!');
            header('Location: ' . APP_URL . '/?page=dashboard');
            exit;
        }
        $otpError = 'Invalid OTP code. Please try again.';
    } else {
        // Generate new OTP
        $otp  = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $db   = getDB();
        $db->prepare('UPDATE users SET otp_code = ? WHERE id = ?')->execute([$otp, $_SESSION['user_id']]);
        $_SESSION['current_otp'] = $otp; // For simulation display
    }
    $pageTitle = 'OTP Verification';
    include APP_DIR . '/views/auth/otp_verify.php';
    exit;
}

// --- LOGIN ---
$loginError = '';
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . APP_URL . '/?page=dashboard');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1');
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            loginUser($user);
            $db->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$user['id']]);
            auditLog('Login', 'users', $user['id']);

            if ($user['is_first_login']) {
                header('Location: ' . APP_URL . '/?page=change_password');
            } elseif (!$user['otp_verified']) {
                header('Location: ' . APP_URL . '/?page=otp_verify');
            } else {
                header('Location: ' . APP_URL . '/?page=dashboard');
            }
            exit;
        } else {
            $loginError = 'Invalid username or password.';
        }
    } else {
        $loginError = 'Please enter your username and password.';
    }
}

$pageTitle = 'Sign In';
include APP_DIR . '/views/auth/login.php';
