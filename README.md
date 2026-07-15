# Sistema NEO — Gestión de Admisiones, Pagos y Titulación

**Instituto Superior Tecnológico RTV** · Aplicación web en PHP + MySQL desplegada en Hostinger.

NEO administra el ciclo completo del estudiante: desde su registro por Admisiones, la carga y revisión de su documentación y pagos, hasta su titulación oficial con registro Senescyt.

---

## 1. Roles y funcionalidades

El sistema tiene **5 roles**, cada uno con su propio panel y permisos estrictos:

### 👩‍💼 Secretaría de Admisiones
- **Crear estudiantes**: al registrar un estudiante se genera automáticamente su carpeta de expediente (con subcarpetas `DOCUMENTOS/` y `PAGOS/`) y sus **credenciales institucionales** (las mismas para el sistema NEO y Moodle):
  - Usuario: `primerapellido + primernombre` (minúsculas, sin tildes) → `alvaradokarla`
  - Contraseña: `InicialApellido + inicialnombre + cédula + *` → `Ak2350159147*`
  - Si el usuario ya existe se añade un número: `alvaradokarla2`, `alvaradokarla3`…
- **Revisar documentos**: bandeja con los PDF subidos por los estudiantes; puede **aprobarlos o negarlos** (negar exige un motivo que el estudiante verá en su panel).
- **Visualizar listas**: directorio con filtros combinables (nombre, programa, carrera, módulo, fechas) y ficha integral de cada estudiante.
- **Exportar listas**: reportes Excel a medida.

### 🎓 Estudiante
- **Dashboard "Mi Progreso"**: porcentaje de documentación aprobada, porcentaje de pagos consolidados, y sus datos personales en solo lectura.
- **Subir documentos**: carga en PDF los requisitos de su programa (9 para Ordinario, 12 para Validación). Si un documento fue negado, ve el **motivo del rechazo** y puede volver a subirlo.
- **Subir pagos**: registra su pago con comprobante (PDF/imagen). Si un pago fue rechazado, ve el motivo antes de reintentar.

### 💰 Financiero (Contabilidad)
- **Consolidación de pagos**: revisa cada pago con su comprobante y lo **aprueba (Consolidado)** o lo **rechaza con motivo obligatorio** (el comprobante rechazado se elimina del servidor).
- **Reporte general**: Excel consolidado de todos los estudiantes.

### 📜 Delegado Académico
- **Reporte para titulación**: filtra estudiantes por carrera y por estado de documentación/pagos. "Completo" significa **todos los documentos APROBADOS** y **pagos consolidados ≥ costo total**.
- **Registro Senescyt**: clasifica a los estudiantes en Incompletos / Para Aprobar / 100% Titulados, con filtros por carrera y módulo; registra acta, número Senescyt y fecha, y exporta titulados a Excel.

### 🏛️ Canciller (Dirección)
- **Dashboard gerencial**: KPIs y gráficas comparativas (población, ingresos nuevos, ingresos consolidados, proyección, ranking de carreras, estado de cobros) con filtros por mes y módulo.
- **Gestión de Módulos (CRUD)**: crea, lista, renombra y elimina módulos académicos (ej. 2026-1). Renombrar propaga el cambio a los estudiantes asignados; eliminar exige **doble confirmación** y se bloquea si el módulo está en uso.

---

## 2. Arquitectura del proyecto

```
public_html/
├── *.php                      → Páginas (controladores delgados + vista)
├── css/style.css              → Hoja de estilos única (con bloques compartidos)
├── js/rechazo.js              → JS compartido del formulario de rechazo
├── expedientes/               → Archivos físicos por estudiante
│   └── APELLIDOS_NOMBRES/
│       ├── DOCUMENTOS/        → Requisitos en PDF
│       └── PAGOS/             → Comprobantes de pago
└── includes/
    ├── bootstrap.php          → Arranque único: sesión + autoloader + helpers
    ├── helpers.php            → e(), dinero(), flash(), obtenerIniciales()
    ├── auth_check.php         → Guard de sesión y roles (exigir_rol)
    ├── requisitos.php         → Catálogo único de requisitos por programa
    ├── clases/                → Capa orientada a objetos
    │   ├── Database.php               → Conexión PDO a MySQL (singleton)
    │   ├── RepositorioBase.php        → Clase padre: PDO inyectado por constructor
    │   ├── EstudianteRepository.php   → Persistencia de estudiantes
    │   ├── DocumentoRepository.php    → Persistencia de documentos y su revisión
    │   ├── PagoRepository.php         → Persistencia de pagos y consolidación
    │   ├── UsuarioRepository.php      → Cuentas de acceso (contraseñas hasheadas)
    │   ├── ModuloRepository.php       → CRUD de módulos académicos
    │   ├── CredencialesEstudiante.php → Regla de generación de usuario/contraseña
    │   ├── Expediente.php             → Carpetas y archivos del expediente físico
    │   ├── ProgresoEstudiante.php     → Reglas de negocio: % documentación y pagos
    │   └── MetricasCanciller.php      → Consultas estadísticas del dashboard
    └── partials/              → Vistas compartidas
        ├── head.php           → <head> común (con cache-busting del CSS)
        ├── sidebar.php        → Menú lateral dirigido por configuración
        └── mensajes_flash.php → Avisos de éxito/error entre páginas
```

