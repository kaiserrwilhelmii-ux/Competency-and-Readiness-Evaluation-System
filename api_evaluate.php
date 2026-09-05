<?php
// api_evaluate.php - Automated Evaluation Endpoint

header('Content-Type: application/json; charset=UTF-8');
session_start();
include_once __DIR__ . '/db_connect.php';
include_once __DIR__ . '/ai_helper.php';

$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) {
    $data = $_POST;
}

$submission_id = isset($data['submission_id']) ? intval($data['submission_id']) : 0;

if (!$submission_id) {
    echo json_encode(['error' => 'No submission ID provided']);
    exit();
}

$stmt = $conn->prepare("SELECT * FROM submissions WHERE id = ?");
$stmt->bind_param("i", $submission_id);
$stmt->execute();
$result = $stmt->get_result();
$submission = $result->fetch_assoc();

if (!$submission) {
    echo json_encode(['error' => 'Submission not found']);
    exit();
}

$file_path = $submission['file_path'];
$title = $submission['title'];
$context = $submission['description'];
$file_content = "";
$pdf_base64 = null;

if (!empty($file_path)) {
    $clean_path = ltrim(str_replace(['../', '..\\'], '', $file_path), '/\\');
    $full_path = __DIR__ . '/' . $clean_path;

    if (file_exists($full_path)) {
        $ext = strtolower(pathinfo($full_path, PATHINFO_EXTENSION));

        if ($ext === 'pdf') {
            $pdf_base64 = base64_encode(file_get_contents($full_path));
            $file_content = "[System Note: PDF attached natively for multimodal analysis.]";
        } elseif ($ext === 'docx') {
            $file_content = read_docx_file($full_path);
        } elseif (in_array($ext, ['txt', 'php', 'html', 'css', 'js', 'json', 'md', 'sql', 'py', 'c'])) {
            $file_content = file_get_contents($full_path);
        } else {
            $file_content = "[System Note: The file is in a binary format (.$ext). Evaluating based on Title and Context.]";
        }
    } else {
        $file_content = "[System Note: Uploaded file not found on disk.]";
    }
}

$prompt = "You are an academic supervisor evaluating a student lesson plan based on the Philippine Professional Standards for Teachers (PPST).\n";
$prompt .= "DETAILS:\n";
$prompt .= "Title: $title\n";
$prompt .= "Student Context: $context\n\n";
$prompt .= "FILE CONTENT:\n" . substr($file_content, 0, 15000) . "\n\n";
$prompt .= "TASK: Provide a structured evaluation in JSON format with these fields: 'score' (1-10), 'title' (a short summary title), and 'feedback' (detailed critique).";

$ai_result = generateAIResponse($prompt, 'rubric_evaluator', $pdf_base64);

if (preg_match('/\{[\s\S]*\}/', $ai_result, $matches)) {
    echo $matches[0];
} else {
    echo json_encode([
        'score' => 8,
        'title' => 'Evaluation of ' . $title,
        'feedback' => $ai_result
    ]);
}

function read_docx_file($filename) {
    if (!$filename || !file_exists($filename)) return '';

    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive;
        if ($zip->open($filename) === TRUE) {
            if (($index = $zip->locateName('word/document.xml')) !== false) {
                $xml = $zip->getFromIndex($index);
                $dom = new DOMDocument;
                @$dom->loadXML($xml, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
                $text = strip_tags($dom->saveXML());
                $zip->close();
                return $text;
            }
            $zip->close();
        }
    }
    
    // Shell fallback if ZipArchive is disabled
    $xml = @shell_exec("unzip -p " . escapeshellarg($filename) . " word/document.xml 2>/dev/null");
    if ($xml) {
        $dom = new DOMDocument;
        @$dom->loadXML($xml, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
        return strip_tags($dom->saveXML());
    }

    return "[System Note: DOCX extraction unavailable on this server.]";
}
?>