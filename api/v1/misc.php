<?php
/**
 * Newsletter and settings endpoints
 */

// Prevent direct access
if (!defined('LUXE_APP')) {
    exit('Direct access not allowed');
}

$method = getMethod();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'newsletter.subscribe':
        checkMethod('POST');
        
        $input = getJsonInput();
        $email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
        
        if (!$email) {
            errorResponse('Email không hợp lệ');
        }
        
        $db = Database::getInstance();
        
        // Check if already subscribed
        $sql = "SELECT id, is_active FROM newsletter_subscribers WHERE email = ?";
        $existing = $db->query($sql, [$email])->fetch();
        
        if ($existing) {
            if ($existing['is_active']) {
                jsonResponse(['success' => true, 'message' => 'Email này đã đăng ký nhận tin']);
            } else {
                // Reactivate subscription
                $sql = "UPDATE newsletter_subscribers SET is_active = 1, subscribed_at = NOW() WHERE id = ?";
                $db->query($sql, [$existing['id']]);
                jsonResponse(['success' => true, 'message' => 'Đã kích hoạt lại đăng ký']);
            }
        } else {
            // New subscription
            $sql = "INSERT INTO newsletter_subscribers (email) VALUES (?)";
            $db->query($sql, [$email]);
            jsonResponse(['success' => true, 'message' => 'Đăng ký nhận tin thành công! 💌']);
        }
        break;

    case 'settings':
        checkMethod('GET');
        
        $db = Database::getInstance();
        $sql = "SELECT setting_key, setting_value FROM settings";
        $rows = $db->query($sql)->fetchAll();
        
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        jsonResponse(['success' => true, 'data' => $settings]);
        break;

    default:
        return false; // Not handled
}