**Flujo de una petición:** cada página incluye `bootstrap.php` (sesión + autocarga), valida el rol con `exigir_rol()`, delega los datos a repositorios/servicios y renderiza su vista reutilizando los partials.

---

## 3. Principios SOLID aplicados

| Principio | Aplicación en NEO |
|---|---|
| **S — Responsabilidad Única** | Cada clase tiene una sola razón de cambio: `Database` solo conecta, cada `*Repository` persiste una entidad, `Expediente` solo maneja archivos físicos, `ProgresoEstudiante` solo calcula avances, las páginas solo orquestan y presentan. |
| **O — Abierto/Cerrado** | El sistema se extiende sin modificar lo existente: agregar una página al menú de un rol es **una línea** en el arreglo de `sidebar.php`; un requisito documental nuevo se agrega solo en `requisitos.php`; un módulo académico nuevo se crea desde la interfaz, sin tocar código. |
| **L — Sustitución de Liskov** | Todos los repositorios extienden `RepositorioBase` y respetan su contrato: cualquier repositorio puede usarse donde se espere la clase base sin romper el comportamiento. |
| **I — Segregación de Interfaces** | Clases pequeñas y enfocadas: ningún consumidor depende de métodos que no usa (ej. el Delegado usa `ProgresoEstudiante` sin conocer `Expediente`). |
| **D — Inversión de Dependencias** | La conexión `PDO` se **inyecta por constructor** en repositorios y servicios; ninguna clase decide de dónde sale la base de datos, lo que permite probarlas o cambiar el motor. |

---

## 4. Código limpio (Clean Code)

- **Nombres que se leen como texto**: `progresoDocumentos()`, `consolidar()`, `rechazar()`, `exigir_rol()`, `nombreExiste()` — en el idioma del dominio institucional.
- **Funciones pequeñas y de un solo nivel de abstracción**: los controladores se leen de arriba abajo: *validar → delegar → responder*.
- **Sin duplicación de estructuras**: una sola definición del `<head>`, del sidebar, de los avisos y de los estilos compartidos.
- **Comentarios útiles, no excesivos**: los comentarios explican el **porqué** de las decisiones (por qué renombrar un módulo propaga el cambio, por qué "completo" significa *aprobado*, por qué el usuario duplicado lleva sufijo), no el *qué* hace cada línea.
- **Cero código muerto**: los archivos obsoletos de versiones anteriores fueron eliminados.

---

## 5. Principio DRY (Don't Repeat Yourself)

Lógica que antes estaba duplicada y ahora vive en **un solo lugar**:

| Antes | Ahora |
|---|---|
| `obtenerIniciales()` definida en 5 páginas | `includes/helpers.php` |
| Sidebar copiado en ~15 páginas | `includes/partials/sidebar.php` |
| CSS de layout fijo repetido en ~12 páginas | Bloque compartido en `css/style.css` |
| Cálculo de deuda y porcentajes en 4 páginas | `ProgresoEstudiante` |
| Catálogo de requisitos en 4 páginas | `includes/requisitos.php` |
| SQL disperso en cada página | Repositorios por entidad |
| Mensajes entre páginas con claves distintas | Sistema único `flash()` + partial |

---

## 6. Buenas prácticas adicionales

- **Seguridad de contraseñas**: todas las cuentas usan `password_hash()` / `password_verify()` (bcrypt); ninguna contraseña de acceso se guarda en texto plano.
- **Prevención de inyección SQL**: el 100% de las consultas usa sentencias preparadas de PDO con parámetros nombrados.
- **Prevención de XSS**: toda salida de datos pasa por el helper `e()` (`htmlspecialchars`).
- **Sesiones seguras**: `session_regenerate_id(true)` al iniciar sesión (evita fijación de sesión) y guarda de rol en **cada** página.
- **Subida de archivos validada**: extensiones permitidas verificadas en servidor, nombres de archivo únicos generados con `uniqid()`, carpetas saneadas de caracteres especiales.
- **Transacciones**: operaciones que deben ser atómicas (crear estudiante + su cuenta; renombrar módulo + propagar) usan `beginTransaction()/commit()/rollback()`.
- **Integridad referencial**: claves foráneas con `ON DELETE CASCADE` y restricciones `UNIQUE` (cédula, usuario, documento por tipo).
- **Cache-busting del CSS**: el enlace a `style.css` incluye `?v=<filemtime>`, forzando al navegador a recargar los estilos cuando cambian.
- **Fallos elegantes**: si una consulta falla, la página se renderiza igual y muestra el error en un aviso, en lugar de romper el HTML.

---

## 7. Puesta en marcha

1. **Base de datos**: crear las tablas ejecutando el script SQL de creación en phpMyAdmin (tablas `estudiantes`, `usuarios`, `documentos`, `pagos`, `modulos` + usuarios administrativos semilla).
2. **Credenciales de conexión**: configuradas en `includes/clases/Database.php` (host `localhost` dentro de Hostinger).
3. **Permisos**: la carpeta `expedientes/` debe permitir escritura (755) para las subidas de archivos.
4. **Acceso**: `login.php` con las cuentas administrativas; las cuentas de estudiantes se generan automáticamente al crearlos.

---

*Sistema desarrollado para el Instituto Superior Tecnológico RTV — Proyecto NEO, 2026.*
