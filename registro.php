<?php
// ----------------------------------------------------------------------------
// registro.php — Procesa el formulario y devuelve JSON compatible con frontend responsive
// ----------------------------------------------------------------------------

// 1) Buffer de salida para capturar warnings/espacios
ob_start();

// 2) Encabezados para permitir solicitudes desde apps responsivas
header('Access-Control-Allow-Origin: *'); // Permite llamadas desde cualquier dominio
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// 3) Manejo de solicitud preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// 4) Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode([
        'status'  => 'error',
        'message' => 'Método inválido. Usa POST.'
    ]);
    exit;
}

// 5) Leer el cuerpo crudo y decodificar si es necesario
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$rawInput    = file_get_contents('php://input');

// Si viene JSON (fetch con application/json)
if (strpos($contentType, 'application/json') !== false) {
    $json = json_decode($rawInput, true);
    if (is_array($json)) {
        $_POST = $json;
    }
}
// Si viene como x-www-form-urlencoded y aún está vacío
elseif (empty($_POST) && strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
    parse_str($rawInput, $_POST);
}
// En multipart/form-data PHP rellena $_POST normalmente

// 6) Conexión a la base de datos
$host   = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'jigoku_db';

$conn = new mysqli($host, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    ob_end_clean();
    echo json_encode([
        'status'  => 'error',
        'message' => 'Conexión fallida: ' . $conn->connect_error
    ]);
    exit;
}

// 7) Validar campos requeridos
$required = ['nickname', 'password', 'email', 'nacionalidad'];
$missing  = [];

foreach ($required as $f) {
    if (!isset($_POST[$f]) || trim($_POST[$f]) === '') {
        $missing[] = $f;
    }
}

if ($missing) {
    ob_end_clean();
    echo json_encode([
        'status'   => 'error',
        'message'  => 'Faltan campos: ' . implode(', ', $missing),
        'received' => array_keys($_POST)
    ]);
    exit;
}

// 8) Validar formato de email
if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    ob_end_clean();
    echo json_encode([
        'status'  => 'error',
        'message' => 'Email inválido.'
    ]);
    exit;
}

// 9) Sanitizar datos
$nickname     = $conn->real_escape_string(trim($_POST['nickname']));
$passwordRaw  = $_POST['password'];
$email        = $conn->real_escape_string(trim($_POST['email']));
$nacionalidad = $conn->real_escape_string(trim($_POST['nacionalidad']));

// 10) Encriptar contraseña
$hashedPassword = password_hash($passwordRaw, PASSWORD_DEFAULT);

// 11) Insertar usando prepared statement
$stmt = $conn->prepare("
    INSERT INTO usuarios (nickname, password, email, nacionalidad)
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param('ssss', $nickname, $hashedPassword, $email, $nacionalidad);

if (!$stmt->execute()) {
    ob_end_clean();
    echo json_encode([
        'status'  => 'error',
        'message' => 'Error al registrar: ' . $stmt->error
    ]);
    $stmt->close();
    $conn->close();
    exit;
}

// 12) Éxito
ob_end_clean();
echo json_encode([
    'status'  => 'success',
    'message' => '¡Registro exitoso!'
]);

$stmt->close();
$conn->close();
exit;
?>
