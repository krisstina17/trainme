<?php
require_once __DIR__ . '/get-avatar.php';

/**
 * Get user profile image with fallback
 */
function getUserProfileImage($user, $size = 200) {
    if ($user['slika_profila']) {
        return '/uploads/' . $user['slika_profila'];
    }
    return getAvatarUrl($user['id_uporabnik'] ?? 0);
}

/**
 * Convert alert to toast
 */
function convertAlertToToast($errors, $success = null) {
    if (!empty($errors)) {
        foreach ($errors as $error) {
            showToast($error, 'error');
        }
    }
    if ($success) {
        showToast($success, 'success');
    }
}
