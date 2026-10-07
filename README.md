# Conexa e-learning

Intranet interna de capacitación e inducción para los nuevos trabajadores de Conexa. Organiza el contenido en **áreas** (cursos) → **módulos** → **lecciones**, con un **quiz** que da acceso a cada módulo, seguimiento de avance por trabajador, reportes en PDF y una vista de **ruta de aprendizaje** estilo Duolingo sobre el mismo motor de progreso.

Alcance: una sola empresa. Destino de despliegue: Hostinger. La arquitectura y las reglas de negocio están en [`doc/arquitectura.md`](doc/arquitectura.md).

## Stack

- PHP 8.3+, Laravel 13
- MySQL / MariaDB (`utf8mb4`)
- Filament 5 para el panel de administración (`/admin`)
- Laravel Breeze (Blade + Alpine.js + Tailwind) para login y vistas del trabajador
- `barryvdh/laravel-dompdf` para los PDF
- Vite para los assets

## Funcionalidades

**Trabajador**
- Dashboard con sus áreas asignadas y su avance.
- Visor de lecciones (contenido, PDF y video), marcado de lección completada.
- Módulos en orden: se desbloquean al aprobar el anterior.
- Quiz calificado en el servidor, con nota mínima editable (70% por defecto) e historial de intentos.
- Encuesta de feedback por área y descarga de resumen en PDF por módulo y por área.
- Modo lista y modo ruta de aprendizaje.

**Administrador** (`/admin`)
- CRUD de áreas, módulos, lecciones, quizzes, preguntas y opciones.
- Gestión de usuarios y asignación de áreas.
- Dashboard con resumen general, avance por área y estado de trabajadores.
- Reporte de avance, reporte PDF por trabajador y consulta de feedback.

No hay registro público: solo un admin crea cuentas, y el trabajador debe cambiar su contraseña temporal en el primer ingreso.

## Instalación local

```sh
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configura la base de datos en `.env` (el proyecto usa MySQL/MariaDB; `.env.example` trae `sqlite` por defecto) y luego:

```sh
php artisan migrate --seed
npm run build
```

El seeder crea las áreas iniciales y un administrador (`admin@conexa.pe`; la contraseña está en `database/seeders/AdminSeeder.php`, cámbiala al ingresar).

## Desarrollo

```sh
composer run dev      # servidor, cola y Vite en paralelo
composer run test     # limpia la caché de config y ejecuta php artisan test
vendor/bin/pint --dirty --format agent   # formatear PHP modificado
```

Un solo archivo de tests: `php artisan test tests/Feature/QuizTest.php`.

## Estructura relevante

| Ruta | Contenido |
|---|---|
| `app/Models` | Modelos Eloquent del dominio (`Area`, `Modulo`, `Leccion`, `Quiz`, `Pregunta`, `Opcion`, `IntentoQuiz`, `ModuloAprobado`, `Feedback`, `User`) |
| `app/Services` | Reglas de negocio compartidas: `ProgresoService`, `QuizService`, `RutaService`, `DashboardService`, `ReporteService`, `ReporteAdminService` |
| `app/Filament` | Panel admin: resources, páginas, widgets y traits de formularios |
| `app/Policies` | Autorización por asignación de área |
| `app/Http/Controllers` | Controladores del trabajador (áreas, módulos, lecciones, quiz, resúmenes, feedback) |
| `doc/arquitectura.md` | Arquitectura, reglas de negocio y plan de despliegue |

## Roles

Solo dos roles, guardados en `users.rol`: `admin` y `trabajador`. El middleware `admin` protege las rutas de administración; el acceso a cada área, módulo o lección se valida por asignación con policies.

## Despliegue

Ver la sección "Despliegue en Hostinger" de [`doc/arquitectura.md`](doc/arquitectura.md). En producción: `APP_DEBUG=false`, HTTPS y credenciales solo en `.env`.
