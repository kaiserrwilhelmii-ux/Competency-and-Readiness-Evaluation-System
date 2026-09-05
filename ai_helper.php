<?php
// ai_helper.php - Core AI Integration for Google Gemini API

// Ensure environment variables from .env are loaded
if (file_exists(__DIR__ . '/.env')) {
    $env_lines = @file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($env_lines) {
        foreach ($env_lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if (!getenv($name)) {
                putenv("$name=$value");
            }
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

function generateAIResponse($context_text, $mode = 'evaluator', $pdf_base64 = null) {
    if (!function_exists('curl_init')) {
        return "System Error: The cURL extension is not enabled in your PHP installation.";
    }

    $apiKey = getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? ($_SERVER['GEMINI_API_KEY'] ?? '')); 
    
    if (empty($apiKey)) {
        return "System Error: Google Gemini API key is missing. Please set the GEMINI_API_KEY environment variable.";
    }

    // Build Prompt based on Mode
    $is_rubric_mode = false;
    
    if ($mode === 'rubric_evaluator' || (is_string($context_text) && strpos($context_text, '100-point rubric') !== false)) {
        $is_rubric_mode = true;
        // If context_text already has the prompt, use it directly; otherwise construct the rubric prompt
        if (strpos($context_text, 'RETURN ONLY VALID JSON') !== false) {
            $prompt = $context_text;
        } else {
            $prompt = "You are a professional teacher evaluator grading a lesson plan based on the Philippine Professional Standards for Teachers (PPST). Use this exact 100-point rubric:
1. Objectives (Max 20 pts): Clear, measurable, HOTS aligned.
2. Content (Max 20 pts): Accurate, relevant to curriculum.
3. Methodology (Max 30 pts): Engaging strategies, well-structured flow.
4. Assessment (Max 20 pts): Valid tools aligned with objectives.
5. Formatting (Max 10 pts): Professional standard and mechanics.

Student Submission Context:
$context_text

CRITICAL: Return ONLY valid JSON format. Do not wrap in markdown code blocks. Structure:
{
    \"obj\": 18,
    \"con\": 16,
    \"meth\": 26,
    \"ass\": 17,
    \"fmt\": 9,
    \"total\": 86,
    \"feedback\": \"Detailed constructive critique highlighting strengths and areas for improvement...\"
}";
        }
    } elseif ($mode === 'evaluator') {
        $prompt = "You are an expert Cooperating Teacher and Pedagogy Evaluator.
Task: Evaluate the following student submission based on the PPST (Philippine Professional Standards for Teachers).

Context:
$context_text

CRITICAL OUTPUT RULES:
1. Start with [SCORE: X] (1-10).
2. STRICTLY PLAIN TEXT ONLY. No markdown, no bolding (**), no tables (|).
3. Use standard dashes (-) for lists.
4. Be objective, thorough, and professional.

Structure:
[SCORE: X]
STRENGTHS:
- Point 1
- Point 2
AREAS FOR IMPROVEMENT:
- Point 1
- Point 2
RECOMMENDATION:
(Actionable pedagogical summary)";
    } elseif ($mode === 'mentor') {
        $prompt = "You are a supportive, knowledgeable PPST Mentor and AI Support Copilot for student teachers.

$context_text

CRITICAL INSTRUCTION:
Respond DIRECTLY to the student's question or message. Do NOT automatically generate a full review or unsolicited summary unless asked. If the student greets you, greet them warmly and ask how you can assist with their lesson plan or portfolio.

OUTPUT RULES:
1. STRICTLY PLAIN TEXT ONLY. No bolding (**), no markdown symbols.
2. Keep feedback encouraging, actionable, and aligned with DepEd / PPST standards.
3. Use standard dashes (-) for bullet points.";
    } elseif ($mode === 'consultant') {
        $prompt = "You are an expert Educational Consultant assisting a Cooperating Teacher or Administrator Supervisor.

$context_text

CRITICAL INSTRUCTION:
Respond DIRECTLY to the supervisor's question. If the user greets you, greet them professionally and offer assistance with rubric scoring or analyzing student submissions.

OUTPUT RULES:
1. STRICTLY PLAIN TEXT ONLY. No bolding (**), no markdown symbols.
2. Be concise, direct, and professional.
3. Use dashes (-) for bulleted observations.";
    } else {
        // Raw / custom prompt pass-through
        $prompt = $context_text;
    }

    // Structure Request Payload
    $parts = [
        ["text" => $prompt]
    ];

    if ($pdf_base64 !== null) {
        $parts[] = [
            "inlineData" => [
                "mimeType" => "application/pdf",
                "data" => $pdf_base64
            ]
        ];
    }

    $postData = [
        "contents" => [
            [
                "parts" => $parts
            ]
        ]
    ];

    if ($is_rubric_mode) {
        $postData["generationConfig"] = [
            "responseMimeType" => "application/json"
        ];
    }

    // List of model candidates to try (handles model availability differences across accounts & regions)
    $preferred_model = getenv('GEMINI_MODEL') ?: ($_ENV['GEMINI_MODEL'] ?? 'gemini-1.5-flash');
    $models_to_try = array_unique([$preferred_model, 'gemini-1.5-flash', 'gemini-2.0-flash', 'gemini-2.5-flash', 'gemini-1.5-pro']);

    $last_error = "";

    foreach ($models_to_try as $model_name) {
        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/" . urlencode($model_name) . ":generateContent?key=" . $apiKey;

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); 
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_err = curl_error($ch);
        curl_close($ch);

        if (!empty($curl_err)) {
            return "CURL Connection Error: " . $curl_err;
        }

        $json = json_decode($response, true);

        if ($http_code === 200 && isset($json['candidates'][0]['content']['parts'][0]['text'])) {
            $raw_text = $json['candidates'][0]['content']['parts'][0]['text'];
            return $is_rubric_mode ? trim($raw_text) : cleanMarkdown($raw_text);
        }

        if (isset($json['error']['message'])) {
            $last_error = $json['error']['message'];
            // If model is not found, continue to next model in the fallback list
            if (strpos(strtolower($last_error), 'not found') !== false || strpos(strtolower($last_error), 'is not supported') !== false) {
                continue;
            }
            return "Google API Error: " . $last_error;
        }
    }

    return !empty($last_error) 
        ? "Google API Error: " . $last_error 
        : "AI Error: The server received an empty response from the AI provider.";
}

function cleanMarkdown($text) {
    $text = str_replace(['**', '__', '*'], '', $text);   
    $text = str_replace(['### ', '## ', '# '], '', $text);   
    $text = str_replace('|', ' - ', $text);   
    $text = str_replace('```', '', $text);   
    return trim($text);
}
?>