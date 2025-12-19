<?php
/**
 * LUXE Fashion - API Helpers
 * 
 * Common functions and middleware for API endpoints
 */

// Prevent direct access
if (!defined('LUXE_APP')) {
    exit('Direct access not allowed');
}

/**
 * Send JSON response
 */
function jsonResponse($data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Send error response
 */
function errorResponse(string $message, int $statusCode = 400): void
{
    jsonResponse(['success' => false, 'message' => $message], $statusCode);
}

/**
 * Get JSON input from request body
 */
function getJsonInput(): array
{
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}

/**
 * Require user to be authenticated
 */
function requireAuth(): void
{
    if (!isset($_SESSION['user_id'])) {
        errorResponse('Chưa đăng nhập', 401);
    }
}

/**
 * Require user to be admin
 */
function requireAdmin(): void
{
    requireAuth();
    
    $db = Database::getInstance();
    $user = $db->query("SELECT role FROM users WHERE id = ?", [$_SESSION['user_id']])->fetch();
    
    if (!$user || $user['role'] !== 'admin') {
        errorResponse('Không có quyền truy cập', 403);
    }
}

/**
 * Validate required fields
 */
function validateRequired(array $input, array $fields): void
{
    foreach ($fields as $field) {
        if (empty($input[$field])) {
            errorResponse("Vui lòng nhập {$field}");
        }
    }
}

/**
 * Get request method
 */
function getMethod(): string
{
    return $_SERVER['REQUEST_METHOD'];
}

/**
 * Check if request method matches
 */
function checkMethod(string $allowed): void
{
    if (getMethod() !== $allowed) {
        errorResponse('Method not allowed', 405);
    }
}
