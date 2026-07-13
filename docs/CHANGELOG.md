# phpcfdi/sat-pys-scraper CHANGELOG

## Acerca de SemVer

Usamos [Versionado Semántico 2.0.0](SEMVER.md) por lo que puedes usar esta librería sin temor a romper tu aplicación.

## Cambios no liberados en una versión

Pueden aparecer cambios no liberados que se integran a la rama principal, pero no ameritan una nueva liberación de
versión, aunque sí su incorporación en la rama principal de trabajo. Generalmente, se tratan de cambios en el desarrollo.

## Listado de cambios

### Cambios no liberados

- Se agrega la exportación normalizada (tablas referenciales) con el argumento `--normalized|-n DIRECTORY`.
  Genera los archivos `SatType.json`, `SatSegment.json`, `SatFamily.json` y `SatClass.json`, donde cada
  elemento contiene el identificador de su elemento padre (`TypeId`, `SegmentId` y `FamilyId`).
- Se agrega la clase `NormalizedExporter` y el enum `SearchStrategy`.
- El mensaje de error cuando no se especifica una salida cambia a
  `Did not specify --xml, --json or --normalized arguments`.

### Versión 5.0.0 2025-11-13

Se elimina la compatibilidad con PHP 8.2. Se mantiene PHP 8.3 y PHP 8.4.

Si estás usando esta herramienta en una implementación de la librería, 
esta versión no presenta cambios significativos a tu código.

- Se establece el tipo `string` a la constante `Scraper::PYS_URL`.

En el entorno de desarrollo:

- Se actualiza PHPUnit a la versión 12.4.

### Versión 4.0.2 2025-11-13

Se corrige la imagen de Docker incluyendo la dependencia `libzip`.

### Versión 4.0.1 2025-11-13

Esta actualización confirma la compatibilidad (que ya existía) con PHP 8.4.

Estos cambios aplican al proyecto liberado:

- Cambios menores sugeridos por PHPStan (forzar escalares en lugar de usar anotaciones).
- Se agrega la imagen en Docker Hub `phpcfdi/sat-pys-scraper`.
- Se corrigen las insignias de SonarQube Cloud.
- Se actualiza el año de licencia a 2025.

Cambios en el entorno de desarrollo:

- Se corrige la integración con SonarQube Cloud.
- En el archivo de construcción de la imagen de Docker:
  - Se construye usando PHP 8.4.
  - Se eliminan las librerías de compilación.
- En los flujos de trabajo:
  - Se agrega PHP 8.4 a la matriz de pruebas.
  - Se agrega PHP 8.4 a la matriz de pruebas de sistema.
  - Se ejecutan los trabajos en PHP 8.4.
  - Se agrega `composer-normalize` a los trabajos.
- Se mejora la configuración de PHPUnit para mostrar todos los problemas encontrados.
- Se actualiza el estándar de código.
- Se actualizan las herramientas de desarrollo.

### Versión 4.0.0 2024-10-17

Esta es una actualización de refactorización que obliga a crear una versión mayor.
Si no utilizas entidades del espacio de nombres `PhpCfdi\SatPysScraper\App` entonces puedes hacer el cambio 
de la versión `3.x` a la versión `4.x` sin conflictos. En caso contrario debes revisar tu implementación. 

- Se agrega el parámetro `--debug` que, si existe, vuelca los datos del error de ejecución.
- Se agrega el parámetro `--tries` que, si existe, reintenta la descarga de información hasta ese número de veces.
- Se extrae el procesamiento de argumentos a su propia clase.
- Se extrae el almacenamiento de argumentos a su propia clase en lugar de un arreglo.
- Se reorganizan las pruebas de acuerdo a los cambios previos.
- La ejecución del flujo de trabajo `system.yml` en el trabajo `system-tests` se configura con `--tries 5`.
- Se vuelve a simplificar la herramienta `bin/sat-pys-scraper` para que toda su lógica esté probada.
- Ya no se usa la variable de entorno `MAX_TRIES`.

### Versión 3.0.2 2024-10-17

A la herramienta `bin/sat-pys-scraper` se le puede definir un número máximo de ejecuciones en la 
variable de entorno `MAX_TRIES`, de forma predeterminada usa el valor `1`. 
Con este cambio se intenta resolver el problema de error `500 Internal Server Error` de la 
aplicación de Productos y Servicios del SAT.

En el flujo de trabajo `system.yml` en el trabajo `system-tests` se configura `MAX_TRIES` a `5`.

### Versión 3.0.1 2024-10-15

La aplicación del SAT devuelve un error 500 frecuentemente (1 de cada 3 veces) desde 2024-07-15.
Este error parece estar relacionado con la distribución de cargas por parte del SAT, así que
reintentar la llamada HTTP sobre la misma conexión no soluciona el problema y hay que crear
un nuevo cliente HTTP. Para intentar solventarlo, se modifica la librería para tirar
excepciones con errores HTTP e intentar solventar el error.

Se cambia la construcción de imagen de docker, ahora depende de `php:8.3-cli-alpine`.

Se actualiza el archivo de licencia a 2024.

Se hacen otros cambios en el entorno de desarrollo:

- Se modifica la prueba funcional para poder hacer hasta 5 reintentos reconstruyendo el cliente http.
- Se prueba el correcto orden para llamar a los métodos para obtener datos.
- Se utiliza la variable `php-version` en singular para las matrices de pruebas.
- Se actualizan las herramientas de desarrollo.

### Versión 3.0.0 2024-03-07

- Se cambia el método `SatPysScraper::run()` para una mejor inyección de dependencias y capacidad de pruebas.
- Se introduce una excepción dedicada para los errores de procesamiento de argumentos.
- Se cambia la forma de procesar los argumentos para usar `array_shift`.

### Versión 2.0.0 2024-03-07

- Se corrige el nodo principal, el nombre correcto es `<pys>`.
- Se cambia el comando de ejecución `bin/sat-pys-scraper` para exportar a JSON y XML al mismo tiempo.

Otros cambios:

- Se utilizan las acciones de GitHub versión 4. 
- Se actualizan las herramientas de desarrollo.

### Versión 1.0.0 2023-12-13

- Versión inicial.
