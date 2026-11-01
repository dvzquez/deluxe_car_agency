<?php
/**
 * get_autos.php — Endpoint JSON para el configurador
 * Devuelve autos filtrados con sus 4 imágenes de color.
 */
header('Content-Type: application/json; charset=utf-8');

include 'config/db.php';

$categoria = trim($_GET['categoria'] ?? '');
$busqueda = trim($_GET['q'] ?? '');
$limite = min(100, max(1, (int) ($_GET['limite'] ?? 50)));
$orden = $_GET['orden'] ?? 'precio_asc';

$ordenMap = [
    'precio_asc' => 'precio_base ASC',
    'precio_desc' => 'precio_base DESC',
    'nombre' => 'modelo ASC',
];
$orderSQL = $ordenMap[$orden] ?? 'precio_base ASC';

$where = [];
$params = [];

if ($categoria !== '') {
    $where[] = 'categoria = ?';
    $params[] = $categoria;
}
if ($busqueda !== '') {
    $where[] = 'modelo LIKE ?';
    $params[] = '%' . $busqueda . '%';
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT id, modelo, categoria, precio_base, ficha_tecnica,
               imagen, imagen_negro, imagen_rojo, imagen_gris, imagen_azul
        FROM autos $whereSQL ORDER BY $orderSQL LIMIT $limite";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$autos = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($autos as &$a) {
    $a['precio_base'] = (float) $a['precio_base'];
    // Fallback: si una imagen de color es NULL, usa la imagen base
    foreach (['imagen_negro', 'imagen_rojo', 'imagen_gris', 'imagen_azul'] as $col) {
        if (empty($a[$col]))
            $a[$col] = $a['imagen'];
    }
}

echo json_encode($autos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);