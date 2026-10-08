# Arquitectura · Intranet de capacitación Conexa

Oct 6, 2026 · @JHAMIL YEYDER TURPO

Sistema interno para capacitar a los nuevos trabajadores de Conexa, construido en Laravel. Organiza la inducción en **áreas** (equivalen a cursos) → **módulos** → **lecciones** → **quiz**, con seguimiento de avance por trabajador, reporte en PDF descargable y una ruta de aprendizaje tipo Duolingo. Alcance: una sola empresa, para desplegar en Hostinger. Plazo: una semana desde el 6 oct 2026.

## Stack y decisiones

Todo parte de PHP, que es el requisito, y de Laravel, que el equipo ya maneja. Lo demás se elige para caber en una semana y desplegarse fácil en Hostinger.

| Capa | Elección | Por qué |
| --- | --- | --- |
| Lenguaje | PHP 8.2+ | Requisito del proyecto. Confirmar la versión del plan de Hostinger. |
| Framework | Laravel | Ya se domina. Trae autenticación, migraciones, ORM y validación listos. |
| Autenticación | Laravel Breeze, con registro público desactivado | Solo el admin crea cuentas. Breeze es el starter más liviano. |
| Base de datos | MySQL / MariaDB, `utf8mb4` | Incluida en Hostinger. `utf8mb4` evita problemas con tildes y ñ. |
| Acceso a datos | Eloquent (ORM de Laravel) | Relaciones área → módulo → lección casi sin SQL a mano. |
| Panel admin | Filament (solo el admin) | Genera el CRUD, filtros y login del admin. Ahorra 1–2 días. Sigue siendo Laravel. |
| Vista del trabajador | Blade + Tailwind + Alpine.js | Vienen con Breeze. Permiten el diseño propio de la ruta y las lecciones. |
| Editor de lecciones | RichEditor de Filament | El admin escribe sin saber HTML. |
| Sanitizado de HTML | `mews/purifier` | Limpia lo que el admin pega (p. ej. desde Word) antes de guardarlo. |
| PDF | `barryvdh/laravel-dompdf` | El reporte se diseña como una vista Blade y se convierte a PDF. |
| Videos | Embed de YouTube (no listado) o Drive | No se aloja ni sirve video: ahorra espacio y ancho de banda. |
| Dependencias | Composer en tu PC, subes `vendor/` | No dependes de que Hostinger tenga Composer. |

Filament es un paquete **dentro** de Laravel, no un reemplazo: tus modelos, migraciones y rutas siguen siendo Laravel. Solo decide si el panel admin lo generas con Filament o lo programas a mano en Blade.

## Entorno de desarrollo

La arquitectura corre en cualquier PHP 8.2+ con MySQL o MariaDB, así que XAMPP es opcional.

- **Windows:** Laragon. Un instalador trae PHP, MySQL y Composer, con URLs tipo `conexa.test`. Más liviano que XAMPP y casi sin configuración.
- **Mac o Linux:** PHP y MySQL con Homebrew o apt, y `php artisan serve` como servidor local.
- **Sin instalar nada:** desarrollar directo en un subdominio de pruebas de Hostinger. Más lento por cada subida; solo si tu PC no da.

## Arquitectura por capas

Controllers delgados, reglas en Services, datos en Models (Eloquent) y HTML en vistas Blade. Si mañana cambia la nota mínima o la regla de desbloqueo, se toca un solo archivo.

```
Navegador  →  GET /areas/ventas
   ▼
routes/web.php  →  Middleware (¿logueado? ¿rol admin o trabajador?)
   ▼
Controller      recibe, valida y decide qué mostrar
   ├─► Policy     ¿este trabajador tiene asignada esta área?
   ├─► Service    reglas: calificar quiz, % de avance, ¿módulo desbloqueado?, armar reporte
   ├─► Model      Eloquent ─► MySQL
   ▼
Vista Blade + Tailwind  ─►  HTML   (o PDF vía Dompdf para el reporte)
```

