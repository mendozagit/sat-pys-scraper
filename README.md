# phpcfdi/sat-pys-scraper

[![Source Code][badge-source]][source]
[![PHP Version][badge-php-version]][php-version]
[![Latest Version][badge-release]][release]
[![Software License][badge-license]][license]
[![Build Status][badge-build]][build]
[![Reliability][badge-reliability]][reliability]
[![Maintainability][badge-maintainability]][maintainability]
[![Code Coverage][badge-coverage]][coverage]
[![Violations][badge-violations]][violations]
[![Total Downloads][badge-downloads]][downloads]
[![Docker Downloads][badge-docker]][docker]

> Herramienta para obtener y generar un listado de las clasificaciones del catálogo de productos y servicios del SAT

:us: The documentation of this project is in Spanish, as this is the natural language for the intended audience.

Es posible que, lo único que buscas es el **Listado de clasificaciones de productos y servicios del SAT**, 
si es ese el caso, es mejor consumir el recurso [phpcfdi/resources-pys](https://github.com/phpcfdi/resources-pys), 
en donde el listado es actualizado automáticamente.

## Acerca de phpcfdi/sat-pys-scraper

El SAT en el sitio de internet <http://pys.sat.gob.mx/PyS/catPyS.aspx> tiene publicada una clasificación de productos y servicios. 
Esta clasificación no pertenece oficialmente a los catálogos y no se encuentra publicada en ningún lugar.

Esta herramienta hace el *scrap* del sitio mencionado para obtener los 4 niveles de clasificación: Tipo, Segmento, Familia y Clase. 
Igualmente, la estructura se puede exportar como XML, como JSON, o como tablas referenciales normalizadas en JSON.

## Instalación usando composer

A diferencia de otras librerías o componentes, este proyecto es una herramienta, por lo que probablemente nunca tengas que 
instalar el proyecto como una dependencia. Sin embargo, se puede hacer para que realices la parte de obtener las 
clasificaciones del sitio del SAT, pero tú mismo te encargues de procesar la estructura y usarla para tus propios 
propósitos, como por ejemplo, almacenar en una base de datos.

```shell
composer require phpcfdi/sat-pys-scraper
```

## Ejecución usando Docker

Este proyecto está integrado a Docker Hub [`phpcfdi/sat-pys-scraper`][docker] por lo que puedes ejecutarlo
desde la línea de comandos:

```shell
# obtener la imagen de docker
docker pull phpcfdi/sat-pys-scraper

# ejecución de la herramienta
docker docker run -it --rm --user="$(id -u):$(id -g)" sat-pys-scraper --help
```

Este proyecto provee un archivo `Dockerfile` para construir una imagen con todas sus dependencias.

Para más información y ejemplos consulte el archivo [`README.Docker.md`](Docker.README.md).

### Ayuda de `sat-pys-scraper` (script)

```text
sat-pys-scraper - Crea un archivo con la clasificación de productos y servicios del SAT.

Sintaxis:
    sat-pys-scraper help|-h|--help
    sat-pys-scraper [--quiet|-q] [--json|-j JSON_FILE] [--xml|-x XML_FILE] [--normalized|-n DIRECTORY]

Argumentos:
    --xml|-x XML_FILE
        Establece el nombre de archivo, o "-" para la salida estándar, donde se envían
        los datos generados en formato XML.
    --json|-j JSON_FILE
        Establece el nombre de archivo, o "-" para la salida estándar, donde se envían
        los datos generados en formato JSON.
    --normalized|-n DIRECTORY
        Establece el directorio donde se escriben los archivos normalizados (tablas referenciales)
        SatType.json, SatSegment.json, SatFamily.json y SatClass.json.
    --sort|-s SORT
        Establece el orden de elementos, default: key, se puede usar "key" o "name".
    --quiet|-q
        Modo de operación silencioso.

Notas:
    Debe especificar al menos un argumento "--xml", "--json" o "--normalized".
    No se puede especificar "-" como salida de "--xml" y "--json" al mismo tiempo.

Acerca de:
    Este script pertenece al proyecto https://github.com/phpcfdi/sat-pys-scraper
    y mantiene la autoría y licencia de todo el proyecto.
```

## Uso de la herramienta

Si usar el código de la herramienta, entonces es importante entender que la tarea trata de dos pasos:

1. Obtener del sitio del SAT el listado de tipos, segmentos, familias y clases.
2. Exportar el listado a un formato específico.

Para generar el listado de tipos, segmentos, familias y clases se usa el objeto `Generator`, que a su vez usa un 
objeto `Scraper` para realizar la descarga de información, que a su vez utiliza un objeto `Client` de `GuzzleHttp`.

En el siguiente ejemplo se muestra cómo generar la estructura e iterar sobre sus elementos.

- Al ejecutar `Generator::generate()` se devuelve un objeto de tipo `Types`.
- Se recorre la estructura con `foreach`.
- Se puede exportar usando `XmlExporter::export()`.

```php
<?php
use GuzzleHttp\Client;
use PhpCfdi\SatPysScraper\Generator;
use PhpCfdi\SatPysScraper\Scraper;
use PhpCfdi\SatPysScraper\XmlExporter;

$scraper = new Scraper(new Client());
$generator = new Generator($scraper);
$types = $generator->generate();
$types->sortByKey();

foreach ($types as $type) {
    printf("Tipo: %s - %s\n", $type->key, $type->name);
    foreach ($type as $segment) {
        printf("  Segmento: %s - %s\n", $segment->key, $segment->name);
        foreach ($segment as $family) {
            printf("    Familia: %s - %s\n", $family->key, $family->name);
            foreach ($family as $class) {
                printf("      Clase: %s - %s\n", $class->key, $class->name);
            }
        }
    }
}

$exporter = new XmlExporter();
$exporter->export('output.xml', $types);
```

### Exportación normalizada (tablas referenciales)

Con el argumento `--normalized DIRECTORY` (o usando la clase `NormalizedExporter`) la jerarquía se aplana
en 4 tablas relacionales en formato JSON, donde cada elemento contiene el identificador de su padre:

- `SatType.json`: `{ "Id", "Description" }`
- `SatSegment.json`: `{ "Id", "Description", "TypeId" }`
- `SatFamily.json`: `{ "Id", "Description", "SegmentId" }`
- `SatClass.json`: `{ "Id", "Description", "FamilyId" }`

En `SatClass.json` el identificador es la clave de 8 dígitos registrable como `c_ClaveProdServ`:
se forma con el código de clase de 6 dígitos rellenando el par de producto con `00`
(por ejemplo, la clase `501115` se exporta con `Id` igual a `50111500`), indicando que la clave
apunta a la clase completa y no a un artículo concreto.

Cada archivo contiene un envoltorio con la descripción del catálogo y la estrategia de búsqueda sugerida
según la cantidad de elementos (`InMemory` hasta 5,000, `Indexed` hasta 30,000 y `DatabaseBacked` en adelante):

```json
{
    "Description": "Catálogo de segmentos de productos y servicios (c_ClaveProdServ)",
    "SearchStrategy": "InMemory",
    "Preload": true,
    "Items": [
        { "Id": "10", "Description": "Material Vivo Vegetal y Animal, Accesorios y Suministros", "TypeId": "1" }
    ]
}
```

Ejemplo de ejecución:

```shell
sat-pys-scraper --normalized build/normalized
```

### Tipos de datos

Un objeto `Types` es una colección iterable de objetos de tipo `Type`.
Un objeto `Type` contiene las propiedades `key` y `name`, y además es una colección iterable de objetos de tipo `Segment`.
Un objeto `Segment` contiene las propiedades `key` y `name`, y además es una colección iterable de objetos de tipo `Family`.
Un objeto `Family` contiene las propiedades `key` y `name`, y además es una colección iterable de objetos de tipo `Classification`.
Un objeto `Classification` solamente contiene las propiedades `key` y `name`.

Todos los objetos de datos implementan `JsonSerializable`, por lo que puedes usar esta característica para exportar a formato JSON.

### Excepciones

La clase `Scraper` y -por consecuencia- también la clase `Generator` generan excepciones.
En el caso de una excepción de tipo HTTP se tira una excepción `HttpException`.
En el caso de una excepción HTTP y tenga un código de error del servicio remoto se tira una excepción `HttpServerException`.

La jerarquía de excepciones es:

```text
- PysException (interface)
    - HttpException (class)
        - HttpServerException (class)
```

## Soporte

Puedes obtener soporte abriendo un ticket en Github.

Adicionalmente, esta librería pertenece a la comunidad [PhpCfdi](https://www.phpcfdi.com),
así que puedes usar los canales oficiales de comunicación para obtener ayuda de la comunidad.

## Compatibilidad

Esta librería se mantendrá compatible con al menos la versión con
[soporte activo de PHP](https://www.php.net/supported-versions.php) más reciente.

También utilizamos [Versionado Semántico 2.0.0](docs/SEMVER.md) por lo que puedes usar esta librería
sin temor a romper tu aplicación.

| Versión | PHP           | Nota                              |
|---------|---------------|-----------------------------------|
| 1.0.0   | 8.2, 8.3      | 2023-12-13 Fuera de mantenimiento |
| 2.0.0   | 8.2, 8.3      | 2024-03-07 Fuera de mantenimiento |
| 3.0.0   | 8.2, 8.3      | 2024-03-07 Fuera de mantenimiento |
| 4.0.0   | 8.2, 8.3, 8.4 | 2024-10-17 Fuera de mantenimiento |
| 5.0.0   | 8.3, 8.4      | 2025-11-13                        |

## Contribuciones

Las contribuciones con bienvenidas. Por favor lee [CONTRIBUTING][] para más detalles
y recuerda revisar el archivo de tareas pendientes [TODO][] y el archivo [CHANGELOG][].

## Copyright and License

The `phpcfdi/sat-pys-scraper` tool is copyright © [PhpCfdi](https://www.phpcfdi.com/)
and licensed for use under the MIT License (MIT). Please see [LICENSE][] for more information.

[contributing]: https://github.com/phpcfdi/sat-pys-scraper/blob/main/CONTRIBUTING.md
[changelog]: https://github.com/phpcfdi/sat-pys-scraper/blob/main/docs/CHANGELOG.md
[todo]: https://github.com/phpcfdi/sat-pys-scraper/blob/main/docs/TODO.md

[source]: https://github.com/phpcfdi/sat-pys-scraper
[php-version]: https://packagist.org/packages/phpcfdi/sat-pys-scraper
[release]: https://github.com/phpcfdi/sat-pys-scraper/releases
[license]: https://github.com/phpcfdi/sat-pys-scraper/blob/main/LICENSE
[build]: https://github.com/phpcfdi/sat-pys-scraper/actions/workflows/build.yml?query=branch:main
[reliability]:https://sonarcloud.io/component_measures?id=phpcfdi_sat-pys-scraper&metric=Reliability
[maintainability]: https://sonarcloud.io/component_measures?id=phpcfdi_sat-pys-scraper&metric=Maintainability
[coverage]: https://sonarcloud.io/component_measures?id=phpcfdi_sat-pys-scraper&metric=Coverage
[violations]: https://sonarcloud.io/project/issues?id=phpcfdi_sat-pys-scraper&resolved=false
[downloads]: https://packagist.org/packages/phpcfdi/sat-pys-scraper
[docker]: https://hub.docker.com/r/phpcfdi/sat-pys-scraper

[badge-source]: https://img.shields.io/badge/source-phpcfdi/sat--pys--scraper-blue?logo=github
[badge-php-version]: https://img.shields.io/packagist/dependency-v/phpcfdi/sat-pys-scraper/php?logo=php
[badge-release]: https://img.shields.io/github/release/phpcfdi/sat-pys-scraper?logo=git
[badge-license]: https://img.shields.io/github/license/phpcfdi/sat-pys-scraper?logo=open-source-initiative
[badge-build]: https://img.shields.io/github/actions/workflow/status/phpcfdi/sat-pys-scraper/build.yml?branch=main&logo=github-actions
[badge-reliability]: https://sonarcloud.io/api/project_badges/measure?project=phpcfdi_sat-pys-scraper&metric=reliability_rating
[badge-maintainability]: https://sonarcloud.io/api/project_badges/measure?project=phpcfdi_sat-pys-scraper&metric=sqale_rating
[badge-coverage]: https://img.shields.io/sonar/coverage/phpcfdi_sat-pys-scraper/main?logo=sonarqubecloud&server=https%3A%2F%2Fsonarcloud.io
[badge-violations]: https://img.shields.io/sonar/violations/phpcfdi_sat-pys-scraper/main?format=long&logo=sonarqubecloud&server=https%3A%2F%2Fsonarcloud.io
[badge-downloads]: https://img.shields.io/packagist/dt/phpcfdi/sat-pys-scraper?logo=packagist
[badge-docker]: https://img.shields.io/docker/pulls/phpcfdi/sat-pys-scraper?logo=docker
