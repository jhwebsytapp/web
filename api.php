<?php
// ============================================================
// api.php — Proxy para Groq API (LLaMA 3)
// Coloca este archivo en la misma carpeta que index.html
// ============================================================

// ⚠️ PON TU API KEY DE GROQ AQUÍ
define('GROQ_API_KEY', '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');

// ⚠️ PON TU SUBDOMINIO AQUÍ
$allowed_origins = [
    'https://ytapp.webdeuna.online',
    'http://localhost',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header("Access-Control-Allow-Origin: " . $allowed_origins[0]);
}

header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Body inválido']);
    exit;
}

// Construir mensajes para Groq (formato OpenAI compatible)
$messages = [];

// System prompt como primer mensaje
if (!empty($input['system'])) {
    $messages[] = [
        'role'    => 'system',
        'content' => $input['system']
    ];
}

// Historial de conversación
foreach ($input['messages'] ?? [] as $msg) {
    $messages[] = [
        'role'    => $msg['role'],
        'content' => $msg['content']
    ];
}

$payload = [
    'model'       => 'llama-3.3-70b-versatile',
    'messages'    => $messages,
    'max_tokens'  => $input['max_tokens'] ?? 1500,
    'temperature' => 0.8,
];

$ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . GROQ_API_KEY,
    ],
    CURLOPT_TIMEOUT        => 60,
]);

$response = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión: ' . $curlError]);
    exit;
}

$data = json_decode($response, true);

// Convertir respuesta de Groq al formato que espera el frontend
if ($httpCode === 200 && isset($data['choices'][0]['message']['content'])) {
    $text = $data['choices'][0]['message']['content'];
    echo json_encode([
        'content' => [['type' => 'text', 'text' => $text]]
    ]);
} else {
    http_response_code($httpCode);
    echo json_encode([
        'error' => $data['error']['message'] ?? 'Error desconocido',
        'raw'   => $data
    ]);
}