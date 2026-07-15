<?php
// includes/clases/Database.php
// Única responsabilidad: entregar la conexión PDO a la base MySQL de Hostinger.
// El esquema se crea una sola vez ejecutando el script SQL de creación en el
// phpMyAdmin de Hostinger (no se genera desde PHP en producción).

class Database
{
    // Credenciales de la base de datos MySQL en Hostinger.
    // El host correcto es 'localhost' cuando la aplicación se ejecuta dentro
    // del mismo hosting; el dominio ...hostingersite.com solo sirve para
    // conexiones remotas (phpMyAdmin externo o MySQL Workbench).
    private const HOST    = 'localhost';
    private const BASE    = 'u817529100_instituto_rtv';
    private const USUARIO = 'u817529100_admin_rtv';
    private const CLAVE   = 'Adminrtv2026*';

    private static ?PDO $pdo = null;

    public static function conexion(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = self::abrir();
        }
        return self::$pdo;
    }

    private static function abrir(): PDO
    {
        $dsn = 'mysql:host=' . self::HOST . ';dbname=' . self::BASE . ';charset=utf8mb4';

        try {
            $pdo = new PDO($dsn, self::USUARIO, self::CLAVE);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            return $pdo;
        } catch (PDOException $e) {
            die('Error crítico: No se pudo conectar a la base de datos MySQL en Hostinger. Detalles: ' . $e->getMessage());
        }
    }
}
