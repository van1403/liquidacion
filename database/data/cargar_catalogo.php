<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$DB = \Illuminate\Support\Facades\DB::class;
$items = json_decode(file_get_contents(__DIR__ . '/catalogo_unidades.json'), true);
$ahora = now();
$fallas = [];

$DB::table('catalogos')->where('tipo', 'codigo_unidad')->delete();
foreach ($items as $i) {
    try {
        $DB::table('catalogos')->insert([
            'tipo'       => 'codigo_unidad',
            'codigo'     => $i['codigo'],
            'nombre'     => $i['nombre'],
            'activo'     => 1,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ]);
    } catch (\Throwable $e) {
        $fallas[] = $i['codigo'] . ': ' . substr($e->getMessage(), 0, 120);
    }
}

echo 'Conexión usada: ' . $DB::getDefaultConnection() . PHP_EOL;
echo 'Acciones en el Excel: ' . count($items) . PHP_EOL;
echo 'Acciones cargadas: ' . $DB::table('catalogos')->where('tipo', 'codigo_unidad')->count() . PHP_EOL;
foreach ($fallas as $f) echo '  No se pudo cargar ' . $f . PHP_EOL;