El panel del admin es la excepción: Filament se encarga de su propio flujo (rutas, vistas y controladores del panel), apoyándose en los mismos Models.

## Estructura de carpetas

Laravel ya trae esta estructura; solo se agregan `app/Services` y las carpetas de Filament (que el propio paquete genera).

```
conexa-capacitacion/
├─ app/
│  ├─ Models/           Usuario, Area, Modulo, Leccion, Quiz, Pregunta, Opcion,
│  │                     ProgresoLeccion, IntentoQuiz, ModuloAprobado, Feedback
│  ├─ Http/
│  │  ├─ Controllers/   del trabajador (Dashboard, Area, Leccion, Quiz, Feedback, Reporte)
│  │  └─ Middleware/    EnsureAdmin, y el de Breeze
│  ├─ Policies/         AreaPolicy, ModuloPolicy, LeccionPolicy
│  ├─ Services/         ProgresoService, QuizService, ReportePdfService
│  └─ Filament/         Resources del admin (generados por Filament)
├─ database/
│  ├─ migrations/       una por tabla
│  └─ seeders/          AreaSeeder (las 3 áreas), AdminSeeder
├─ resources/views/     Blade del trabajador (inicio, área, lección, quiz, ruta, pdf)
├─ routes/web.php       rutas del trabajador (las del admin las pone Filament)
└─ public/              única carpeta pública; el dominio de Hostinger apunta aquí
```

## Modelo de datos

Jerarquía fija de tres niveles. La profundidad es fija (área → módulo → lección), pero la cantidad en cada nivel es libre: un área tiene los módulos que quieras y un módulo las lecciones que quieras.

```
Area 1──N Modulo 1──N Leccion
                 └─1 Quiz 1──N Pregunta 1──N Opcion
Usuario N──M Area            (areas asignadas al trabajador)
Usuario N──M Leccion         (progreso: lección vista)
Usuario 1──N IntentoQuiz ─► Quiz
Usuario 1──N ModuloAprobado ─► Modulo   (fecha y nota congeladas)
Usuario 1──N Feedback ─► Area
```

| Decisión | Por qué |
| --- | --- |
| Área = curso | Cada área es un curso con sus módulos y lecciones. Si Conexa pide "Seguridad industrial", es un área nueva. |
| Un quiz por módulo | Cada módulo se cierra con una evaluación. |
| Se guardan todos los intentos | El PDF muestra intentos y evolución, no solo la última nota. |
| `ModuloAprobado` con fecha y nota congeladas | El historial y el PDF no cambian aunque luego se edite el módulo. |
| Estado del módulo calculado, no guardado | Módulo completo = todas las lecciones vistas + quiz aprobado. Nada queda desincronizado al agregar una lección. |
| Áreas asignadas por usuario | Un asesor de ventas no ve Administrativa; Bienvenida la ven todos. |
| Nota mínima global con excepción por quiz | Cumple el 70% editable sin perder flexibilidad. |
| Desactivar en vez de borrar | Borrar un módulo no destruye notas históricas ni PDF ya generados. |

## Reglas de negocio clave

Estas reglas viven en `ProgresoService` y `QuizService`, en un solo lugar, para que el dashboard, el PDF y el panel admin nunca se contradigan.

- **Avance de un área** = módulos aprobados / módulos activos del área. El avance global es el promedio de sus áreas asignadas.
- **Módulo completo** = todas sus lecciones activas marcadas como vistas **y** quiz aprobado.
- **Aprobación congelada:** al aprobar, se escribe una fila en `ModuloAprobado` (fecha + nota) una sola vez. El historial y el PDF leen de ahí, así que no cambian si después se edita el módulo.
- **Lección agregada después:** si el admin suma una lección a un módulo ya aprobado, esa lección aparece como "Nueva" y pendiente, pero el módulo **sigue aprobado**. En el PDF sale en una sección aparte: "Lecciones agregadas después". El área cuenta como completa, con una insignia visible "1 lección nueva", para que nadie quede desaprobado por algo que agregó la empresa.
- **Desbloqueo:** los módulos se recorren en orden dentro de cada área (configurable). El siguiente se abre al aprobar el anterior.

