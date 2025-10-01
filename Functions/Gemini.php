<?php
function Gemini(array $messages, string $geminiApiKey, string $model = "gemini-2.5-flash")
{
if (empty($geminiApiKey)) {
throw new Exception("Gemini API key is required");
}

if (empty($messages)) {
throw new Exception("Messages array cannot be empty");
}

// Normalize model name if needed

$url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($geminiApiKey);

// Convert messages
$contents = [];
foreach ($messages as $msg) {
$role = $msg['role'] ?? 'user';
$text = $msg['text'] ?? '';
if (empty($text)) continue;

switch ($role) {
case 'assistant': $role = 'model'; break;
case 'system': $role = 'user'; break;
case 'user':
case 'model': break;
default: $role = 'user';
}

$contents[] = [
"role" => $role,
"parts" => [["text" => $text]]
];
}

if (empty($contents)) {
throw new Exception("No valid messages to send to Gemini API");
}

$data = [
"contents" => $contents,
"generationConfig" => [
"temperature" => 0.7,
"topK" => 40,
"topP" => 0.95,
"maxOutputTokens" => 2048
],
"safetySettings" => [
["category" => "HARM_CATEGORY_HARASSMENT", "threshold" => "BLOCK_MEDIUM_AND_ABOVE"],
["category" => "HARM_CATEGORY_HATE_SPEECH", "threshold" => "BLOCK_MEDIUM_AND_ABOVE"],
["category" => "HARM_CATEGORY_SEXUALLY_EXPLICIT", "threshold" => "BLOCK_MEDIUM_AND_ABOVE"],
["category" => "HARM_CATEGORY_DANGEROUS_CONTENT", "threshold" => "BLOCK_MEDIUM_AND_ABOVE"]
]
];

// cURL
$ch = curl_init($url);
curl_setopt_array($ch, [
CURLOPT_RETURNTRANSFER => true,
CURLOPT_HTTPHEADER => ["Content-Type: application/json", "User-Agent: ChillaziBot/1.0"],
CURLOPT_POST => true,
CURLOPT_POSTFIELDS => json_encode($data),
CURLOPT_TIMEOUT => 30,
CURLOPT_CONNECTTIMEOUT => 10
]);

$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
$error = curl_error($ch);
curl_close($ch);
throw new Exception("cURL Error: " . $error);
}
curl_close($ch);

if ($httpCode !== 200) {
$errorDetail = json_decode($resp, true);
$errorMsg = $errorDetail['error']['message'] ?? substr($resp, 0, 200);
throw new Exception("HTTP Error {$httpCode}: " . $errorMsg);
}

$json = json_decode($resp, true);
if (json_last_error() !== JSON_ERROR_NONE) {
throw new Exception("Invalid JSON response: " . json_last_error_msg());
}

if (isset($json['error'])) {
$errorMsg = $json['error']['message'] ?? 'Unknown API error';
throw new Exception("Gemini API Error: " . $errorMsg);
}

foreach ($json["candidates"] ?? [] as $candidate) {
$text = $candidate["content"]["parts"][0]["text"] ?? null;
if (!empty($text)) {
return trim($text);
}
}

error_log("Unexpected Gemini response structure: " . json_encode($json));
throw new Exception("No response text received from Gemini API");
}