[repo]: https://github.com/yordanny90/BigXLSX
[iconGit]: http://www.google.com/s2/favicons?domain=www.github.com

# Documentación BigXLSX

Esta librería permite leer archivos XLSX demasiado grandes para cargar todos los datos en memoria.

[Ir a ![GitHub CI][iconGit]][repo]

## Requisitos

- PHP 7.1 o superior
- Extensión `zip`
- Extensión `xmlreader`
- Librería `yordanny90/bigxml`
- Extensión `sqlite3` opcional para cache SQLite

## Ejemplo básico

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

Cada `$row` es un array asociativo indexado por número de columna (0-based), con el valor de cada celda.

## Localizar hojas y tablas

```php
// Hojas visibles: [rId => nombre]
$xlsx->getSheetNames();

// Hojas, incluyendo ocultas
$xlsx->getSheetNames(true);

// Cantidad de hojas visibles / con ocultas
$xlsx->getSheetCount();
$xlsx->getSheetCount(true);

// Detalle de hojas: type, id (rId), name, hidden
$xlsx->getSheetrIdNames();

// Detalle de tablas (Excel Tables) dentro de las hojas
$xlsx->getTableNames();
$xlsx->getTablerIdNames();

// Obtener una hoja o tabla por su identificador
$sheet = $xlsx->getSheetByrId($rId);
$sheet = $xlsx->getSheetByName($nombre);
$table = $xlsx->getTableByrId($tablerId); // formato "sheetRId:tableRId"
```

## Tablas de Excel (`Table`)

Si la hoja contiene tablas definidas en Excel, se pueden recorrer igual que una hoja, pero limitadas al rango y con los nombres de columna definidos en la tabla:

```php
$table = $xlsx->getTableByrId('rId2:1');

foreach ($table as $row) {
    // $row usa como llave el nombre de columna de la tabla
}

// Encabezado de la tabla
$header = $table->getHeaderRow();
```

## Alias de columnas

Permite renombrar las columnas (por índice numérico) a un nombre propio, tanto en hojas como en tablas:

```php
$sheet->alias([
    0 => 'id',
    1 => 'nombre',
    2 => null, // conserva el índice original (2) como llave
]);
```

## Filas y columnas ocultas

Por defecto se excluyen filas y columnas ocultas. Se puede desactivar:

```php
$sheet->setExcludeHidden(false);
```

## Valores como objeto `CellValue`

Por defecto cada celda retorna su valor simple (string, número, etc). Activando `setCellObject`, cada celda se retorna como un objeto `\BigXLSX\CellValue`, que permite distinguir errores de fórmulas:

```php
$sheet->setCellObject(true);

foreach ($sheet as $row) {
    foreach ($row as $cell) {
        /** @var \BigXLSX\CellValue $cell */
        if ($cell->isError()) {
            echo $cell->errorMessage();
        } else {
            echo $cell->value();
        }
    }
}

// Extrae solo las celdas con error de una fila
$errores = \BigXLSX\CellValue::extractErrors($row);
```

## Cache de shared strings y estilos numéricos

Los textos compartidos (*shared strings*) y los estilos numéricos (fechas) se cargan en memoria la primera vez que se necesitan. Para archivos muy grandes, se puede usar SQLite como cache en disco en vez de memoria:

```php
\BigXLSX\Reader::useSQLite(true); // requiere ext-sqlite3
```

También se puede conservar el cache entre instancias de `Reader` dentro del mismo proceso (por ejemplo, al procesar varios archivos con la misma estructura):

```php
\BigXLSX\Reader::saveCacheSharedStrings(true);
\BigXLSX\Reader::saveCacheStylesNumeric(true);
```

Estas son opciones estáticas, aplican a todas las instancias de `Reader` creadas después de activarlas.
