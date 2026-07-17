<?php
// database/init_db.php
// Crea el esquema de la base de datos SQLite del Sistema NEO y siembra
// los 4 usuarios administrativos. Se ejecuta automáticamente desde
// la clase Database la primera vez (cuando no existe database/neo.db).
//
// También puede ejecutarse manualmente desde la terminal para regenerar
// una base vacía (borrando antes database/neo.db):
//   C:\php\php.exe database\init_db.php

function inicializar_base_datos(PDO $pdo) {

    // ==========================================================
    // 1. TABLA ESTUDIANTES
    //    (misma estructura funcional que en producción, sin
    //    documentos_json y con credenciales Moodle visibles)
    // ==========================================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS estudiantes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        carrera TEXT,
        tipo_programa TEXT,
        modulo TEXT,
        fecha_inscripcion TEXT,
        tipo_convenio TEXT,
        apellidos TEXT NOT NULL,
        nombres TEXT NOT NULL,
        cedula TEXT NOT NULL UNIQUE,
        fecha_nacimiento TEXT,
        telefono TEXT,
        correo TEXT,
        provincia TEXT,
        direccion TEXT,
        tipo_colegio TEXT,
        observaciones TEXT,
        carpeta_fisica TEXT,
        costo_inscripcion REAL NOT NULL DEFAULT 0,
        costo_matricula REAL NOT NULL DEFAULT 0,
        costo_colegiatura REAL NOT NULL DEFAULT 0,
        costo_ceremonia REAL NOT NULL DEFAULT 0,
        costo_ingles REAL NOT NULL DEFAULT 0,
        modalidad_ingles TEXT,
        moodle_user TEXT,
        moodle_pass TEXT,
        numero_acta TEXT,
        numero_registro TEXT,
        fecha_registro_titulo TEXT,
        fecha_registro TEXT NOT NULL DEFAULT (datetime('now','localtime'))
    )");

    // ==========================================================
    // 2. TABLA USUARIOS (login del sistema, contraseñas hasheadas)
    // ==========================================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        rol TEXT NOT NULL CHECK (rol IN ('admisiones','contabilidad','delegado','admin','estudiante')),
        nombre_visible TEXT NOT NULL,
        estudiante_id INTEGER REFERENCES estudiantes(id) ON DELETE CASCADE,
        activo INTEGER NOT NULL DEFAULT 1,
        fecha_creacion TEXT NOT NULL DEFAULT (datetime('now','localtime'))
    )");

    // ==========================================================
    // 3. TABLA DOCUMENTOS
    //    Un registro por documento subido por el estudiante.
    //    UNIQUE(estudiante_id, tipo_documento): al volver a subir un
    //    documento negado se reemplaza el registro existente.
    // ==========================================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS documentos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        estudiante_id INTEGER NOT NULL REFERENCES estudiantes(id) ON DELETE CASCADE,
        tipo_documento TEXT NOT NULL,
        archivo TEXT NOT NULL,
        estado TEXT NOT NULL DEFAULT 'Pendiente' CHECK (estado IN ('Pendiente','Aprobado','Negado')),
        motivo_rechazo TEXT,
        fecha_subida TEXT NOT NULL DEFAULT (datetime('now','localtime')),
        fecha_revision TEXT,
        UNIQUE (estudiante_id, tipo_documento)
    )");

    // ==========================================================
    // 4. TABLA PAGOS (misma estructura que en producción)
    // ==========================================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS pagos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        estudiante_id INTEGER NOT NULL REFERENCES estudiantes(id) ON DELETE CASCADE,
        fecha_pago TEXT NOT NULL,
        valor REAL NOT NULL,
        metodo_pago TEXT NOT NULL,
        num_transaccion TEXT NOT NULL,
        archivo_comprobante TEXT,
        estado TEXT NOT NULL DEFAULT 'En Revisión' CHECK (estado IN ('En Revisión','Consolidado','Rechazado')),
        motivo_rechazo TEXT,
        fecha_registro TEXT NOT NULL DEFAULT (datetime('now','localtime'))
    )");

    // Migración para bases creadas antes de existir pagos.motivo_rechazo
    $columnas_pagos = $pdo->query("PRAGMA table_info(pagos)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('motivo_rechazo', $columnas_pagos)) {
        $pdo->exec("ALTER TABLE pagos ADD COLUMN motivo_rechazo TEXT");
    }

    // Índices para las consultas más frecuentes
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_documentos_estudiante ON documentos(estudiante_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_documentos_estado ON documentos(estado)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_pagos_estudiante ON pagos(estudiante_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_pagos_estado ON pagos(estado)");

    // ==========================================================
    // 5. SEMILLA: usuarios administrativos (mismas credenciales
    //    que el sistema anterior, ahora hasheadas)
    // ==========================================================
    $usuarios_semilla = [
        ['admisiones@rtv.edu.ec',   'ARTVV2026', 'admisiones',   'Equipo de Admisiones'],
        ['contabilidad@rtv.edu.ec', '123654',    'contabilidad', 'Dpto. de Contabilidad'],
        ['delegado@rtv.edu.ec',     '123456',    'delegado',     'Gestión Académica'],
        ['admin@rtv.edu.ec',        '123456',    'admin',        'Administrador'],
    ];

    $stmt = $pdo->prepare("INSERT OR IGNORE INTO usuarios (username, password_hash, rol, nombre_visible)
                           VALUES (:username, :hash, :rol, :nombre)");

    foreach ($usuarios_semilla as [$username, $password, $rol, $nombre]) {
        $stmt->execute([
            ':username' => $username,
            ':hash'     => password_hash($password, PASSWORD_DEFAULT),
            ':rol'      => $rol,
            ':nombre'   => $nombre
        ]);
    }
}

// Permite ejecutar este archivo directamente desde la terminal
if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $db_file = __DIR__ . '/neo.db';
    $pdo = new PDO('sqlite:' . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');
    inicializar_base_datos($pdo);
    echo "Base de datos inicializada correctamente en: $db_file" . PHP_EOL;
}
