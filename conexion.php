<?php
// Conexión a la base de datos PostgreSQL de Render
$conn = pg_connect("host=dpg-d19jc23ipnbc73en8q90-a dbname=web_jigoku user=web_jigoku_user password=IYV4xNbNp2ieg4lrrx7kb2QpxgW6A5VO port=5432");

if (!$conn) {
    echo "❌ No me pude conectar 😢";
} else {
    echo "✅ ¡Me conecté a la base de datos, qué feliz estoy! 😄";
}
?>

