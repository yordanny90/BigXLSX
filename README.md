[repo]: https://github.com/yordanny90/BigXLSX
[iconGit]: http://www.google.com/s2/favicons?domain=www.github.com

# Librería BigXLSX

Esta librería permite leer archivos XLSX demasiado grandes para cargar todos los datos en memoria.

[Ir a ![GitHub CI][iconGit]][repo]

## Instalación

```bash
composer require yordanny90/bigxlsx
```

> BigXLSX depende de `yordanny90/bigxml`. Publica BigXML en Packagist con ese nombre o ajusta el nombre de la dependencia en `composer.json` antes de crear el tag de release.

## Requisitos

- PHP 7.1 o superior
- Extensión `zip`
- Extensión `xmlreader`
- Librería `yordanny90/bigxml`
- Extensión `sqlite3` opcional para cache SQLite

## Ejemplo

La clase principal es `\BigXLSX\Reader`:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

$file = 'ruta-del-archivo.xlsx';
$xlsx = new \BigXLSX\Reader($file);

// Lista de hojas con su rId y nombre.
$sheets = $xlsx->getSheetrIdNames();

// Obtiene la primera hoja visible del archivo Excel.
// El rId de la primera hoja no siempre es el mismo.
$firstSheet = reset($sheets);
$sheet = $xlsx->getSheetByrId($firstSheet['id']);

foreach ($sheet as $row) {
    // Usar cada fila de la hoja.
}
```

## Publicación en Packagist

1. Verifica que `composer validate --strict` no reporte errores.
2. Sube el repositorio a GitHub.
3. Crea un release/tag semántico, por ejemplo `v1.0.0`.
4. Registra `https://github.com/yordanny90/BigXLSX` en Packagist.
