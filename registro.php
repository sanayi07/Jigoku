<?php
// ----------------------------------------------------------------------------
// registro.php — Procesa el formulario y devuelve JSON sin errores
// ----------------------------------------------------------------------------

// 1) Buffer de salida para capturar warnings/espacios
ob_start();

// 2) Cabecera para JSON
header('Content-Type: application/json; charset=utf-8');

// 3) Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode([
        'status'  => 'error',
        'message' => 'Método inválido. Usa POST.'
    ]);
    exit;
}

// 4) Leer el raw input y popular $_POST según Content-Type
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$rawInput    = file_get_contents('php://input');

// Si viene JSON, decodifícalo
if (strpos($contentType, 'application/json') !== false) {
    $json = json_decode($rawInput, true);
    if (is_array($json)) {
        $_POST = $json;
    }
}
// Si está vacío y es urlencoded
elseif (empty($_POST) && strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
    parse_str($rawInput, $_POST);
}
// Si viene multipart/form-data con FormData, PHP ya rellenará $_POST

// 5) Conexión a la base de datos
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

// 6) Campos requeridos
$required = ['nickname','password','email','nacionalidad'];
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

// 7) Validar email
if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    ob_end_clean();
    echo json_encode([
        'status'  => 'error',
        'message' => 'Email inválido.'
    ]);
    exit;
}

// 8) Sanitizar y preparar datos
$nickname     = $conn->real_escape_string(trim($_POST['nickname']));
$passwordRaw  = $_POST['password'];
$email        = $conn->real_escape_string(trim($_POST['email']));
$nacionalidad = $conn->real_escape_string(trim($_POST['nacionalidad']));

// 9) Encriptar contraseña
$hashedPassword = password_hash($passwordRaw, PASSWORD_DEFAULT);

// 10) Insertar usando prepared statement
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

// 11) Éxito
ob_end_clean();
echo json_encode([
    'status'  => 'success',
    'message' => '¡Registro exitoso!'
]);

$stmt->close();
$conn->close();
exit;
