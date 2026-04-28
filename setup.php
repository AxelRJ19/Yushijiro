<?php
// Ejecutar solo desde la linea de comandos: php setup.php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acceso denegado. Ejecuta desde CLI: php setup.php' . PHP_EOL);
}

echo "\n=== Yushijiro San — Configuracion de contrasenas ===\n\n";

// Leer VPS_URL y ALLOWED_ORIGINS existentes si config.php ya existe
$vpsUrl         = 'http://187.77.27.240:3001/api/webchat';
$allowedOrigins = ['http://localhost', 'http://localhost/yushijiro'];

if (file_exists(__DIR__ . '/config.php')) {
    @include __DIR__ . '/config.php';
    if (defined('VPS_URL'))         $vpsUrl         = VPS_URL;
    if (defined('ALLOWED_ORIGINS')) $allowedOrigins  = ALLOWED_ORIGINS;
}

// Contrasena del panel admin
echo "Contrasena para el panel admin (usuario: admin): ";
$adminPass = trim(fgets(STDIN));
if (!$adminPass) { die("Error: contrasena vacia\n"); }
$panelHash = password_hash($adminPass, PASSWORD_BCRYPT);

// Contrasenas de repartidores
$accounts = [
    'carlos.m' => ['nombre' => 'Carlos Martinez', 'inicial' => 'CM', 'branch' => 'Hacienda'],
    'hector.r' => ['nombre' => 'Hector Ramos',    'inicial' => 'HR', 'branch' => 'Tonanitla'],
    'paola.s'  => ['nombre' => 'Paola Sanchez',   'inicial' => 'PS', 'branch' => 'Acueducto'],
];

echo "\n";
$usersEntries = [];
foreach ($accounts as $user => $info) {
    echo "Contrasena para {$info['nombre']} ({$user}): ";
    $pass = trim(fgets(STDIN));
    if (!$pass) { die("Error: contrasena vacia\n"); }
    $usersEntries[$user] = array_merge($info, ['pass' => password_hash($pass, PASSWORD_BCRYPT)]);
}

// Escribir users.php
$usersCode = "<?php\n// NO SUBIR A GIT\nreturn [\n";
foreach ($usersEntries as $user => $info) {
    $usersCode .= '    ' . var_export($user, true) . ' => ' . var_export([
        'pass'    => $info['pass'],
        'nombre'  => $info['nombre'],
        'inicial' => $info['inicial'],
        'branch'  => $info['branch'],
    ], true) . ",\n";
}
$usersCode .= "];\n";
file_put_contents(__DIR__ . '/users.php', $usersCode);

// Escribir config.php con el hash del panel
$configLines = [
    "<?php\n",
    "// NO SUBIR A GIT\n",
    "define('VPS_URL', " . var_export($vpsUrl, true) . ");\n",
    "define('ALLOWED_ORIGINS', " . var_export($allowedOrigins, true) . ");\n",
    "define('PANEL_USER', 'admin');\n",
    "define('PANEL_PASS_HASH', " . var_export($panelHash, true) . ");\n",
];
file_put_contents(__DIR__ . '/config.php', implode('', $configLines));

echo "\n";
echo "config.php actualizado\n";
echo "users.php generado\n";
echo "\nIMPORTANTE: Verifica que VPS_URL en config.php sea correcto.\n\n";
