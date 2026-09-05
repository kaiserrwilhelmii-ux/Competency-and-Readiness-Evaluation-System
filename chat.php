<?php
// chat.php - AI Chat Endpoint for Website Support Copilot

error_reporting(0);
header("Content-Type: application/json; charset=UTF-8");
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "SESSION_EXPIRED"]);
    exit;
}

include_once __DIR__ . '/db_connect.php';
include_once __DIR__ . '/ai_helper.php';

$user_id = $_SESSION['user_id'];
$raw_input = file_get_contents("php://input");
$input = json_decode($raw_input, true);

if (!is_array($input)) {
    $input = $_POST;
}

$userMessage = trim($input['message'] ?? '');
$context = trim($input['context'] ?? '');
$mode = trim($input['mode'] ?? 'mentor');

if (empty($userMessage)) {
    echo json_encode(["error" => "Message cannot be empty."]);
    exit;
}

try {
    // Log user message
    $stmt = $conn->prepare("INSERT INTO chat_logs (user_id, sender, message) VALUES (?, 'user', ?)");
    if ($stmt) {
        $stmt->bind_param("is", $user_id, $userMessage);
        $stmt->execute();
    }

    // Generate AI response
    $fullPrompt = !empty($context) ? "Context:\n" . $context . "\n\nUser Question: " . $userMessage : $userMessage;
    $reply = generateAIResponse($fullPrompt, $mode);

    // Log AI response
    $stmt_ai = $conn->prepare("INSERT INTO chat_logs (user_id, sender, message) VALUES (?, 'ai', ?)");
    if ($stmt_ai) {
        $stmt_ai->bind_param("is", $user_id, $reply);
        $stmt_ai->execute();
    }

    echo json_encode([
        "success" => true,
        "reply" => $reply
    ]);

} catch (Throwable $e) {
    echo json_encode([
        "error" => "Error processing AI request: " . $e->getMessage()
    ]);
}
?>