## Roles y seguridad

Dos roles: `admin` y `trabajador`, con una columna `rol` en `usuarios` y el middleware `EnsureAdmin`. Con solo dos roles, un paquete como Spatie sería exceso.

- **Autorización por registro (Policies):** cada acción verifica que el área, módulo o lección pertenezca al trabajador. Sin esto, cambiando el ID en la URL vería contenido ajeno. Es la falla más común en este tipo de sistema.
- **Quiz calificado en el servidor:** las respuestas correctas nunca viajan al navegador. Si no, se verían con "Inspeccionar".
- **PDFs de lecciones protegidos:** se sirven por un controlador que exige login, no por URL pública.
- **Lo que Laravel ya da:** hash bcrypt de contraseñas, CSRF en todos los formularios, consultas parametrizadas por Eloquent, escape automático en Blade y limitador de intentos de login.
- **Cuentas nuevas:** el admin las crea con contraseña temporal; el trabajador la cambia en su primer ingreso.
- **Producción:** HTTPS (Hostinger da SSL gratis), cookies `secure`, `APP_DEBUG=false` y credenciales solo en `.env`, fuera de `public/`.

## Pantallas y modos de visualización

**Trabajador:** inicio (sus áreas con % de avance) → área → módulo → lección → quiz → resultado → feedback del área → descarga del PDF.

**Admin (Filament):** panel, usuarios, áreas, módulos, lecciones, quizzes, reportes y configuración (nota mínima).

Dos modos para mostrar los módulos de un área, sobre el **mismo motor** (lecciones, quiz, progreso, desbloqueo, PDF son idénticos). Solo cambia la pantalla:

- **Modo lista (estándar):** módulos como tarjetas con barra de avance y estados (bloqueado, en curso, completado, aprobado). Es lo funcional y va sí o sí.
- **Modo ruta (tipo Duolingo):** una piel sobre el mismo avance. El área es la unidad, cada lección es un nodo y al final de cada módulo hay un nodo de quiz, en un camino vertical en zigzag que funciona bien en celular.

```
 ÁREA: Ventas
  MÓDULO 1 · Producto
      ✔ Lección 1
          ✔ Lección 2
      ● Lección 3   ← estás aquí
          ★ Quiz del módulo (bloqueado)
  MÓDULO 2 · Proceso comercial
      🔒 ...
```

Qué copiar de Duolingo: camino visual, nodos con estados, botón "Continuar" y una pequeña celebración al cerrar un módulo. Qué evitar: vidas, ligas y rachas — en una capacitación obligatoria generan presión sin aportar. Se construye al final porque es solo presentación; si el tiempo aprieta, sale la lista y la ruta queda como mejora.

## Alcance del CRUD

El contenido vive en la base de datos, no en el código. Lo único fijo es lo que carga el seeder (las 3 áreas iniciales); después el admin crea todo sin tocar código. No es una plataforma de cursos genérica multiempresa, y ahí están los límites.

**Qué puede administrar el admin:**

| Entidad | Puede |
| --- | --- |
| Áreas | Crear, editar, ordenar, activar/desactivar (cuantas quiera) |
| Módulos | Crear dentro de un área, ordenar, desactivar (cantidad libre) |
| Lecciones | Texto enriquecido, video embebido o PDF subido, con orden y duración |
| Quizzes | Preguntas, opciones, marcar la correcta, nota mínima propia |
| Usuarios | Crear, asignar áreas, activar/desactivar, resetear contraseña |
| Configuración | Nota mínima global, nombre y logo |
| Reportes | Ver avance y notas por trabajador, descargar su PDF (solo lectura) |

**Qué queda fuera a propósito (por el plazo):**

