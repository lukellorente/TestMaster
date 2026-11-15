# TestMaster — Documentación Técnica

> **Plataforma de creación, realización y seguimiento de exámenes en línea.**
> Stack: Laravel 12 + Vue 3 · MySQL 8.4 · Docker (Laravel Sail) · Caddy 2
>
> Última actualización: noviembre 2026 · Rama `Redesign`

---

## Tabla de Contenidos

1. [Visión General del Proyecto](#1-visión-general-del-proyecto)
2. [Stack Tecnológico](#2-stack-tecnológico)
3. [Arquitectura del Sistema](#3-arquitectura-del-sistema)
4. [Instalación y Despliegue](#4-instalación-y-despliegue)
   - 4.1 [Entorno de desarrollo](#41-entorno-de-desarrollo)
   - 4.2 [Variables de entorno](#42-variables-de-entorno)
   - 4.3 [Producción](#43-producción)
   - 4.4 [Tareas programadas](#44-tareas-programadas)
   - 4.5 [Comandos útiles](#45-comandos-útiles)
5. [Base de Datos](#5-base-de-datos)
   - 5.1 [Diagrama de Relaciones (ER)](#51-diagrama-de-relaciones-er)
   - 5.2 [Detalle de Tablas](#52-detalle-de-tablas)
   - 5.3 [Migraciones](#53-migraciones)
6. [Backend — Laravel](#6-backend--laravel)
   - 6.1 [Modelos y Relaciones Eloquent](#61-modelos-y-relaciones-eloquent)
   - 6.2 [API REST — Endpoints](#62-api-rest--endpoints)
   - 6.3 [Autenticación (Sanctum SPA)](#63-autenticación-sanctum-spa)
   - 6.4 [Middleware y Rate Limiting](#64-middleware-y-rate-limiting)
   - 6.5 [Controladores en Detalle](#65-controladores-en-detalle)
   - 6.6 [Capa de Dominio](#66-capa-de-dominio)
7. [Frontend — Vue.js](#7-frontend--vuejs)
   - 7.1 [Estructura de Archivos](#71-estructura-de-archivos)
   - 7.2 [Router y Navegación](#72-router-y-navegación)
   - 7.3 [Store (Pinia)](#73-store-pinia)
   - 7.4 [Capa API (Axios)](#74-capa-api-axios)
   - 7.5 [Páginas](#75-páginas)
   - 7.6 [Componentes](#76-componentes)
   - 7.7 [Tema y Sistema de Diseño](#77-tema-y-sistema-de-diseño)
8. [Lógica de Negocio](#8-lógica-de-negocio)
   - 8.1 [Creación y Edición de Tests](#81-creación-y-edición-de-tests)
   - 8.2 [Tipos de Pregunta y Formatos](#82-tipos-de-pregunta-y-formatos)
   - 8.3 [Corrección y Puntuación](#83-corrección-y-puntuación)
   - 8.4 [Modo Repaso y Mi Progreso](#84-modo-repaso-y-mi-progreso)
   - 8.5 [Cómo añadir un tipo de pregunta](#85-cómo-añadir-un-tipo-de-pregunta)
   - 8.6 [Tests con Contraseña](#86-tests-con-contraseña)
   - 8.7 [Administración](#87-administración)
9. [Seeders y Datos de Prueba](#9-seeders-y-datos-de-prueba)
10. [Testing](#10-testing)
11. [Privacidad y RGPD](#11-privacidad-y-rgpd)
12. [Notas de Seguridad](#12-notas-de-seguridad)
13. [Funcionalidades Planificadas (No Implementadas)](#13-funcionalidades-planificadas-no-implementadas)
14. [Historial de Cambios](#14-historial-de-cambios)

---

## 1. Visión General del Proyecto

**TestMaster** es una plataforma web para crear, compartir y realizar exámenes/cuestionarios en línea, con seguimiento del progreso del alumno. Está dirigida a estudiantes, docentes, academias y entornos corporativos.

### ¿Qué permite hacer?

| Actor | Acciones |
|---|---|
| **Visitante (sin cuenta)** | Ver el catálogo de tests públicos y los destacados, buscar/filtrar/ordenar, ver el detalle de un test, ver perfiles públicos, leer ayuda y textos legales |
| **Usuario registrado** | Todo lo anterior + crear, editar, duplicar preguntas y borrar sus tests; realizar tests; ver resultados detallados; **repasar sus fallos**; consultar **Mi progreso**; gestionar perfil y avatar; cambiar contraseña; **exportar sus datos** y **borrar su cuenta** |
| **Admin** (`role = admin`) | Todo lo anterior + panel `/admin`: métricas, gestión de tests (estado, destacados, borrado), usuarios (rol, suspensión), categorías y registro de auditoría. Puede ver/editar cualquier test y no necesita la contraseña de los tests protegidos |

### Funcionalidades principales

- **7 tipos de pregunta** con puntuación parcial y penalización por fallo opcional.
- **Corrección automática** en el servidor; las soluciones nunca se envían al cliente antes de corregir.
- **Modo repaso**: rehacer solo las preguntas falladas, sin gastar intentos.
- **Mi progreso**: racha, calendario de actividad, dominio por categoría e historial.
- **Tests con contraseña**, modo de presentación paginado o en una sola página.
- **Panel de administración** con auditoría.
- **Autenticación SPA** con cookie de sesión httpOnly + CSRF.
- **Cumplimiento RGPD**: consentimiento versionado, exportación, supresión, minimización y retención.
- **Tema claro/oscuro** basado en *design tokens*.

---

## 2. Stack Tecnológico

| Capa | Tecnología | Versión |
|---|---|---|
| **Backend (API)** | PHP + Laravel | PHP 8.2+ (imagen Docker 8.5), Laravel 12 |
| **Frontend (SPA)** | Vue.js | 3.5 (Composition API, `<script setup>`) |
| **Router Frontend** | Vue Router | 4.x |
| **Estado Frontend** | Pinia | 2.x |
| **Build Tool** | Vite + `laravel-vite-plugin` | Vite 7 |
| **CSS** | Design tokens propios + Tailwind CSS | Tailwind 4 |
| **Iconos / Fuente** | Font Awesome Free · Wix Madefor Text (`@fontsource`) | 6.x · 5.x |
| **HTTP Client** | Axios | 1.x |
| **Autenticación** | Laravel Sanctum (modo SPA, cookie de sesión) | 4.x |
| **Base de Datos** | MySQL (SQLite admitido en local) | 8.4 |
| **Contenedores** | Docker vía Laravel Sail | — |
| **Proxy / HTTPS** | Caddy (solo producción) | 2.x |
| **Procesos** | Supervisor (PHP + `schedule:work`) | — |
| **Testing** | PHPUnit | 11.x |

### Dependencias principales (composer.json)

```
laravel/framework    → Framework principal
laravel/sanctum      → Autenticación SPA
laravel/tinker       → REPL para depuración
laravel/sail (dev)   → Entorno Docker
laravel/pail (dev)   → Visor de logs en tiempo real
laravel/pint (dev)   → Formateador de código
phpunit/phpunit (dev)
```

### Dependencias principales (package.json)

```
vue, vue-router, pinia         → SPA
axios                          → Cliente HTTP
@fortawesome/fontawesome-free  → Iconos
@fontsource/wix-madefor-text   → Tipografía (auto-alojada, compatible con la CSP)
vite, @vitejs/plugin-vue, laravel-vite-plugin, @tailwindcss/vite, tailwindcss
concurrently                   → `composer dev`
```

---

## 3. Arquitectura del Sistema

### Patrón general

```
┌──────────────────────────────────────────────────────────────────┐
│                         NAVEGADOR (SPA)                          │
│  ┌───────────┐  ┌────────────┐  ┌──────────────┐  ┌───────────┐  │
│  │ Vue Router│  │   Pinia    │  │    Axios     │  │Componentes│  │
│  │ (guards)  │  │ (auth)     │  │ cookie+XSRF  │  │  (UI)     │  │
│  └───────────┘  └────────────┘  └──────┬───────┘  └───────────┘  │
└────────────────────────────────────────┼─────────────────────────┘
                                         │ /api/*  ·  /sanctum/csrf-cookie
                          ┌──────────────▼──────────────┐
                          │  Caddy 2 (solo producción)  │  HTTPS, CSP, HSTS…
                          └──────────────┬──────────────┘
┌────────────────────────────────────────▼─────────────────────────┐
│                         LARAVEL 12                               │
│  routes/web.php  → catch-all → vista app.blade.php (SPA)         │
│  routes/api.php  → middleware: statefulApi · throttle ·          │
│                    EnsureAccountActive · auth:sanctum · admin    │
│                                                                  │
│  Controladores Api/                                              │
│    AuthController · AccountController · TestController           │
│    AttemptController · ProfileController · DashboardController   │
│    Admin/AdminController · Admin/AdminCategoryController         │
│                                                                  │
│  Dominio                                                         │
│    app/Questions  → QuestionTypes, Types/*, AttemptScorer,       │
│                     ReviewQueue                                  │
│    app/Progress   → StudyActivity, CategoryMastery               │
│    app/Support    → TestAccess                                   │
│                                                                  │
│  Modelos Eloquent → User, Test, Question, TestAttempt, …         │
│  Scheduler        → limpieza de sesiones / tokens (RGPD)         │
└────────────────────────────────────────┬─────────────────────────┘
                                         │
                               ┌─────────▼─────────┐
                               │     MySQL 8.4     │
                               └───────────────────┘
```

### Flujo de una petición típica

1. El usuario navega a una URL (ej.: `/test/5`).
2. Laravel sirve siempre la misma vista Blade (catch-all en `routes/web.php`).
3. Antes de montar la ruta, el guard de Vue Router llama a `authStore.init()` (`GET /api/me`) para saber si hay sesión.
4. Vue Router renderiza `ViewTest.vue`, que pide `GET /api/tests/5` con Axios (la cookie de sesión viaja sola).
5. Laravel aplica el middleware, el controlador consulta Eloquent y devuelve JSON.
6. El componente actualiza la interfaz.

### Infraestructura Docker

**Desarrollo (`compose.yaml`)**

| Servicio | Imagen | Puertos | Notas |
|---|---|---|---|
| `laravel.test` | `sail-8.5/app` | `APP_PORT` (80), `VITE_PORT` (5173) | Volumen `.:/var/www/html`, espera a que MySQL esté *healthy* |
| `mysql` | `mysql:8.4` | `FORWARD_DB_PORT` (3306) | Volumen `sail-mysql`, healthcheck con `mysqladmin ping` |

**Producción (`compose.prod.yaml`)**

| Servicio | Notas |
|---|---|
| `laravel.test` | Sin puertos expuestos: solo accesible a través de Caddy en la red interna `sail` |
| `mysql` | Sin puertos expuestos |
| `caddy` | Puertos 80/443, certificado HTTPS automático para `APP_DOMAIN`, cabeceras de seguridad (ver [§12](#12-notas-de-seguridad)) |

El contenedor de la app ejecuta con **Supervisor** dos programas: el servidor PHP y `php artisan schedule:work` (tareas programadas).

---

## 4. Instalación y Despliegue

### 4.1 Entorno de desarrollo

**Requisitos:** Docker + Docker Compose y Git. (Sin Docker: PHP 8.2+, Composer, Node.js 18+ y MySQL 8 o SQLite.)

```bash
# 1. Clonar el repositorio
git clone https://github.com/HiImGhost666/TestMaster.git
cd TestMaster

# 2. Copiar y ajustar el entorno (ver 4.2)
cp .env.example .env

# 3. Instalar dependencias de PHP
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html \
    laravelsail/php84-composer:latest composer install --ignore-platform-reqs

# 4. Levantar los contenedores
./vendor/bin/sail up -d

# 5. Clave, migraciones, datos de prueba y enlace de storage (avatares)
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail artisan storage:link

# 6. Frontend
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev      # desarrollo (HMR)
./vendor/bin/sail npm run build    # compilación de producción
```

**Sin Docker:**

```bash
composer setup     # install + .env + key:generate + migrate + npm install + npm run build
php artisan db:seed
composer dev       # php artisan serve + queue:listen + pail + vite (concurrently)
```

### 4.2 Variables de entorno

| Variable | Descripción | Valor típico (dev) |
|---|---|---|
| `APP_URL` | URL base | `http://localhost` |
| `APP_DOMAIN` | Dominio público (producción, lo usa Caddy y se añade a los dominios *stateful* de Sanctum) | `testmaster.ejemplo.com` |
| `DB_CONNECTION` | Driver (`.env.example` trae `sqlite`; con Sail usar `mysql`) | `mysql` |
| `DB_HOST` / `DB_PORT` | Host y puerto | `mysql` / `3306` |
| `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | Credenciales | `laravel` / `sail` / `password` |
| `SESSION_DRIVER` | Almacén de sesiones | `database` |
| `SESSION_LIFETIME` | Duración de la sesión (min) | `120` |
| `SESSION_ENCRYPT` | Cifrar el contenido de la sesión | `true` |
| `SESSION_SECURE_COOKIE` | Cookie solo por HTTPS | `false` en dev, **`true` en producción** |
| `SESSION_HTTP_ONLY` / `SESSION_SAME_SITE` | Cookie inaccesible desde JS / política SameSite | `true` / `lax` |
| `SANCTUM_STATEFUL_DOMAINS` | Dominios que reciben autenticación por cookie | `localhost,127.0.0.1` |
| `LOG_STACK` / `LOG_DAILY_DAYS` | Logs diarios y retención | `daily` / `30` |
| `SEED_ADMIN_PASSWORD` | Contraseña del admin de prueba. Si falta, el seeder genera una aleatoria y la imprime | — |
| `APP_PORT` / `VITE_PORT` / `FORWARD_DB_PORT` | Puertos de Sail | `80` / `5173` / `3306` |

### 4.3 Producción

1. Configura `.env` con `APP_ENV=production`, `APP_DEBUG=false`, `APP_DOMAIN`, `APP_URL=https://…`, `SESSION_SECURE_COOKIE=true` y las credenciales de MySQL.
2. Apunta el DNS del dominio al servidor (Caddy obtiene el certificado automáticamente).
3. Ejecuta:

```bash
./deploy.sh
```

`deploy.sh` (pide confirmación):

1. `docker compose -f compose.prod.yaml down`
2. `docker compose -f compose.prod.yaml up -d --build`
3. Espera a que MySQL responda a `mysqladmin ping`.
4. `php artisan optimize:clear`
5. `php artisan migrate --force`
6. `npm ci` y `npm run build`

> El script continúa aunque falle un paso (muestra el error y sigue). Revisa la salida.

### 4.4 Tareas programadas

Definidas en `routes/console.php` y ejecutadas por el programa `scheduler` de Supervisor:

| Comando | Frecuencia | Qué hace |
|---|---|---|
| `gdpr:prune-sessions` | Cada hora | Borra sesiones caducadas (contienen IP y user-agent) |
| `gdpr:prune-reset-tokens` | Diaria | Borra tokens de restablecimiento de contraseña de más de 1 día |
| `sanctum:prune-expired --hours=24` | Diaria | Borra tokens de Sanctum caducados |

Los logs rotan a diario y se conservan `LOG_DAILY_DAYS` (30) días.

### 4.5 Comandos útiles

```bash
./vendor/bin/sail shell                         # Shell dentro del contenedor
./vendor/bin/sail artisan test                  # Ejecutar los tests
./vendor/bin/sail artisan migrate:fresh --seed  # Reiniciar la base de datos
./vendor/bin/sail artisan route:list --path=api # Ver rutas de la API
./vendor/bin/sail artisan schedule:list         # Ver tareas programadas
./vendor/bin/sail artisan optimize:clear        # Limpiar cachés
./vendor/bin/sail artisan pail                  # Logs en tiempo real
./vendor/bin/pint                               # Formatear código PHP
```

---

## 5. Base de Datos

### 5.1 Diagrama de Relaciones (ER)

```
┌────────────────┐       ┌────────────────┐       ┌──────────────────┐
│     users      │       │     tests      │       │   categories     │
├────────────────┤       ├────────────────┤       ├──────────────────┤
│ id (PK)        │──┐    │ id (PK)        │──┐    │ id (PK)          │
│ email          │  │    │ user_id (FK,   │  │    │ name, slug       │
│ password_hash  │  │    │   nullable)    │  │    │ icon, color      │
│ name, avatar   │  │    │ title          │  │    │ parent_id (FK)   │
│ role           │  │    │ is_public      │  │    │ tests_count      │
│ privacy_*      │  │    │ is_featured    │  │    └────────┬─────────┘
│ suspended_at   │  │    │ featured_at    │  │             │
│ last_login     │  │    │ password (hash)│  │    ┌────────▼─────────┐
└──────┬─────────┘  │    │ wrong_penalty  │  ├───►│ test_categories  │
       │            │    │ display_mode   │  │    └──────────────────┘
       │ 1:1        │    │ status         │  │
       ▼            │    └──────┬─────────┘  │    ┌──────────────────┐
┌──────────────┐    │           │ 1:N        └───►│   test_stats     │
│ user_points  │    │           ▼                 └──────────────────┘
│ user_stats   │    │    ┌────────────────┐
└──────────────┘    │    │   questions    │
                    │    ├────────────────┤
                    │    │ type (7 tipos) │
                    │    │ content, points│
                    │    │ numeric_answer │
                    │    │ numeric_toler. │
                    │    └──────┬─────────┘
                    │           │ 1:N
                    │           ├──► question_options     (opciones / V-F / respuesta corta)
                    │           ├──► question_blanks      (fill_blank)
                    │           ├──► question_order_items (order_items)
                    │           └──► question_stats
                    │
                    │ 1:N
                    ▼
            ┌────────────────┐         ┌────────────────────┐
            │ test_attempts  │  1:N    │ question_responses │
            ├────────────────┤────────►├────────────────────┤
            │ user_id, test_id│        │ question_id        │
            │ mode (exam/    │         │ given_answer (json)│
            │       review)  │         │ is_correct         │
            │ score, passed  │         │ points_earned (dec)│
            │ points_earned  │         │ display_order      │
            └────────────────┘         └────────────────────┘

┌────────────────────┐    Tablas sin API/UI todavía (ver §13):
│ admin_audit_logs   │    comments, ratings, follows, groups,
├────────────────────┤    group_members, group_tests, point_history,
│ admin_id (FK, null)│    settings, activity_logs, notifications, reports
│ action, target_*   │
│ details (json)     │
└────────────────────┘
```

### 5.2 Detalle de Tablas

#### Dominio: Usuarios

##### Tabla `users`
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | bigint (PK) | auto_increment | Identificador único |
| `email` | varchar | unique, not null | Email del usuario |
| `password_hash` | varchar | not null | Contraseña hasheada (bcrypt) |
| `name` | varchar(100) | not null | Nombre mostrado |
| `avatar` | varchar(255) | nullable | Ruta pública del avatar (`/storage/avatars/…`) |
| `bio` | text | nullable | Biografía |
| `role` | enum | default: `user` | `user`, `premium`, `educator`, `admin`. Hoy solo `user` y `admin` tienen efecto |
| `email_verified_at` | timestamp | nullable | Se rellena al registrarse |
| `privacy_accepted_at` | timestamp | nullable | Cuándo aceptó la Política de Privacidad y los Términos (RGPD art. 7.1) |
| `privacy_policy_version` | varchar(20) | nullable | Versión aceptada (`AuthController::PRIVACY_POLICY_VERSION`) |
| `last_login` | timestamp | nullable | Último inicio de sesión |
| `suspended_at` | timestamp | nullable | Si no es nulo, la cuenta está suspendida |
| `remember_token` | varchar(100) | nullable | — |
| `created_at` / `updated_at` | timestamp | — | — |

##### Tabla `user_points`
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `user_id` | bigint (FK→users) | unique, cascade delete | Relación 1:1 |
| `points` | int | default: 0 | Puntos acumulados |
| `level` | int | default: 1 | Nivel |
| `experience` | int | default: 0 | Experiencia |
| `streak_days` | int | default: 0 | Días consecutivos |
| `last_activity_date` | date | nullable | Última actividad |

> Tabla creada para la gamificación futura; no se actualiza todavía.

##### Tabla `user_stats`
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `user_id` | bigint (FK→users) | unique, cascade delete | Relación 1:1 |
| `tests_created` | unsigned int | default: 0 | Tests creados |
| `tests_completed` | unsigned int | default: 0 | Exámenes completados (no cuenta repasos) |
| `tests_passed` | unsigned int | default: 0 | Exámenes aprobados |
| `average_score` | decimal(5,2) | default: 0 | Nota media de exámenes |
| `total_study_time`, `perfect_scores`, `current_streak`, `longest_streak`, `total_points`, `badges_earned`, `followers_count`, `following_count` | unsigned int / int | default: 0 | Reservados. La racha real se calcula al vuelo (ver [§8.4](#84-modo-repaso-y-mi-progreso)) |

---

#### Dominio: Tests y Categorías

##### Tabla `tests`
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | bigint (PK) | auto_increment | — |
| `user_id` | bigint (FK→users) | **nullable**, `nullOnDelete`, indexed | Autor. `NULL` = autor eliminado (test anonimizado) |
| `title` | varchar(255) | not null | Título |
| `description` | text | nullable | Descripción |
| `is_public` | boolean | default: true, indexed | Visible en el catálogo |
| `is_featured` | boolean | default: false | Destacado en la Home (lo gestiona un admin) |
| `featured_at` | timestamp | nullable | Orden de los destacados (más reciente primero) |
| `password` | varchar(255) | nullable, oculto en JSON | **Hash** de la contraseña del test. El API expone solo `has_password` |
| `time_limit` | unsigned int | nullable | Límite de tiempo (segundos; el editor lo envía en minutos) |
| `max_attempts` | int | default: -1 | Intentos de examen máximos (-1 = ilimitados) |
| `pass_score` | int | default: 70 | Nota mínima para aprobar (%) |
| `wrong_penalty` | decimal(5,4) | default: 0 | Fracción de los puntos que resta un fallo (0, 0.25, 0.3333, 0.5) |
| `shuffle_questions` / `shuffle_answers` | boolean | default: false | Barajar preguntas / opciones |
| `show_correct_answers` / `show_explanations` | boolean | default: true | Qué se muestra en resultados |
| `display_mode` | enum | default: `paged` | `paged` (una pregunta por pantalla) o `all` (todas en una página) |
| `difficulty` | enum | default: `medium`, indexed | `easy`, `medium`, `hard` |
| `status` | enum | default: `draft`, indexed | `draft`, `published`, `archived` |
| `views_count` / `attempts_count` | unsigned int | default: 0 | Contadores |
| `average_score` | decimal(5,2) | nullable | Nota media |
| `published_at` | timestamp | nullable | Fecha de publicación |

##### Tabla `categories`
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | bigint (PK) | auto_increment | — |
| `name` | varchar(100) | unique | Nombre |
| `slug` | varchar(100) | unique | Slug generado (`historia`, `historia-2`…) |
| `description` | text | nullable | Descripción (máx. 500 en el admin) |
| `icon` | varchar(50) | nullable | Clase Font Awesome validada (`fa-solid fa-code`) |
| `color` | varchar(7) | nullable | Color hex (`#3B82F6`) |
| `parent_id` | bigint (FK→categories) | nullable, cascade delete | Categoría padre |
| `tests_count` | unsigned int | default: 0 | Contador de tests |

##### Tabla `test_categories` (pivote)
| Columna | Tipo | Restricciones |
|---|---|---|
| `test_id` | bigint (FK→tests) | PK compuesta, cascade delete |
| `category_id` | bigint (FK→categories) | PK compuesta, cascade delete |

---

#### Dominio: Preguntas

##### Tabla `questions`
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | bigint (PK) | auto_increment | — |
| `test_id` | bigint (FK→tests) | cascade delete, indexed | Test al que pertenece |
| `type` | enum | indexed | `multiple_choice`, `multiple_select`, `true_false`, `short_answer`, `fill_blank`, `order_items`, `numeric` |
| `content` | text | not null | Enunciado (en `fill_blank`, los huecos se marcan con `___`) |
| `explanation` | text | nullable | Explicación de la respuesta |
| `points` | int | default: 1 | Puntos que vale |
| `numeric_answer` | decimal(20,6) | nullable | Respuesta de las preguntas `numeric` |
| `numeric_tolerance` | decimal(20,6) | nullable | Margen aceptado (±) |
| `order_index` | int | default: 0 | Orden dentro del test |
| `image_url` / `audio_url` / `video_url` | varchar(255) | nullable | Reservados para multimedia |
| `time_limit` | unsigned int | nullable | Reservado |

##### Tabla `question_options`
Usada por `multiple_choice`, `multiple_select`, `true_false` (Verdadero/Falso) y `short_answer` (respuesta aceptada).

| Columna | Tipo | Descripción |
|---|---|---|
| `question_id` | bigint (FK→questions, cascade) | — |
| `content` | text | Texto de la opción |
| `is_correct` | boolean | Si es correcta |
| `order_index` | int | Orden |
| `image_url` | varchar(255) nullable | Reservado |

##### Tabla `question_blanks` (`fill_blank`)
| Columna | Tipo | Descripción |
|---|---|---|
| `question_id` | bigint (FK, cascade) | — |
| `blank_index` | int | Índice del hueco (0…n) |
| `correct_answers` | json | Respuestas válidas para ese hueco |
| `case_sensitive` | boolean (default false) | Si distingue mayúsculas |

##### Tabla `question_order_items` (`order_items`)
| Columna | Tipo | Descripción |
|---|---|---|
| `question_id` | bigint (FK, cascade) | — |
| `content` | text | Texto del elemento |
| `correct_order` | int | Posición correcta |

##### Tabla `question_stats`
1:1 con la pregunta: `times_answered`, `times_correct`, `times_incorrect`, `difficulty_score`, `average_time`.

---

#### Dominio: Intentos y Respuestas

##### Tabla `test_attempts`
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | bigint (PK) | auto_increment | — |
| `user_id` | bigint (FK→users) | cascade delete, indexed | Usuario |
| `test_id` | bigint (FK→tests) | cascade delete, indexed | Test |
| `mode` | enum | default: `exam` | `exam` o `review`. Índice `(user_id, test_id, mode)` |
| `score` | decimal(5,2) | default: 0 | Nota (0–100) |
| `points_earned` | decimal(8,2) | default: 0 | Puntos obtenidos (admite parciales y penalizaciones) |
| `total_points` | int | default: 0 | Puntos posibles |
| `correct_answers` / `incorrect_answers` / `unanswered` | int | default: 0 | Recuento (parciales cuentan como incorrectas) |
| `time_spent` | unsigned int | default: 0 | Segundos empleados |
| `is_completed` | boolean | default: false, indexed | — |
| `passed` | boolean | default: false | `score >= pass_score` |
| `started_at` / `completed_at` | timestamp | nullable | — |
| `ip_address` / `user_agent` | varchar / text | nullable | **Ya no se guardan** (minimización RGPD) |

> Los repasos no gastan intentos ni cuentan en medias ni estadísticas: usa el scope `TestAttempt::completedExams()`.

##### Tabla `question_responses`
| Columna | Tipo | Descripción |
|---|---|---|
| `attempt_id` | bigint (FK→test_attempts, cascade) | Intento |
| `question_id` | bigint (FK→questions, cascade) | Pregunta |
| `given_answer` | json, nullable | Respuesta dada (formato según tipo, ver [§8.2](#82-tipos-de-pregunta-y-formatos)) |
| `is_correct` | boolean | Solo `true` con 100 % de acierto |
| `points_earned` | decimal(8,2) | Puntos (pueden ser parciales o negativos por penalización) |
| `display_order` | int | Posición en la que se mostró la pregunta |
| `time_spent` | unsigned int | Reservado |

---

#### Dominio: Administración

##### Tabla `admin_audit_logs`
| Columna | Tipo | Descripción |
|---|---|---|
| `id` | bigint (PK) | — |
| `admin_id` | bigint (FK→users), nullable, `nullOnDelete` | Quién hizo la acción |
| `action` | varchar(50) | `test.status`, `test.featured`, `test.unfeatured`, `test.deleted`, `user.role`, `user.suspended`, `user.reactivated`, `category.created`, `category.updated`, `category.deleted` |
| `target_type` | varchar(30) | `test`, `user`, `category` |
| `target_id` | unsigned bigint, nullable | Id del objeto afectado |
| `details` | json, nullable | Cambios (`from`/`to`…). **Sin datos personales** |
| `created_at` | timestamp | Índice |

---

#### Dominio: Comunidad y Grupos (sin API todavía)

| Tabla | Columnas clave |
|---|---|
| `test_stats` | 1:1 con test: `views_count`, `attempts_count`, `completions_count`, `average_score`, `pass_rate`, `rating_avg`, `ratings_count`, `comments_count` |
| `comments` | `user_id`, `test_id`, `parent_id` (respuestas anidadas), `content`, `is_edited`, `is_hidden` |
| `ratings` | `user_id` + `test_id` (unique), `rating` 1–5 |
| `follows` | `follower_id` + `following_id` (PK compuesta) |
| `groups` | `owner_id`, `name`, `description`, `avatar`, `is_private`, `join_code` (unique), `max_members` |
| `group_members` | `group_id` + `user_id` (unique), `role` (`owner`, `admin`, `member`) |
| `group_tests` | `group_id` + `test_id` (unique), `added_by` |

#### Dominio: Sistema

| Tabla | Descripción |
|---|---|
| `sessions` | Sesiones de Laravel (**se usan para la autenticación SPA**). Limpieza horaria |
| `password_reset_tokens` / `password_resets` | Tokens de restablecimiento. Limpieza diaria |
| `personal_access_tokens` | Tokens de Sanctum (ya no se emiten; se revocaron al migrar a cookie) |
| `cache` / `cache_locks` | Caché de Laravel |
| `point_history`, `settings`, `activity_logs`, `notifications`, `reports` | Reservadas (ver [§13](#13-funcionalidades-planificadas-no-implementadas)) |

### 5.3 Migraciones

| Migración | Contenido |
|---|---|
| `2024_01_01_000001` … `000007` | Esquema base: usuarios, puntos/estadísticas, tests y categorías, preguntas, intentos, grupos y sistema |
| `2026_02_13_142657_create_personal_access_tokens_table` | Tokens de Sanctum |
| `2026_04_08_120000_add_display_order_to_question_responses_table` | Orden en que se mostró cada pregunta |
| `2026_11_15_120000_add_display_mode_to_tests_table` | `tests.display_mode` (`paged` / `all`) |
| `2026_11_15_130000_gdpr_compliance` | Consentimiento en `users`, `tests.user_id` nullable con `nullOnDelete`, borrado de IP/user-agent existentes y revocación de los tokens Bearer |
| `2026_11_16_100000_question_types_and_scoring` | Nuevos tipos (`multiple_select`; se eliminan `drag_drop` e `image_hotspot`), `numeric_answer/tolerance`, `tests.wrong_penalty`, puntos decimales |
| `2026_11_16_110000_add_mode_to_test_attempts` | `test_attempts.mode` (`exam` / `review`) |
| `2026_11_16_120000_admin_panel` | `users.suspended_at`, `tests.featured_at`, tabla `admin_audit_logs` |

---

## 6. Backend — Laravel

### 6.1 Modelos y Relaciones Eloquent

#### User

```
User
├── hasMany → Test, TestAttempt, Comment, Rating
├── hasOne  → UserPoint (points), UserStat (stats)
├── belongsToMany → User (followers / following, vía 'follows')
└── belongsToMany → Group (vía 'group_members')

Métodos: isAdmin(), isSuspended(), getAuthPassword() → password_hash
```

> La columna de contraseña se llama `password_hash`, por eso `getAuthPassword()` está sobrescrito.

#### Test

```
Test
├── belongsTo    → User (autor; puede ser null)
├── hasMany      → Question, TestAttempt, Comment, Rating
├── belongsToMany → Category (vía 'test_categories')
└── hasOne       → TestStat

`password` está en $hidden; hasPassword() indica si está protegido.
```

#### Question

```
Question
├── belongsTo → Test
├── hasMany   → QuestionOption (options), QuestionBlank (blanks),
│               QuestionOrderItem (orderItems), QuestionResponse
└── hasOne    → QuestionStat
```

#### TestAttempt

```
TestAttempt
├── belongsTo → User, Test
└── hasMany   → QuestionResponse (responses)

Constantes: MODE_EXAM = 'exam', MODE_REVIEW = 'review'
Scope: completedExams()  → is_completed = true AND mode = 'exam'
```

#### Modelos auxiliares

| Modelo | Relación principal |
|---|---|
| `QuestionOption` | `belongsTo` → Question |
| `QuestionBlank` | `belongsTo` → Question (cast `correct_answers` → array) |
| `QuestionOrderItem` | `belongsTo` → Question |
| `QuestionStat` | `belongsTo` → Question |
| `QuestionResponse` | `belongsTo` → TestAttempt, Question (cast `given_answer` → array) |
| `Category` | `belongsTo` parent · `hasMany` children · `belongsToMany` Test |
| `AdminAuditLog` | `belongsTo` → User (admin). Helper estático `record($admin, $action, $type, $id, $details)` |
| `Comment`, `Rating`, `Group`, `UserPoint`, `UserStat`, `TestStat` | Relaciones definidas, sin API todavía |

---

### 6.2 API REST — Endpoints

Todos bajo `/api`, con respuestas JSON. La autenticación es por **cookie de sesión** (ver [§6.3](#63-autenticación-sanctum-spa)).

#### Públicos

| Método | Endpoint | Controlador | Descripción |
|---|---|---|---|
| `POST` | `/register` | `AuthController@register` | Registro. `throttle:auth` |
| `POST` | `/login` | `AuthController@login` | Inicio de sesión. `throttle:auth` |
| `GET` | `/tests` | `TestController@index` | Catálogo público paginado con filtros |
| `GET` | `/tests/stats` | `TestController@stats` | Totales para la Home |
| `GET` | `/tests/featured` | `TestController@featured` | Destacados (públicos y publicados), el más reciente primero |
| `GET` | `/tests/{id}` | `TestController@show` | Detalle de un test |
| `GET` | `/categories` | `TestController@categories` | Categorías por nombre |
| `GET` | `/profile/{id}` | `ProfileController@show` | Perfil público (datos mínimos) |

#### Autenticados (`auth:sanctum`)

| Método | Endpoint | Controlador | Descripción |
|---|---|---|---|
| `POST` | `/logout` | `AuthController@logout` | Cierra e invalida la sesión |
| `GET` | `/me` | `AuthController@me` | Usuario actual |
| `POST` | `/reset-password` | `AuthController@resetPassword` | Cambio de contraseña (requiere la actual). `throttle:sensitive` |
| `GET` | `/account/export` | `AccountController@export` | Descarga JSON con todos tus datos. `throttle:sensitive` |
| `DELETE` | `/account` | `AccountController@destroy` | Borra la cuenta (requiere contraseña). `throttle:sensitive` |
| `POST` | `/profile/avatar` | `ProfileController@updateAvatar` | Sube avatar (jpeg/png/gif/webp, máx. 2 MB) |
| `DELETE` | `/profile/avatar` | `ProfileController@deleteAvatar` | Elimina el avatar |
| `POST` | `/tests` | `TestController@store` | Crea un test con sus preguntas |
| `GET` | `/tests/{id}/edit` | `TestController@edit` | Datos completos para el editor (autor o admin) |
| `PUT` | `/tests/{id}` | `TestController@update` | Actualiza test y sincroniza preguntas (autor o admin) |
| `DELETE` | `/tests/{id}` | `TestController@destroy` | Borra el test (autor o admin) |
| `GET` | `/tests/{id}/questions` | `TestController@questions` | Preguntas para responder, sin soluciones. `?mode=review` → solo las pendientes, sin límite de tiempo. `423` si está bloqueado |
| `POST` | `/tests/{id}/unlock` | `TestController@unlock` | Desbloquea un test con contraseña para la sesión. `throttle:unlock` |
| `POST` | `/attempts/submit` | `AttemptController@submit` | Envía y corrige un intento (`mode`: `exam` / `review`) |
| `GET` | `/attempts/{attemptId}/results` | `AttemptController@results` | Resultados de un intento propio |
| `GET` | `/dashboard?tz=Europe/Madrid` | `DashboardController@show` | Mi progreso (ver [§8.4](#84-modo-repaso-y-mi-progreso)) |
| `GET` | `/dashboard/attempts?page=&mode=` | `DashboardController@attempts` | Historial propio, 15 por página, filtrable por `exam`/`review` |

#### Administración (`auth:sanctum` + `admin`)

| Método | Endpoint | Descripción |
|---|---|---|
| `GET` | `/admin/metrics` | Totales de usuarios/tests/intentos y serie diaria de 30 días (intentos y altas) |
| `GET` | `/admin/tests?search=&…` | Listado paginado de todos los tests |
| `PATCH` | `/admin/tests/{id}` | `is_featured` y/o `status`. Solo se destacan tests públicos y publicados; despublicar quita el destacado |
| `DELETE` | `/admin/tests/{id}` | Borra el test y ajusta los contadores del autor |
| `GET` | `/admin/users?search=&…` | Listado paginado de usuarios |
| `PATCH` | `/admin/users/{id}` | `role` (`user`/`admin`) y/o `suspended`. No puedes modificarte a ti mismo ni suspender a un admin |
| `GET` · `POST` | `/admin/categories` | Listar / crear categoría |
| `PUT` · `DELETE` | `/admin/categories/{id}` | Editar / borrar categoría |
| `GET` | `/admin/audit` | Registro de acciones de administración |

#### Códigos de error habituales

| Código | Cuándo |
|---|---|
| `401` | Sin sesión |
| `403` | Sin permisos de admin, o cuenta suspendida (`{ "suspended": true }`) |
| `404` | Test inexistente o no visible para ti |
| `419` | Token CSRF caducado (Axios lo renueva y reintenta una vez) |
| `422` | Validación, intentos agotados, sin preguntas pendientes de repasar |
| `423` | Test protegido con contraseña y no desbloqueado |
| `429` | Rate limit superado |

#### Ruta web (catch-all)

```php
Route::get('/{any?}', fn () => view('app'))->where('any', '.*');
```

Además, Laravel expone `GET /up` (health check) y Sanctum `GET /sanctum/csrf-cookie`.

---

### 6.3 Autenticación (Sanctum SPA)

Desde la rama `Redesign`, TestMaster **ya no usa tokens Bearer en `localStorage`**. Usa el modo SPA de Sanctum: la sesión vive en una cookie **httpOnly** (inaccesible desde JavaScript, lo que neutraliza el robo de sesión por XSS) y las peticiones mutantes llevan un token **CSRF**.

```
1. CSRF
   Cliente → GET /sanctum/csrf-cookie
   Servidor → Set-Cookie: XSRF-TOKEN (legible) + laravel_session (httpOnly)

2. LOGIN / REGISTRO
   Cliente → POST /api/login  (cabecera X-XSRF-TOKEN, la añade Axios)
   Servidor → Auth::guard('web')->login($user) + session()->regenerate()
   Servidor → { user }

3. PETICIONES
   El navegador envía la cookie de sesión automáticamente (withCredentials).
   Middleware 'auth:sanctum' → $request->user()

4. LOGOUT
   Servidor → logout + session()->invalidate() + regenerateToken()

5. ERRORES
   401 → el frontend limpia el usuario y redirige a /login?redirect=…
   419 → se pide una nueva cookie CSRF y se reintenta una vez
   403 suspended → aviso y cierre de sesión
```

Configuración relevante: `bootstrap/app.php` (`statefulApi()`, `trustProxies('*')`), `config/sanctum.php` (dominios *stateful*, incluye `APP_DOMAIN`) y las variables `SESSION_*` de [§4.2](#42-variables-de-entorno).

---

### 6.4 Middleware y Rate Limiting

| Middleware | Dónde | Qué hace |
|---|---|---|
| `statefulApi()` | Grupo `api` | Habilita la autenticación por cookie y CSRF de Sanctum |
| `throttle:api` | Grupo `api` | 120 peticiones/min por usuario o IP |
| `EnsureAccountActive` | Grupo `api` | Si el usuario está suspendido: cierra la sesión y responde `403` |
| `auth:sanctum` | Rutas protegidas | Requiere sesión |
| `admin` (`EnsureAdmin`) | `/api/admin/*` | Requiere `role = admin`, si no `403` |

Limitadores definidos en `AppServiceProvider`:

| Nombre | Límite | Clave |
|---|---|---|
| `api` | 120/min | usuario o IP |
| `auth` | 5/min **y** 20/min | email+IP **y** IP |
| `unlock` | 5/min | usuario (o IP) + test |
| `sensitive` | 5/min | usuario (o IP) |

---

### 6.5 Controladores en Detalle

#### AuthController

##### `register` → `POST /api/register`

```
name                  → required, string, max:100
email                 → required, email, max:255, unique
password              → required, confirmed, min:8
password_confirmation → required
accept_terms          → accepted   (≥ 14 años + Política de Privacidad y Términos)
```

En una transacción crea el usuario (con `privacy_accepted_at` y `privacy_policy_version`), `user_stats` y `user_points`; inicia sesión y regenera el id de sesión. Respuesta **201** `{ user, message }`.

##### `login` → `POST /api/login`

Verifica la contraseña. Si es incorrecta → **422** `"Email o contraseña incorrectos."`. Si es correcta pero la cuenta está suspendida → **403** con `suspended: true` (solo se revela a quien conoce la contraseña). Si todo va bien, inicia sesión, regenera la sesión, actualiza `last_login` y devuelve `{ user }`.

##### `logout` → `POST /api/logout` · `me` → `GET /api/me`

`logout` invalida la sesión y regenera el token CSRF. `me` devuelve el usuario con `stats` y `points`.

##### `resetPassword` → `POST /api/reset-password` (auth)

Cambio de contraseña con sesión iniciada: requiere `current_password`, `password` y `password_confirmation` (mín. 8). Tras cambiarla regenera la sesión actual, **cierra el resto de sesiones** del usuario y revoca cualquier token.

---

#### AccountController (RGPD)

- **`export`** → `GET /api/account/export`: JSON descargable (`testmaster-mis-datos.json`) con cuenta, consentimiento, estadísticas, puntos, tests creados (con preguntas y opciones) e intentos con respuestas.
- **`destroy`** → `DELETE /api/account` `{ password, delete_tests? }`: verifica la contraseña y, en una transacción, borra borradores y tests privados, conserva **anonimizados** los tests públicos publicados (salvo `delete_tests: true`), borra el avatar, sesiones, tokens y tokens de reseteo, y elimina el usuario (cascada sobre estadísticas, puntos, intentos y respuestas).

---

#### TestController

##### `index` → `GET /api/tests`

| Parámetro | Descripción |
|---|---|
| `category_id` | Filtrar por categoría |
| `difficulty` | `easy`, `medium`, `hard` |
| `search` | Búsqueda en título y descripción |
| `sort` | `recent` (defecto), `attempts`, `longest`, `shortest` |
| `per_page` | Elementos por página (defecto 20) |

Cada test incluye `user` (id, name), `categories`, `questions_count`, `completed_attempts_count` y `has_password`.

##### `show` → `GET /api/tests/{id}`

Devuelve el test con `avg_score`, `has_password` y, si hay sesión, tus intentos, último intento, historial de notas propio y número de preguntas pendientes de repasar. Incrementa `views_count`. Los tests privados o en borrador solo los ven su autor y los admins.

##### `store` → `POST /api/tests` · `update` → `PUT /api/tests/{id}`

```json
{
  "title": "Test de Química",
  "description": "Conceptos básicos",
  "category_id": 1,
  "difficulty": "medium",
  "time_limit": 30,
  "max_attempts": 3,
  "pass_score": 70,
  "wrong_penalty": 0.25,
  "display_mode": "paged",
  "is_public": true,
  "password": null,
  "shuffle_questions": false,
  "shuffle_answers": false,
  "show_correct_answers": true,
  "show_explanations": true,
  "status": "published",
  "questions": [
    { "type": "multiple_choice", "content": "¿Fórmula del agua?", "points": 1,
      "options": [ { "content": "H2O", "is_correct": true }, { "content": "CO2", "is_correct": false } ] },
    { "type": "multiple_select", "content": "Gases nobles", "points": 2,
      "options": [ { "content": "He", "is_correct": true }, { "content": "Ne", "is_correct": true }, { "content": "O", "is_correct": false } ] },
    { "type": "true_false", "content": "El agua hierve a 100 ºC a nivel del mar", "correct": "true" },
    { "type": "short_answer", "content": "Símbolo del sodio", "correct_answer": "Na" },
    { "type": "fill_blank", "content": "El ___ es el gas más abundante del aire", "case_sensitive": false,
      "blanks": [ { "answers": ["nitrógeno", "nitrogeno"] } ] },
    { "type": "order_items", "content": "Ordena de menor a mayor masa", "items": ["H", "He", "Li"] },
    { "type": "numeric", "content": "pH del agua pura", "numeric_answer": 7, "numeric_tolerance": 0.1 }
  ]
}
```

Notas:
- `time_limit` llega en **minutos** y se guarda en **segundos**.
- `password`: mín. 4 caracteres, se guarda con hash. En `update`, `null` o vacío la quita; si no se envía, se conserva.
- Cada tipo valida sus datos (`QuestionType::validate`); el error se devuelve como `422` indicando la pregunta.
- En `update`, las preguntas se **sincronizan** por `id` (las que no llegan se borran) y las filas auxiliares por posición, conservando los ids a los que apuntan las respuestas antiguas.

##### `questions` → `GET /api/tests/{id}/questions`

Devuelve `test` (`id`, `title`, `mode`, `time_limit` — `null` en repaso —, `display_mode`, `shuffle_questions`, `wrong_penalty`) y `questions`. Cada pregunta se construye a mano con lo que el tipo decide mostrar (`forTaking`): **nunca** incluye `is_correct`, la respuesta numérica, los huecos correctos ni la explicación. Respeta `shuffle_questions` y `shuffle_answers` (los elementos de `order_items` siempre se barajan).

##### `unlock` → `POST /api/tests/{id}/unlock` `{ password }`

Si la contraseña es correcta, marca el test como desbloqueado en la sesión (ver [§8.6](#86-tests-con-contraseña)).

---

#### AttemptController

##### `submit` → `POST /api/attempts/submit`

```json
{
  "test_id": 1,
  "started_at": 1763200000,
  "mode": "exam",
  "question_order": [3, 1, 2],
  "answers": {
    "1": "12",
    "2": ["15", "16"],
    "3": "true",
    "4": "Na",
    "5": ["nitrógeno"],
    "6": ["31", "29", "30"],
    "7": "7,05"
  }
}
```

Proceso:
1. Comprueba visibilidad (`404`), bloqueo por contraseña (`423`) y que `started_at` sea válido (`422`).
2. En examen, comprueba `max_attempts` (`422 "Has alcanzado el número máximo de intentos"`). En repaso, solo se corrigen las preguntas pendientes (`422` si no hay ninguna).
3. Corrige con `AttemptScorer` ([§8.3](#83-corrección-y-puntuación)) y guarda intento y respuestas en una transacción.
4. Solo en examen: actualiza `user_stats` y `test_stats`.

Respuesta: `{ attempt_id, score, passed, mode, message }`.

##### `results` → `GET /api/attempts/{attemptId}/results`

Solo para el dueño del intento. Devuelve:
- `attempt` con su test.
- `responses`: por pregunta, `status` (`ok`, `partial`, `bad`, `none`), puntos, `given_answer_label` y, si el test lo permite, `correct_answer_label` y la explicación.
- `comparison` (solo exámenes): media del test, número de intentos totales, tu mejor nota anterior, número de intento e intentos restantes.
- `pending_review_count`: preguntas de ese test pendientes de repasar.

---

#### ProfileController

- **`show`**: perfil público con datos mínimos (el email solo en tu propio perfil), estadísticas, tests públicos creados, últimos 20 intentos completados en tests públicos y actividad reciente (máx. 30).
- **`updateAvatar`**: valida imagen real (jpeg/png/gif/webp, máx. 2 MB), **ignora la extensión del cliente** y la deriva del tipo MIME; guarda en `storage/app/public/avatars` y borra el anterior.
- **`deleteAvatar`**: elimina el archivo y la referencia.

#### DashboardController

- **`show`** (`?tz=`): `summary` (exámenes, aprobados, nota media, repasos, tiempo de estudio), `activity` (racha y calendario de 182 días), `mastery` (dominio por categoría), `pending_reviews` (tests con fallos pendientes) y `timezone`. Una zona horaria inválida cae a la de la app.
- **`attempts`** (`?page=&mode=`): historial propio paginado (15).

#### Admin\AdminController y Admin\AdminCategoryController

Ver [§8.7](#87-administración). Toda acción que cambia datos se registra con `AdminAuditLog::record()` dentro de la misma transacción.

---

### 6.6 Capa de Dominio

| Clase | Responsabilidad |
|---|---|
| `App\Questions\QuestionType` | Interfaz de un tipo de pregunta: `validate`, `attributes`, `store`, `forTaking`, `grade`, `answerLabel`, `correctLabel` |
| `App\Questions\QuestionTypes` | Registro tipo → clase, `keys()`, `get()`, `isAnswered()` y `RELATIONS` a precargar (evita N+1) |
| `App\Questions\Types\*` | Una clase por tipo. `BaseType` (normalización de texto, sincronización por posición) y `OptionsType` (base de los tipos con opciones) |
| `App\Questions\AttemptScorer` / `AttemptScore` | Corrige un intento completo y devuelve totales y respuestas |
| `App\Questions\ReviewQueue` | Preguntas pendientes de repasar por usuario y test |
| `App\Progress\StudyActivity` | Días de estudio, racha actual y más larga en la zona horaria del usuario |
| `App\Progress\CategoryMastery` | Dominio por categoría según la última respuesta |
| `App\Support\TestAccess` | `canView`, `canManage`, `isLocked`, `unlock` |

---

## 7. Frontend — Vue.js

### 7.1 Estructura de Archivos

```
Vue/
├── App.vue                     ← Raíz: cabecera, pie, <router-view>, toasts
├── api/axios.js                ← Axios con cookies + XSRF y manejadores 401/419/403
├── assets/
│   ├── tokens.css              ← Design tokens (colores, superficies) claro/oscuro
│   ├── styles.css              ← Estilos globales
│   └── images/
├── components/
│   ├── AppHeader.vue / AppFooter.vue
│   ├── base/                   ← BaseAlert, BaseBadge, BaseButton, BaseInput,
│   │                             BaseLoader, BaseModal, BaseSelect, ToastStack
│   ├── auth/AuthShell.vue      ← Marco común de login y registro
│   ├── home/                   ← HomeHero, FeaturedTestCard
│   ├── test/                   ← CatalogCard, TestPreview
│   ├── question/AnswerInput.vue← Entrada de respuesta para los 7 tipos
│   ├── charts/                 ← ScoreChart, ColumnChart, ActivityHeatmap (SVG propio)
│   ├── admin/                  ← AdminOverview, AdminTests, AdminUsers,
│   │                             AdminCategories, AdminAudit, AdminPager
│   ├── legal/LegalDocument.vue ← Plantilla de Privacidad y Términos
│   └── user/UserAvatar.vue
├── composables/
│   ├── useTheme.js             ← Tema claro/oscuro/sistema
│   └── useToast.js             ← Notificaciones
├── pages/                      ← Ver 7.5
├── router/index.js
├── stores/auth.js
└── utils/
    ├── editorDraft.js          ← Borrador local del editor
    ├── redirect.js             ← Validación del parámetro ?redirect
    └── tests.js                ← Utilidades de tests (etiquetas, formato…)
```

El punto de entrada es `resources/js/app.js` (monta Vue, Pinia y el router, y registra los manejadores de 401 y cuenta suspendida). `public/js/theme.js` aplica el tema antes de pintar para evitar el parpadeo (archivo externo para cumplir la CSP `script-src 'self'`).

### 7.2 Router y Navegación

| Ruta | Componente | Meta | Descripción |
|---|---|---|---|
| `/` | `Home` | — | Portada y catálogo |
| `/login` · `/register` | `Login` · `Register` | — | Acceso |
| `/test/:id` | `ViewTest` | — | Detalle del test |
| `/test/:id/take` | `TakeTest` | `requiresAuth`, `hideChrome` | Realizar el examen |
| `/test/:id/review` | `TakeTest` | `requiresAuth`, `hideChrome` | Repasar fallos |
| `/results/:attemptId` | `Results` | `requiresAuth` | Resultados |
| `/create-test` | `CreateTest` | `requiresAuth` | Crear test |
| `/test/:id/edit` | `CreateTest` | `requiresAuth` | Editar test |
| `/profile/:id` | `Profile` | — | Perfil |
| `/dashboard` | `Dashboard` | `requiresAuth` | Mi progreso |
| `/admin` | `Admin` | `requiresAuth`, `requiresAdmin` | Panel de administración |
| `/help` · `/privacy` · `/terms` · `/contact` | `Help` · `Privacy` · `Terms` · `Contact` | — | Ayuda y legales |
| `/:pathMatch(.*)*` | `NotFound` | — | 404 |

`hideChrome` oculta cabecera y pie para que el examen ocupe toda la pantalla.

```javascript
router.beforeEach(async (to) => {
    const authStore = useAuthStore();
    await authStore.init();                       // GET /api/me una sola vez
    if (to.meta.requiresAuth && !authStore.isLoggedIn) → login?redirect=<ruta>
    else if (to.meta.requiresAdmin && !authStore.isAdmin) → NotFound
});
```

> El guard de admin es solo de UX: el servidor vuelve a comprobarlo en cada petición.

### 7.3 Store (Pinia)

`Vue/stores/auth.js`. El usuario vive **solo en memoria**; al cargar se borran restos del antiguo sistema (`token`/`user` en `localStorage`).

| Estado / getter | Descripción |
|---|---|
| `user`, `initialized` | Usuario actual y si ya se consultó `/me` |
| `isLoggedIn`, `isAdmin`, `userId`, `userName`, `userRole` | Derivados |

| Acción | Descripción |
|---|---|
| `init()` | Llama a `fetchUser()` una vez |
| `login(email, password)` | CSRF + `POST /login` |
| `register(name, email, password, password_confirmation, accept_terms)` | CSRF + `POST /register` |
| `logout()` | `POST /logout`, borra los borradores locales y el usuario |
| `fetchUser()` | `GET /me` (sin redirigir si falla) |
| `exportData()` | Descarga el JSON de `GET /account/export` |
| `deleteAccount(password, deleteTests)` | `DELETE /account` y limpia los borradores |

### 7.4 Capa API (Axios)

```javascript
const api = axios.create({
    baseURL: '/api',
    withCredentials: true,      // envía la cookie de sesión
    withXSRFToken: true,        // reenvía XSRF-TOKEN como X-XSRF-TOKEN
    headers: { 'Content-Type': 'application/json', Accept: 'application/json',
               'X-Requested-With': 'XMLHttpRequest' },
});
```

Interceptor de respuestas:
- **419** → `getCsrfCookie()` y reintenta la petición una vez.
- **401** → llama al manejador registrado (limpia el usuario y redirige a login), salvo con `skipAuthRedirect`.
- **403 + `suspended`** → muestra el aviso y cierra la sesión.

### 7.5 Páginas

| Página | Funcionalidad |
|---|---|
| **Home** | Hero, tests destacados, catálogo con búsqueda, filtros de categoría/dificultad, orden (recientes, más realizados, más largos, más cortos) y paginación. El estado de los filtros vive en la query de la URL |
| **Login / Register** | Dentro de `AuthShell`. El registro exige la casilla de edad y aceptación de los textos legales. Tras entrar, vuelve a `?redirect` si es una ruta interna válida |
| **ViewTest** | Información del test, configuración, estadísticas, gráfica de tus notas (`ScoreChart`), intentos restantes, botón de **repasar fallos** con el número de pendientes y formulario de **desbloqueo** si tiene contraseña. El autor/admin ve accesos a editar y borrar |
| **TakeTest** | Examen o repaso. Modo paginado (navegación con teclado ←/→) o todo en una página, temporizador con **envío automático** al llegar a 0, aviso `beforeunload` si hay respuestas sin enviar y `AnswerInput` para cada tipo |
| **Results** | Nota, aprobado/suspenso, desglose por pregunta (correcta / parcial / incorrecta / en blanco), respuesta correcta y explicación si el test lo permite, comparativa con la media y tu mejor intento, y acceso a repasar fallos |
| **CreateTest** | Editor de creación y edición: datos básicos, configuración (tiempo, intentos, nota de corte, penalización, modo de presentación, contraseña, visibilidad…), constructor de preguntas de los 7 tipos con **reordenación por arrastre** y **duplicado**, **vista previa** (`TestPreview`), borrador automático local y aviso al salir con cambios |
| **Dashboard** | Mi progreso: tarjetas de resumen, racha, `ActivityHeatmap`, dominio por categoría, tests con fallos pendientes e historial paginado con filtro examen/repaso |
| **Profile** | Avatar (subir/eliminar), datos, estadísticas, tests creados, completados y actividad. En el perfil propio: cambio de contraseña, **descargar mis datos** y **eliminar cuenta** |
| **Admin** | Pestañas: Resumen (métricas y `ColumnChart`), Tests, Usuarios, Categorías y Registro. Navegable con teclado |
| **Help / Contact** | Preguntas frecuentes y contacto |
| **Privacy / Terms** | Textos legales con `LegalDocument` |
| **NotFound** | 404 |

### 7.6 Componentes

| Componente | Uso |
|---|---|
| `AppHeader` | Logo, navegación (Explorar, Crear, Mi progreso, Admin si procede), menú de usuario y selector de tema; menú móvil |
| `AppFooter` | Enlaces a ayuda, contacto y legales |
| `AnswerInput` | Renderiza la entrada adecuada para cada tipo (radios, checkboxes, V/F, texto, huecos en línea, lista ordenable, número) |
| `TestPreview` | Previsualiza en el editor cómo verá el alumno el test |
| `CatalogCard` / `FeaturedTestCard` | Tarjetas del catálogo y de destacados |
| `ScoreChart`, `ColumnChart`, `ActivityHeatmap` | Gráficas SVG propias, sin librerías externas |
| `Base*` | Botones, inputs, selects, badges, alertas, modales y loader reutilizables |
| `ToastStack` + `useToast` | Notificaciones no intrusivas |

### 7.7 Tema y Sistema de Diseño

- `Vue/assets/tokens.css` define todas las variables (superficies, texto, bordes, acento, estados). Los componentes **nunca** usan colores literales.
- El modo oscuro solo redefine esas variables con `<html data-theme="dark">`.
- `useTheme` guarda la preferencia (`light`, `dark` o la del sistema) en `localStorage` y reacciona a `prefers-color-scheme`.
- Contraste de texto ≥ 4.5:1 en ambos temas.

### Borrador local del editor

`utils/editorDraft.js` guarda una copia del editor en `localStorage` con clave `testmaster:draft:<userId>:<testId|new>`, versión y caducidad de 30 días. Se borran todos al cerrar sesión o eliminar la cuenta (no al caducar la sesión, para no perder trabajo). Si el almacenamiento no está disponible, el editor funciona igual sin copia.

---

## 8. Lógica de Negocio

### 8.1 Creación y Edición de Tests

```
DB::transaction
├── 1. Validar campos del test y de cada pregunta (QuestionType::validate)
├── 2. Crear / actualizar 'tests'
│   ├── time_limit: minutos × 60
│   ├── password: hash (o se conserva / se quita)
│   └── published_at: now() al publicar por primera vez
├── 3. Sincronizar categoría (y contadores tests_count)
├── 4. Sincronizar preguntas
│   ├── Por id: actualizar existentes, crear nuevas, borrar las ausentes
│   ├── order_index secuencial
│   ├── attributes(): columnas propias del tipo (p. ej. numeric_answer)
│   └── store(): opciones / huecos / elementos, sincronizados por posición
└── 5. (Solo al crear) test_stats y user_stats.tests_created
```

### 8.2 Tipos de Pregunta y Formatos

| Tipo | Datos del editor | Lo que recibe el alumno | `answers[id]` | Puntuación |
|---|---|---|---|---|
| `multiple_choice` | `options[{content, is_correct}]`, exactamente 1 correcta | opciones sin `is_correct` | id de opción | Todo o nada |
| `multiple_select` | `options[…]`, ≥ 1 correcta | opciones sin `is_correct` | `[ids]` | (aciertos − errores) / correctas, mín. 0 |
| `true_false` | `correct: 'true'|'false'` | — | `'true'` / `'false'` | Todo o nada |
| `short_answer` | `correct_answer` | — | texto | Todo o nada, sin mayúsculas ni espacios sobrantes |
| `fill_blank` | `___` en el enunciado + `blanks[{answers[]}]`, `case_sensitive` (máx. 20 huecos) | número de huecos | `[texto por hueco]` | huecos acertados / huecos |
| `order_items` | `items[]` en el orden correcto | elementos barajados con id | `[ids en orden]` | elementos en su sitio / elementos |
| `numeric` | `numeric_answer`, `numeric_tolerance` | — | número (coma o punto) | Todo o nada dentro de ± tolerancia |

### 8.3 Corrección y Puntuación

`App\Questions\AttemptScorer`:

```
Para cada pregunta (en el orden mostrado):
├── Sin respuesta (QuestionTypes::isAnswered = false)
│   └── unanswered++, 0 puntos (las preguntas en blanco no penalizan)
└── Con respuesta
    ├── ratio = tipo->grade(pregunta, respuesta)   ∈ [0, 1]
    ├── ratio ≥ 0.9999 → correcta, puntos completos
    ├── 0 < ratio < 1  → parcial: ratio × puntos (cuenta como incorrecta)
    └── ratio = 0      → incorrecta: −wrong_penalty × puntos

score  = max(0, puntos obtenidos) / puntos totales × 100   (2 decimales)
passed = score ≥ pass_score
```

Tras un **examen** se actualizan `user_stats` (`tests_completed`, `tests_passed`, `average_score`) y `test_stats`. Los **repasos** no modifican estadísticas, medias ni intentos gastados.

### 8.4 Modo Repaso y Mi Progreso

**Cola de repaso (`ReviewQueue`)**: para cada pregunta se toma la **última** respuesta del usuario (de examen o de repaso). Si no es 100 % correcta (incorrecta, parcial o en blanco) la pregunta está pendiente; al acertarla una vez sale de la lista. `pendingByTest` solo incluye tests que el usuario aún puede abrir.

**Racha (`StudyActivity`)**:
- Cualquier intento terminado cuenta como actividad (también los repasos).
- Los días se cuentan en la **zona horaria del navegador** (`?tz=`), para que estudiar a las 00:30 no cuente para el día anterior.
- La racha actual sigue viva durante hoy aunque aún no hayas estudiado (se cuenta desde ayer).
- Se calcula a partir de los intentos, no se almacena, así nunca se desincroniza.

**Dominio por categoría (`CategoryMastery`)**: puntos ganados / puntos posibles de las preguntas respondidas, usando la última respuesta a cada una. Las parciales cuentan su parte y las penalizaciones no restan. Se ordena de menor a mayor dominio para señalar lo que más hay que estudiar.

### 8.5 Cómo añadir un tipo de pregunta

1. Crea `app/Questions/Types/MiTipoType.php` que extienda `BaseType` (u `OptionsType` si usa opciones) e implemente `QuestionType`.
2. Regístralo en `QuestionTypes::MAP`.
3. Añade el valor al enum `questions.type` con una migración.
4. Si necesita columnas o tablas propias, añádelas en la migración y en `attributes()` / `store()`.
5. Frontend: añade la entrada en `components/question/AnswerInput.vue`, el bloque del editor en `pages/CreateTest.vue` y la etiqueta en `utils/tests.js`.
6. Añade casos a `tests/Feature/QuestionTypesTest.php`, en especial: que `forTaking` **no revela la solución** y la puntuación parcial.

### 8.6 Tests con Contraseña

`App\Support\TestAccess`:

- Un test con contraseña aparece en el catálogo (título y descripción), pero para obtener las preguntas o enviar un intento hay que desbloquearlo (`423` si no).
- `POST /tests/{id}/unlock` guarda en la **sesión** una huella (`sha256` del hash de la contraseña, 16 caracteres). Si el autor cambia la contraseña, la huella deja de coincidir y las sesiones desbloqueadas vuelven a bloquearse.
- El autor y los admins nunca necesitan la contraseña.
- Fuerza bruta limitada a 5 intentos/min por usuario y test.

### 8.7 Administración

| Acción | Reglas | Auditoría |
|---|---|---|
| Cambiar estado de un test | `draft` / `published` / `archived`. Al despublicar se quita el destacado | `test.status` (+ `test.unfeatured`) |
| Destacar / quitar destacado | Solo tests públicos y publicados; `featured_at` ordena la Home | `test.featured` / `test.unfeatured` |
| Borrar test | Ajusta contadores del autor y de categorías | `test.deleted` |
| Cambiar rol | `user` ↔ `admin`. No puedes cambiarte a ti mismo | `user.role` |
| Suspender / reactivar | No a ti mismo ni a un admin (quítale antes el rol). Suspender cierra todas sus sesiones | `user.suspended` / `user.reactivated` |
| Categorías | Nombre único, slug único automático, icono validado `fa-(solid|regular|brands) fa-*`, color `#RRGGBB` | `category.created/updated/deleted` |

---

## 9. Seeders y Datos de Prueba

### DatabaseSeeder

| Nombre | Email | Contraseña | Rol |
|---|---|---|---|
| Test User | `test@example.com` | `password` | `user` |
| Admin User | `admin@example.com` | `SEED_ADMIN_PASSWORD` (o aleatoria impresa por consola) | `admin` |

Es idempotente (`updateOrCreate`), así que se puede ejecutar varias veces.

### QuestionSeeder

**8 categorías:**

| Categoría | Slug | Color | Subcategoría de |
|---|---|---|---|
| Programación | programacion | #3B82F6 | — |
| Laravel | laravel | #FF2D20 | Programación |
| JavaScript | javascript | #F7DF1E | Programación |
| Bases de Datos | bases-de-datos | #F59E0B | Programación |
| Ciencias | ciencias | #10B981 | — |
| Historia | historia | #8B5CF6 | — |
| Geografía | geografia | #06B6D4 | — |
| Inglés | ingles | #EC4899 | — |

**10 tests con 80 preguntas** (30 de opción múltiple, 30 de verdadero/falso y 20 de respuesta corta): Laravel, JavaScript, SQL, Historia universal, Geografía, Inglés básico, Ciencia y matemáticas, Cine y series, Deportes y Arte y literatura.

> Los nuevos tipos (`multiple_select`, `fill_blank`, `order_items`, `numeric`) no están en el seeder: créalos desde el editor para probarlos.

---

## 10. Testing

```bash
./vendor/bin/sail artisan test           # Docker
composer test                            # sin Docker (config:clear + test)
php artisan test --filter=ReviewModeTest # una suite concreta
```

`phpunit.xml` usa la base de datos `testing`, caché y sesiones en memoria (`array`) y `BCRYPT_ROUNDS=4`.

| Suite | Qué comprueba |
|---|---|
| `QuestionTypesTest` | Guardado de los 7 tipos e intento perfecto = 100; el payload del alumno no revela la solución; puntuación parcial; penalización solo en fallos totales y nota ≥ 0; las preguntas en blanco no penalizan; numérica con coma y tolerancia; etiquetas en resultados; mensajes de validación; ida y vuelta del editor |
| `ReviewModeTest` | La cola incluye falladas, parciales y en blanco; una pregunta sale al acertarla; el repaso no gasta intentos ni toca estadísticas; solo corrige pendientes; rechazo sin pendientes; editar un test conserva respuestas e ids de opciones |
| `DashboardTest` | Requiere login; racha con repasos; racha viva hasta medianoche; días en la zona horaria del usuario; resumen sin repasos; dominio con última respuesta y parciales; pendientes ocultan tests inaccesibles; historial paginado, filtrado y privado |
| `AdminTest` | Rutas solo para admin; cualquier admin funciona; destacados en la Home con auditoría; borrado ajusta contadores; suspensión bloquea login y cierra sesiones; no auto-bloqueo ni suspender admins; promoción a admin; CRUD de categorías con slugs únicos e iconos seguros; búsqueda y filtros |
| `TestPasswordTest` | Contraseña con hash y nunca expuesta; bloqueo hasta desbloquear; el autor no la necesita; cambiarla vuelve a bloquear y quitarla desbloquea; rate limiting del desbloqueo |
| `SecurityGdprTest` | Consentimiento obligatorio y registrado; rate limiting del login; avatar ignora la extensión del cliente; perfil público sin campos privados; las preguntas no filtran explicaciones; exportación de datos propios; borrado de cuenta (anonimizando o borrando tests) |

---

## 11. Privacidad y RGPD

| Principio / derecho | Implementación |
|---|---|
| **Consentimiento** (art. 7) | Casilla obligatoria en el registro (≥ 14 años, LOPDGDD art. 7, y aceptación de Política y Términos). Se guarda fecha y versión (`privacy_accepted_at`, `privacy_policy_version`). Sube `AuthController::PRIVACY_POLICY_VERSION` al cambiar los textos |
| **Acceso y portabilidad** (arts. 15 y 20) | `GET /api/account/export` → JSON con todos tus datos |
| **Supresión** (art. 17) | `DELETE /api/account`. Los tests públicos publicados se conservan anonimizados ("Usuario eliminado") salvo que elijas borrarlos |
| **Minimización** (art. 5.1.c) | No se guarda IP ni user-agent por intento; el perfil público muestra datos mínimos |
| **Limitación del plazo** (art. 5.1.e) | Limpieza programada de sesiones, tokens de reseteo y tokens de Sanctum; logs de 30 días |
| **Seguridad** (art. 32) | Ver [§12](#12-notas-de-seguridad) |
| **Auditoría de admins** | `admin_audit_logs` sin datos personales en `details` |
| **Datos locales** | Borradores del editor por usuario, borrados al cerrar sesión o eliminar la cuenta |

---

## 12. Notas de Seguridad

| Aspecto | Estado | Descripción |
|---|---|---|
| **Sesión** | ✅ | Cookie httpOnly, cifrada, `SameSite=lax`, `Secure` en producción; regeneración en login (anti fijación) |
| **CSRF** | ✅ | Token XSRF de Sanctum en todas las peticiones mutantes |
| **Contraseñas** | ✅ | bcrypt, mínimo 8; el cambio exige la actual y cierra otras sesiones |
| **Rate limiting** | ✅ | Login/registro, API general, desbloqueo de tests y operaciones sensibles ([§6.4](#64-middleware-y-rate-limiting)) |
| **Roles** | ✅ | Middleware `admin` en servidor; guard de router solo para UX |
| **Suspensión** | ✅ | Bloquea login y corta sesiones abiertas en la siguiente petición |
| **Ocultación de soluciones** | ✅ | `forTaking()` por tipo; probado en tests |
| **Autorización** | ✅ | `TestAccess` para ver/gestionar; resultados solo del dueño |
| **Subida de archivos** | ✅ | Validación de imagen, extensión derivada del MIME, Caddy solo sirve imágenes en `/storage` |
| **Cabeceras HTTP** | ✅ | HSTS, CSP (`default-src 'self'`, sin scripts inline), `X-Frame-Options: DENY`, `nosniff`, Referrer-Policy, Permissions-Policy, COOP; se eliminan `Server` y `X-Powered-By` |
| **Inyección SQL** | ✅ | Eloquent / query builder parametrizados |
| **XSS** | ✅ | Vue escapa por defecto; iconos de categoría validados por regex antes de usarse como clase |
| **Transacciones** | ✅ | Crear/editar tests, intentos, borrado de cuenta y acciones de admin |
| **Recuperación de contraseña olvidada** | ⚠️ Pendiente | No hay flujo por email; solo cambio con sesión iniciada |
| **Verificación de email** | ⚠️ Pendiente | `email_verified_at` se rellena al registrarse sin verificación real |

---

## 13. Funcionalidades Planificadas (No Implementadas)

Tablas y modelos que existen pero **sin API ni interfaz**:

| Funcionalidad | Tablas | Estado |
|---|---|---|
| Grupos / clases | `groups`, `group_members`, `group_tests` | Modelo con relaciones |
| Comentarios | `comments` | Modelo con relaciones |
| Valoraciones | `ratings` | Modelo con relaciones |
| Seguidores | `follows` | Relaciones en `User` |
| Gamificación (puntos, niveles, insignias) | `user_points`, `point_history`, campos de `user_stats` | Tablas creadas |
| Multimedia en preguntas | `questions.image_url/audio_url/video_url` | Columnas sin subida ni presentación |
| Notificaciones / reportes | `notifications`, `reports` | Tablas creadas |
| Configuración y logs de actividad | `settings`, `activity_logs` | Tablas creadas |
| Recuperación de contraseña por email | `password_reset_tokens` | Solo limpieza programada |
| Roles `premium` / `educator` | `users.role` | Valores del enum sin efecto |

---

## 14. Historial de Cambios

### Rama `Redesign` (noviembre 2026)

| Área | Cambios |
|---|---|
| **Rediseño UI** | Nuevo sistema de diseño con *design tokens* y tema oscuro sin colores literales; nuevas Home, catálogo, ViewTest, TakeTest, Results, Profile, Help, Contact y legales; `AuthShell`; toasts; gráficas SVG propias. Eliminados componentes obsoletos (`QuestionCard`, `QuestionMultipleChoice`, `QuestionShortAnswer`, `QuestionTrueFalse`, `CountdownTimer`, `QuestionResult`, `ScoreCircle`, `TestCard`, `TestFilters`, `TestInfo`, `TestStats`, `ActivityFeed`, `StatsCard`) |
| **Editor** | Edición de tests, arrastrar para reordenar, duplicar preguntas, vista previa, borrador local, modo de presentación, contraseña y penalización |
| **Tipos de pregunta** | Arquitectura por estrategias (`app/Questions`); nuevos `multiple_select`, `fill_blank`, `order_items`, `numeric`; puntuación parcial y penalización |
| **Modo repaso** | Repetir solo las preguntas falladas sin gastar intentos (`test_attempts.mode`) |
| **Mi progreso** | `/dashboard` con resumen, racha en la zona horaria del usuario, calendario, dominio por categoría, pendientes e historial |
| **Administración** | Panel `/admin`: métricas, tests destacados y estado, usuarios (rol, suspensión), CRUD de categorías y auditoría |
| **Seguridad** | Paso de tokens Bearer en `localStorage` a sesión en cookie httpOnly + CSRF; rate limiting; cambio de contraseña seguro; cuentas suspendidas; tests con contraseña con hash y bloqueo por sesión; Caddy con HTTPS y cabeceras |
| **RGPD** | Consentimiento versionado, exportación y borrado de cuenta, anonimización de tests, minimización de IP/user-agent, tareas de retención y textos legales actualizados |
| **Testing** | Nuevas suites de feature: `QuestionTypesTest`, `ReviewModeTest`, `DashboardTest`, `AdminTest`, `TestPasswordTest`, `SecurityGdprTest` |

### Versión inicial (febrero 2026)

Catálogo, creación de tests con 3 tipos de pregunta (opción múltiple, verdadero/falso, respuesta corta), realización con temporizador, resultados, perfiles y autenticación por token Bearer.

---

> **Documento mantenido por el equipo de desarrollo de TestMaster.**
