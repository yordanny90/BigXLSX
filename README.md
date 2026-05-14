[repo]: https://github.com/yordanny90/BigXLSX
[iconGit]: http://www.google.com/s2/favicons?domain=www.github.com

# Librería BigXLSX

Esta librería permite leer archivos XLSX demasiado grandes para cargar todos los datos en memoria.

[Ir a ![GitHub CI][iconGit]][repo]

## Instalación

```bash
composer require yordanny90/bigxlsx
```

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
