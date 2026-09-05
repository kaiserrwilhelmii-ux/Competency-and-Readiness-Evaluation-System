<?php
// api_chat.php - Asynchronous JSON Endpoint for AI Support Copilot

error_reporting(0);
header("Content-Type: application/json; charset=UTF-8");
session_start();

// Ensure the user is authenticated
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "SESSION_EXPIRED"]);
    exit();
}

include_once __DIR__ . '/db_connect.php';
include_once __DIR__ . '/ai_helper.php';

$user_id = $_SESSION['user_id'];

// Read JSON or POST input
$raw_input = file_get_contents("php://input");
$input_data = json_decode($raw_input, true);

if (!is_array($input_data)) {
    $input_data = $_POST;
}

$message = trim($input_data['message'] ?? '');
$context = trim($input_data['context'] ?? '');
$mode    = trim($input_data['mode'] ?? 'mentor');

if (empty($message)) {
    echo json_encode(["error" => "Please provide a message."]);
    exit();
}

try {
    // 1. Log the user message
    $stmt = $conn->prepare("INSERT INTO chat_logs (user_id, sender, message) VALUES (?, 'user', ?)");
    if ($stmt) {
        $stmt->bind_param("is", $user_id, $message);
        $stmt->execute();
    }

    // 2. Generate AI response
    $full_context = !empty($context) ? "Context:\n" . $context . "\n\nUser Question: " . $message : $message;
    $ai_reply = generateAIResponse($full_context, $mode);

    // 3. Log the AI response
    $stmt_ai = $conn->prepare("INSERT INTO chat_logs (user_id, sender, message) VALUES (?, 'ai', ?)");
    if ($stmt_ai) {
        $stmt_ai->bind_param("is", $user_id, $ai_reply);
        $stmt_ai->execute();
    }

    echo json_encode([
        "success" => true,
        "reply" => $ai_reply
    ]);

} catch (Throwable $e) {
    echo json_encode([
        "error" => "An error occurred while processing your request: " . $e->getMessage()
    ]);
}
?>