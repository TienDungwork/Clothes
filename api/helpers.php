<?php
if (!defined('LUXE_APP')) {
    exit('Direct access not allowed');
}

function jsonResponse($data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function errorResponse(string $message, int $statusCode = 400): void
{
    jsonResponse(['success' => false, 'message' => $message], $statusCode);
}

function getJsonInput(): array
{
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}

function requireAuth(): void
{
    if (!isset($_SESSION['user_id'])) {
        errorResponse('Chưa đăng nhập', 401);
    }
}

function requireAdmin(): void
{
    requireAuth();
    
    $db = Database::getInstance();
    $user = $db->query("SELECT role FROM users WHERE id = ?", [$_SESSION['user_id']])->fetch();
    
    if (!$user || $user['role'] !== 'admin') {
        errorResponse('Không có quyền truy cập', 403);
    }
}

function validateRequired(array $input, array $fields): void
{
    foreach ($fields as $field) {
        if (empty($input[$field])) {
            errorResponse("Vui lòng nhập {$field}");
        }
    }
}

function getMethod(): string
{
    return $_SERVER['REQUEST_METHOD'];
}

function checkMethod(string $allowed): void
{
    if (getMethod() !== $allowed) {
        errorResponse('Method not allowed', 405);
    }
}
