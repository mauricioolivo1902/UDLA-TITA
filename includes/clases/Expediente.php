<?php
// includes/clases/Expediente.php
// Única responsabilidad: la carpeta física del estudiante y sus archivos.
// Estructura en disco:
//   expedientes/<APELLIDOS_NOMBRES>/DOCUMENTOS/  (requisitos en PDF)
//   expedientes/<APELLIDOS_NOMBRES>/PAGOS/       (comprobantes de pago)

class Expediente
{
    public const DOCUMENTOS = 'DOCUMENTOS';
    public const PAGOS      = 'PAGOS';

    private string $baseDir;

    public function __construct(?string $baseDir = null)
    {
        $this->baseDir = $baseDir ?? dirname(__DIR__, 2) . '/expedientes';
    }

    // Nombre de carpeta seguro a partir de apellidos y nombres
    public static function nombreCarpeta(string $apellidos, string $nombres): string
    {
        $texto = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ'],
            ['a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U', 'n', 'N'],
            $apellidos . '_' . $nombres
        );
        $texto = preg_replace('/[^a-zA-Z0-9_]/', '_', str_replace(' ', '_', $texto));
        return preg_replace('/_+/', '_', $texto);
    }

    // Crea la carpeta del estudiante con sus subcarpetas DOCUMENTOS y PAGOS
    public function crearCarpetas(string $carpeta): void
    {
        foreach ([$this->ruta($carpeta), $this->ruta($carpeta, self::DOCUMENTOS), $this->ruta($carpeta, self::PAGOS)] as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    // Mueve un archivo subido a la subcarpeta indicada.
    // Devuelve el nombre único generado, o null si falla el movimiento.
    public function guardarArchivo(array $archivoSubido, string $carpeta, string $subcarpeta, string $prefijo): ?string
    {
        $extension = strtolower(pathinfo($archivoSubido['name'], PATHINFO_EXTENSION));
        $nombre = $prefijo . '_' . date('Ymd_His') . '_' . uniqid() . '.' . $extension;

        $directorio = $this->ruta($carpeta, $subcarpeta);
        if (!file_exists($directorio)) {
            mkdir($directorio, 0755, true);
        }

        $destino = $directorio . '/' . $nombre;
        return move_uploaded_file($archivoSubido['tmp_name'], $destino) ? $nombre : null;
    }

    public function eliminarArchivo(string $carpeta, string $subcarpeta, string $archivo): void
    {
        if ($archivo === '') {
            return;
        }
        $ruta = $this->ruta($carpeta, $subcarpeta) . '/' . $archivo;
        if (file_exists($ruta)) {
            unlink($ruta);
        }
    }

    public function extension(array $archivoSubido): string
    {
        return strtolower(pathinfo($archivoSubido['name'], PATHINFO_EXTENSION));
    }

    // ¿Llegó un archivo válido en la petición?
    public static function archivoRecibido(?array $archivoSubido): bool
    {
        return $archivoSubido !== null && ($archivoSubido['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;
    }

    private function ruta(string $carpeta, string $subcarpeta = ''): string
    {
        return $this->baseDir . '/' . $carpeta . ($subcarpeta !== '' ? '/' . $subcarpeta : '');
    }
}
