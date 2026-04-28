<?php
// Copia este archivo a users.php y ejecuta: php setup.php para generar los hashes
// NO SUBIR A GIT — users.php esta en .gitignore
return [
    'usuario.ejemplo' => [
        'pass'    => '',  // hash bcrypt generado por setup.php
        'nombre'  => 'Nombre Completo',
        'inicial' => 'NC',
        'branch'  => 'Sucursal',
    ],
];
