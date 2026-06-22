<?php
// ============================================================
// save.php — Guarda y carga datos en el servidor
// Coloca este archivo en la misma carpeta que index.html
// ============================================================

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

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200); exit;
}

// Archivo donde se guardan los datos
$dataFile = __DIR__ . '/data.json';

// ── CARGAR datos (GET) ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (file_exists($dataFile)) {
        echo file_get_contents($dataFile);
    } else {
        echo json_encode(['projects' => [], 'metas' => []]);
    }
    exit;
}

// ── GUARDAR datos (POST) ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data  = json_decode($input, true);

    if (!$data || !isset($data['projects'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos inválidos']);
        exit;
    }

    // Guardar con backup automático
    if (file_exists($dataFile)) {
        copy($dataFile, $dataFile . '.bak');
    }

    if (file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false) {
        echo json_encode(['ok' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'No se pudo guardar. Verifica permisos de la carpeta.']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
