<?php
// Enhanced Gemini function with better error handling
function Gemini(array $messages, string $geminiApiKey, string $model = "gemini-1.5-flash-latest")
{
if (empty($geminiApiKey)) {
throw new Exception("Gemini API key is required");
}

if (empty($messages)) {
throw new Exception("Messages array cannot be empty");
}

$url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($geminiApiKey);

// Convert messages to Gemini format
$contents = [];
foreach ($messages as $msg) {
$role = $msg['role'] ?? 'user';
$text = $msg['text'] ?? '';

if (empty($text)) {
continue;
}

// Map roles for Gemini (only 'user' and 'model' are supported)
if ($role === 'assistant') {
$role = 'model';
} elseif ($role !== 'user' && $role !== 'model') {
$role = 'user';
}

$contents[] = [
"role" => $role,
"parts" => [["text" => $text]]
];
}

if (empty($contents)) {
throw new Exception("No valid messages to send to Gemini API");
}

// Configure generation parameters
$data = [
"contents" => $contents,
"generationConfig" => [
"temperature" => 0.7,
"topK" => 40,
"topP" => 0.95,
"maxOutputTokens" => 2048,
"stopSequences" => []
],
"safetySettings" => [
[
"category" => "HARM_CATEGORY_HARASSMENT",
"threshold" => "BLOCK_MEDIUM_AND_ABOVE"
],
[
"category" => "HARM_CATEGORY_HATE_SPEECH",
"threshold" => "BLOCK_MEDIUM_AND_ABOVE"
],
[
"category" => "HARM_CATEGORY_SEXUALLY_EXPLICIT",
"threshold" => "BLOCK_MEDIUM_AND_ABOVE"
],
[
"category" => "HARM_CATEGORY_DANGEROUS_CONTENT",
"threshold" => "BLOCK_MEDIUM_AND_ABOVE"
]
]
];

// Make API request
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
"Content-Type: application/json",
"User-Agent: ChillaziBot/1.0"
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

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

// Check for API errors
if (isset($json['error'])) {
$errorMsg = $json['error']['message'] ?? 'Unknown API error';
throw new Exception("Gemini API Error: " . $errorMsg);
}

// Extract response text
$responseText = $json["candidates"][0]["content"]["parts"][0]["text"] ?? null;

if ($responseText === null) {
error_log("Unexpected Gemini response structure: " . json_encode($json));
throw new Exception("No response text received from Gemini API");
}

return trim($responseText);
}