- Estructura fija de 3 niveles: sin submódulos ni cursos dentro de áreas.
- Un solo tipo de pregunta: opción múltiple con una respuesta correcta (verdadero/falso entra como 2 opciones).
- Una sola empresa: no es multiempresa. Es lo más caro de cambiar después.
- Sin fechas límite, correos automáticos, foros, certificados verificables ni SCORM.
- Una lección pertenece a un solo módulo.

## Extras: baratos vs caros

**Baratos (horas), casi sin código gracias a Filament:**

| Extra | Cómo |
| --- | --- |
| Duplicar módulos | Acción de replicar de Filament, copiando lecciones y quiz. Agregar un chequeo para copiar también las preguntas, porque la copia por defecto solo duplica el registro. |
| Reordenar arrastrando | Tablas reordenables sobre la columna `orden`. |
| Importar usuarios desde CSV | Columnas `nombre, email, cargo`. Se crean con contraseña temporal y la cambian al primer ingreso. Mostrar vista previa con errores antes de confirmar. |
| Áreas automáticas por cargo | Tabla `cargo_area` (cargo → áreas). Al crear o importar un usuario se asignan solas; el admin puede ajustarlas. |

**Caros (días), para una versión futura:** multiempresa, nuevos tipos de pregunta, y fechas límite con avisos por correo.

## Plan de 7 días

Una fase por día, con tu OK antes de avanzar a la siguiente.

1. **Base.** Proyecto Laravel, Breeze sin registro, migraciones (incluida `modulo_aprobados`), seeders, roles y policies. Despliegue de prueba a Hostinger.
2. **Admin.** Panel con Filament: CRUD anidado área → módulo → lección, duplicar, reordenar, cargo → áreas e importación CSV.
3. **Trabajador.** Vista en modo lista: inicio, visor de lección y registro de progreso.
4. **Quizzes.** Calificación en el servidor, nota mínima 70 editable, historial de intentos y registro en `modulo_aprobados`.
5. **Reporte.** PDF de resumen, encuesta de feedback y descarga para el admin.
6. **Ruta + contenido.** Modo ruta tipo Duolingo, pulido visual y carga del contenido real.
7. **Cierre.** Pruebas y despliegue final.

El día 2 es el más cargado. Si hay que recortar, la importación CSV es lo primero que se mueve. Y el día 6 depende del contenido real: si no llega, el proyecto se cae aunque el código esté perfecto; pídelo ya.

## Despliegue en Hostinger

1. Revisa que tu plan permita apuntar el dominio a la carpeta `public/` y que su versión de PHP cumpla lo que pide la versión de Laravel y de Filament que uses.
2. Crea la base de datos MySQL en hPanel.
3. Sube el proyecto (incluida `vendor/`) por el Administrador de archivos o FTP. Si el plan tiene SSH, puedes correr Composer allá; si no, sube `vendor/` ya instalado.
4. Configura `.env`: credenciales de la base, `APP_ENV=production`, `APP_DEBUG=false` y `APP_KEY` (`php artisan key:generate`).
5. Corre las migraciones y los seeders (`php artisan migrate --seed`) por SSH, o importa el SQL si no hay SSH.
6. Activa SSL gratis de Hostinger.

Haz un despliegue de prueba el día 1, no el día 7: es donde más sorpresas salen.

## Decisiones pendientes

- [ ] Color corporativo y logo de Conexa (para el tema de Tailwind y el PDF).
- [ ] ¿Usar Filament para el admin, o programarlo a mano? Define el alcance del día 2.
- [ ] Confirmar versión de PHP y soporte de `public/` + SSH en el plan de Hostinger.
- [ ] ¿La encuesta de feedback la ve también RRHH, o es solo registro interno?
- [ ] Fecha límite real para el contenido (textos, videos, preguntas) de parte de Conexa.

Confirmadas: cliente Conexa (una sola empresa), local y luego Hostinger, cuentas creadas solo por el admin, nota mínima 70 editable, módulos en orden, PDF visible también para el admin.
