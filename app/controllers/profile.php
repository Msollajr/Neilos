<?php
requireLogin();
$db   = getDB();
$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['profile_action'] ?? '';

    if ($action === 'update_profile') {
        $email = trim($_POST['email'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        if ($email && $mobile) {
            $db->prepare("UPDATE users SET email = ?, mobile = ? WHERE id = ?")->execute([$email, $mobile, $user['id']]);
            $_SESSION['email'] = $email;
            setFlash('success', 'Profile updated.');
        } else {
            setFlash('danger', 'Email and mobile are required.');
        }
    }

    if ($action === 'update_picture') {
        if (!empty($_FILES['profile_picture']['name'])) {
            try {
                $up = uploadFile($_FILES['profile_picture'], 'profiles');
                $stmt = $db->prepare("SELECT profile_picture FROM users WHERE id = ?");
                $stmt->execute([$user['id']]);
                $old = $stmt->fetchColumn();
                if ($old && file_exists(__DIR__ . '/../../public/' . $old)) {
                    unlink(__DIR__ . '/../../public/' . $old);
                }
                $db->prepare("UPDATE users SET profile_picture = ? WHERE id = ?")->execute([$up['path'], $user['id']]);
                $_SESSION['profile_picture'] = $up['path'];
                setFlash('success', 'Profile picture updated.');
            } catch (RuntimeException $e) {
                setFlash('danger', $e->getMessage());
            }
        }
    }

    if ($action === 'remove_picture') {
        $stmt = $db->prepare("SELECT profile_picture FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $old = $stmt->fetchColumn();
        if ($old && file_exists(__DIR__ . '/../../public/' . $old)) {
            unlink(__DIR__ . '/../../public/' . $old);
        }
        $db->prepare("UPDATE users SET profile_picture = NULL WHERE id = ?")->execute([$user['id']]);
        unset($_SESSION['profile_picture']);
        setFlash('success', 'Profile picture removed.');
    }

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $hash = $stmt->fetchColumn();
        if (!password_verify($current, $hash)) {
            setFlash('danger', 'Current password is incorrect.');
        } elseif (strlen($newPass) < 8) {
            setFlash('danger', 'Password must be at least 8 characters.');
        } elseif ($newPass !== $confirm) {
            setFlash('danger', 'Passwords do not match.');
        } else {
            $db->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([password_hash($newPass, PASSWORD_BCRYPT), $user['id']]);
            auditLog('Changed password', 'users', $user['id']);
            setFlash('success', 'Password changed successfully.');
        }
    }

    header('Location: ' . APP_URL . '/?page=profile');
    exit;
}

$stmt = $db->prepare("SELECT u.*, p.name as partner_name FROM users u LEFT JOIN partners p ON u.partner_id = p.id WHERE u.id = ?");
$stmt->execute([$user['id']]);
$profile = $stmt->fetch();

$pageTitle = 'My Profile';
include APP_DIR . '/views/layout/header.php';
include APP_DIR . '/views/profile/index.php';
include APP_DIR . '/views/layout/footer.php';