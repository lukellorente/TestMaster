# TestMaster — Documentación Técnica Completa

> **Plataforma de gestión de exámenes y cuestionarios en línea.**
> Stack: Laravel 12 + Vue.js 3 · MySQL 8.4 · Docker (Laravel Sail)

---

## Tabla de Contenidos

1. [Visión General del Proyecto](#1-visión-general-del-proyecto)
2. [Stack Tecnológico](#2-stack-tecnológico)
3. [Arquitectura del Sistema](#3-arquitectura-del-sistema)
4. [Instalación y Despliegue](#4-instalación-y-despliegue)
5. [Base de Datos](#5-base-de-datos)
   - 5.1 [Diagrama de Relaciones (ER)](#51-diagrama-de-relaciones-er)
   - 5.2 [Detalle de Tablas](#52-detalle-de-tablas)
6. [Backend — Laravel](#6-backend--laravel)
   - 6.1 [Modelos y Relaciones Eloquent](#61-modelos-y-relaciones-eloquent)
   - 6.2 [API REST — Endpoints](#62-api-rest--endpoints)
   - 6.3 [Autenticación (Sanctum)](#63-autenticación-sanctum)
   - 6.4 [Controladores en Detalle](#64-controladores-en-detalle)
7. [Frontend — Vue.js](#7-frontend--vuejs)
   - 7.1 [Estructura de Archivos](#71-estructura-de-archivos)
   - 7.2 [Router y Navegación](#72-router-y-navegación)
   - 7.3 [Store (Pinia)](#73-store-pinia)
   - 7.4 [Capa API (Axios)](#74-capa-api-axios)
   - 7.5 [Páginas](#75-páginas)
   - 7.6 [Componentes](#76-componentes)
8. [Lógica de Negocio](#8-lógica-de-negocio)
   - 8.1 [Creación de Tests](#81-creación-de-tests)
   - 8.2 [Corrección Automática de Respuestas](#82-corrección-automática-de-respuestas)
   - 8.3 [Sistema de Puntuación y Estadísticas](#83-sistema-de-puntuación-y-estadísticas)
   - 8.4 [Perfiles de Usuario](#84-perfiles-de-usuario)
9. [Seeders y Datos de Prueba](#9-seeders-y-datos-de-prueba)
10. [Funcionalidades Planificadas (No Implementadas)](#10-funcionalidades-planificadas-no-implementadas)
11. [Notas de Seguridad](#11-notas-de-seguridad)

---

## 1. Visión General del Proyecto

**TestMaster** es una plataforma web completa para la creación, gestión y realización de exámenes/cuestionarios en línea. Está dirigida a instituciones educativas, academias y entornos corporativos.

### ¿Qué permite hacer?

| Actor | Acciones |
|---|---|
| **Visitante (sin cuenta)** | Ver la lista de tests públicos, ver detalles de un test, ver perfiles de usuarios |
| **Usuario registrado** | Todo lo anterior + crear tests con preguntas, realizar tests, ver resultados detallados, gestionar su perfil |
| **Admin** | Rol `admin`: panel `/admin` (métricas, tests, usuarios, categorías y registro de acciones) |

### Tipos de preguntas soportados

| Tipo | Datos | Puntuación |
|---|---|---|
| `multiple_choice` (opción múltiple) | `question_options`, una correcta | Todo o nada |
| `multiple_select` (selección múltiple) | `question_options`, una o más correctas | Parcial: (aciertos − errores) / correctas, mínimo 0 |
| `true_false` (verdadero/falso) | `question_options` (Verdadero / Falso) | Todo o nada |
| `short_answer` (respuesta corta) | `question_options` (respuesta aceptada) | Todo o nada, sin mayúsculas ni espacios sobrantes |
| `fill_blank` (rellenar huecos) | `___` en el enunciado + `question_blanks` | Parcial: huecos acertados / huecos |
| `order_items` (ordenar elementos) | `question_order_items` | Parcial: elementos en su posición / elementos |
| `numeric` (numérica) | `questions.numeric_answer` y `numeric_tolerance` | Todo o nada, dentro del margen ± |

Cada tipo vive en `app/Questions/Types/` (validación, guardado, datos para el alumno, corrección y etiquetas de resultados). `App\Questions\AttemptScorer` calcula la nota:

- Cada pregunta aporta `fracción de acierto × puntos`.
- Con `tests.wrong_penalty` > 0, una respuesta con 0 aciertos resta `wrong_penalty × puntos`. Las parciales y las preguntas en blanco no restan.
- La nota es `puntos obtenidos / puntos totales` y nunca baja de 0 %. Solo cuenta como correcta una pregunta con el 100 % de acierto.

---

## 2. Stack Tecnológico

| Capa | Tecnología | Versión |
|---|---|---|
| **Backend (API)** | PHP + Laravel | PHP 8.2+, Laravel 12 |
| **Frontend (SPA)** | Vue.js | Vue 3 (Composition API) |
| **Router Frontend** | Vue Router | 4.x |
| **Estado Frontend** | Pinia | 3.x |
| **Build Tool** | Vite | 7.x |
| **CSS** | Tailwind CSS + CSS personalizado | Tailwind 4 |
| **HTTP Client** | Axios | 1.x |
| **Autenticación API** | Laravel Sanctum | 4.x (tokens Bearer) |
| **Base de Datos** | MySQL | 8.4 |
| **Containerización** | Docker via Laravel Sail | — |
| **Testing** | PHPUnit | 11.x |

### Dependencias principales (composer.json)

```
laravel/framework    → Framework principal
laravel/sanctum      → Autenticación por tokens API
laravel/sail         → Entorno Docker de desarrollo
laravel/tinker       → REPL para depuración
```

### Dependencias principales (package.json)

```
vue                  → Framework frontend
vue-router           → Enrutamiento SPA
pinia                → Gestión de estado
axios                → Cliente HTTP
vite                 → Bundler/dev server
@tailwindcss/vite    → Plugin CSS
@vitejs/plugin-vue   → Plugin Vue para Vite
laravel-vite-plugin  → Integración Laravel + Vite
```

---

## 3. Arquitectura del Sistema

### Patrón general

```
┌─────────────────────────────────────────────────────────┐
│                    NAVEGADOR (SPA)                       │
│  ┌──────────┐  ┌──────────┐  ┌────────┐  ┌──────────┐  │
│  │ Vue Router│  │  Pinia   │  │  Axios │  │Components│  │
│  │  (rutas) │  │ (estado) │  │ (HTTP) │  │ (UI)     │  │
│  └────┬─────┘  └────┬─────┘  └───┬────┘  └──────────┘  │
│       │              │            │                       │
└───────┼──────────────┼────────────┼───────────────────────┘
        │              │            │
        │              │     ┌──────▼──────┐
        │              │     │  /api/*     │  HTTP requests
        │              │     └──────┬──────┘
        │              │            │
┌───────┼──────────────┼────────────┼───────────────────────┐
│       │      SERVIDOR LARAVEL     │                       │
│  ┌────▼─────────────────────┐     │                       │
│  │   routes/web.php         │     │                       │
│  │   (catch-all → SPA)      │     │                       │
│  └──────────────────────────┘     │                       │
│                              ┌────▼──────────────┐        │
│                              │  routes/api.php   │        │
│                              │  (endpoints REST) │        │
│                              └────┬──────────────┘        │
│                                   │                       │
│                ┌──────────────────▼──────────────┐        │
│                │      CONTROLADORES (Api/)       │        │
│                │  AuthController                 │        │
│                │  TestController                 │        │
│                │  AttemptController               │        │
│                │  ProfileController               │        │
│                └──────────────────┬──────────────┘        │
│                                   │                       │
│                ┌──────────────────▼──────────────┐        │
│                │      MODELOS (Eloquent ORM)     │        │
│                │  User, Test, Question, etc.     │        │
│                └──────────────────┬──────────────┘        │
│                                   │                       │
└───────────────────────────────────┼───────────────────────┘
                                    │
                          ┌─────────▼─────────┐
                          │    MySQL 8.4       │
                          │  (Docker container)│
                          └───────────────────┘
```

### Flujo de una petición típica

1. El usuario navega a una URL en el navegador (ej: `/test/5`)
2. Laravel sirve siempre el mismo archivo Blade (SPA catch-all en `routes/web.php`)
3. Vue Router toma el control y renderiza el componente correspondiente (`ViewTest.vue`)
4. El componente hace peticiones a `/api/tests/5` usando Axios
5. Laravel recibe la petición en `routes/api.php`, la dirige al controlador
6. El controlador consulta la base de datos mediante Eloquent y devuelve JSON
7. El componente Vue actualiza la interfaz con los datos recibidos

### Infraestructura Docker (compose.yaml)

```
┌─────────────────────────────────────┐
│           docker compose            │
│                                     │
│  ┌────────────────────────────┐     │
│  │  laravel.test (app)        │     │
│  │  Imagen: sail-8.5/app      │     │
│  │  Puerto 80 → HTTP          │     │
│  │  Puerto 5173 → Vite HMR    │     │
│  │  Volumen: .:/var/www/html   │     │
│  └──────────┬─────────────────┘     │
│             │ depends_on             │
│  ┌──────────▼─────────────────┐     │
│  │  mysql (base de datos)     │     │
│  │  Imagen: mysql:8.4         │     │
│  │  Puerto 3306               │     │
│  │  Volumen: sail-mysql       │     │
│  └────────────────────────────┘     │
│                                     │
│  Red: sail (bridge)                 │
└─────────────────────────────────────┘
```

---

## 4. Instalación y Despliegue

### Requisitos previos

- **Docker** y **Docker Compose** instalados
- **Node.js** (v18+) y **npm** para el frontend
- **Git** para clonar el repositorio

### Pasos de instalación

```bash
# 1. Clonar el repositorio
git clone https://github.com/HiImGhost666/TestMaster_Laravel-Vue.git
cd TestMaster_Laravel-Vue

# 2. Copiar el archivo de entorno
cp .env.example .env

# 3. Instalar dependencias de PHP (con Sail)
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php82-composer:latest \
    composer install --ignore-platform-reqs

# 4. Levantar los contenedores Docker
./vendor/bin/sail up -d

# 5. Generar la clave de la aplicación
./vendor/bin/sail artisan key:generate

# 6. Ejecutar las migraciones
./vendor/bin/sail artisan migrate

# 7. (Opcional) Poblar con datos de prueba
./vendor/bin/sail artisan db:seed

# 8. Instalar dependencias de Node.js
./vendor/bin/sail npm install

# 9. Compilar el frontend (desarrollo)
./vendor/bin/sail npm run dev

# 10. Para producción
./vendor/bin/sail npm run build
```

### Variables de entorno clave (.env)

| Variable | Descripción | Valor típico |
|---|---|---|
| `APP_URL` | URL base de la aplicación | `http://localhost` |
| `DB_CONNECTION` | Driver de base de datos | `mysql` |
| `DB_HOST` | Host de MySQL | `mysql` (nombre del servicio Docker) |
| `DB_PORT` | Puerto de MySQL | `3306` |
| `DB_DATABASE` | Nombre de la base de datos | `laravel` |
| `DB_USERNAME` | Usuario de MySQL | `sail` |
| `DB_PASSWORD` | Contraseña de MySQL | `password` |
| `APP_PORT` | Puerto HTTP del contenedor | `80` |
| `VITE_PORT` | Puerto del servidor Vite (HMR) | `5173` |

### Comandos útiles

```bash
# Acceder al contenedor
./vendor/bin/sail shell

# Ejecutar tests
./vendor/bin/sail artisan test

# Limpiar caché
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:clear

# Reiniciar la base de datos
./vendor/bin/sail artisan migrate:fresh --seed

# Ver rutas registradas
./vendor/bin/sail artisan route:list
```

---

## 5. Base de Datos

La base de datos se compone de **26 tablas** organizadas en 6 dominios funcionales, definidas en 8 archivos de migración.

### 5.1 Diagrama de Relaciones (ER)

```
┌──────────────┐       ┌──────────────┐       ┌──────────────────┐
│    users     │       │    tests     │       │   categories     │
├──────────────┤       ├──────────────┤       ├──────────────────┤
│ id (PK)      │──┐    │ id (PK)      │──┐    │ id (PK)          │
│ email        │  │    │ user_id (FK) │  │    │ name             │
│ password_hash│  │    │ title        │  │    │ slug             │
│ name         │  │    │ description  │  │    │ parent_id (FK→self)│
│ avatar       │  │    │ is_public    │  │    │ tests_count      │
│ bio          │  │    │ time_limit   │  │    └────────┬─────────┘
│ role         │  │    │ max_attempts │  │             │
│ last_login   │  │    │ pass_score   │  │    ┌────────▼─────────┐
└──────┬───────┘  │    │ difficulty   │  │    │ test_categories  │
       │          │    │ status       │  │    │ (tabla pivote)   │
       │          │    │ views_count  │  ├───►│ test_id (FK)     │
       │          │    └──────┬───────┘  │    │ category_id (FK) │
       │          │           │          │    └──────────────────┘
       │          │           │          │
       │ 1:1      │    1:N    │    1:1   │
       ▼          │           ▼          │
┌──────────────┐  │  ┌──────────────┐   │   ┌──────────────────┐
│ user_points  │  │  │  questions   │   │   │   test_stats     │
├──────────────┤  │  ├──────────────┤   │   ├──────────────────┤
│ user_id (FK) │  │  │ id (PK)      │   │   │ test_id (FK)     │
│ points       │  │  │ test_id (FK) │   │   │ views_count      │
│ level        │  │  │ type         │   │   │ attempts_count   │
│ experience   │  │  │ content      │   │   │ average_score    │
│ streak_days  │  │  │ explanation  │   │   │ pass_rate        │
└──────────────┘  │  │ points       │   │   └──────────────────┘
                  │  └──────┬───────┘   │
┌──────────────┐  │         │           │
│ user_stats   │  │         │ 1:N       │
├──────────────┤  │         ▼           │
│ user_id (FK) │  │  ┌──────────────┐   │
│ tests_created│  │  │question_opts │   │
│ tests_passed │  │  ├──────────────┤   │
│ average_score│  │  │ question_id  │   │
│ current_streak│  │  │ content      │   │
└──────────────┘  │  │ is_correct   │   │
                  │  │ order_index  │   │
                  │  └──────────────┘   │
                  │                     │
                  │  N:M                │
                  ▼                     │
          ┌──────────────┐              │
          │test_attempts │              │
          ├──────────────┤              │
          │ id (PK)      │              │
          │ user_id (FK) │◄─────────────┘
          │ test_id (FK) │
          │ score        │
          │ passed       │
          │ time_spent   │
          └──────┬───────┘
                 │ 1:N
                 ▼
          ┌──────────────────┐
          │question_responses│
          ├──────────────────┤
          │ attempt_id (FK)  │
          │ question_id (FK) │
          │ given_answer     │
          │ is_correct       │
          │ points_earned    │
          └──────────────────┘

     ┌──────────────┐      ┌──────────────┐
     │   comments   │      │   ratings    │
     ├──────────────┤      ├──────────────┤
     │ user_id (FK) │      │ user_id (FK) │
     │ test_id (FK) │      │ test_id (FK) │
     │ parent_id    │      │ rating (1-5) │
     │ content      │      └──────────────┘
     │ is_edited    │
     └──────────────┘      ┌──────────────┐
                           │   follows    │
     ┌──────────────┐      ├──────────────┤
     │   groups     │      │ follower_id  │
     ├──────────────┤      │ following_id │
     │ owner_id(FK) │      └──────────────┘
     │ name         │
     │ join_code    │
     │ is_private   │
     └──────────────┘
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
| `avatar` | varchar(255) | nullable | URL del avatar |
| `bio` | text | nullable | Biografía del usuario |
| `role` | enum | default: 'user' | `user`, `premium`, `educator`, `admin` |
| `email_verified_at` | timestamp | nullable | Fecha de verificación de email |
| `created_at` | timestamp | — | Fecha de registro |
| `updated_at` | timestamp | — | Última actualización |
| `last_login` | timestamp | nullable | Último inicio de sesión |
| `remember_token` | varchar(100) | nullable | Token "recordar sesión" |

##### Tabla `user_points`
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | bigint (PK) | auto_increment | — |
| `user_id` | bigint (FK→users) | unique, cascade delete | Relación 1:1 con usuario |
| `points` | int | default: 0 | Puntos acumulados |
| `level` | int | default: 1 | Nivel del usuario |
| `experience` | int | default: 0 | Experiencia acumulada |
| `streak_days` | int | default: 0 | Días consecutivos de actividad |
| `last_activity_date` | date | nullable | Última fecha de actividad |

##### Tabla `user_stats`
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | bigint (PK) | auto_increment | — |
| `user_id` | bigint (FK→users) | unique, cascade delete | Relación 1:1 con usuario |
| `tests_created` | unsigned int | default: 0 | Tests creados por el usuario |
| `tests_completed` | unsigned int | default: 0 | Tests completados |
| `tests_passed` | unsigned int | default: 0 | Tests aprobados |
| `average_score` | decimal(5,2) | default: 0 | Nota media global |
| `total_study_time` | unsigned int | default: 0 | Tiempo total de estudio (seg) |
| `perfect_scores` | unsigned int | default: 0 | Puntuaciones perfectas |
| `current_streak` | unsigned int | default: 0 | Racha actual (días) |
| `longest_streak` | unsigned int | default: 0 | Racha más larga |
| `total_points` | int | default: 0 | Puntos totales |
| `badges_earned` | unsigned int | default: 0 | Insignias ganadas |
| `followers_count` | unsigned int | default: 0 | Número de seguidores |
| `following_count` | unsigned int | default: 0 | Número de seguidos |

---

#### Dominio: Tests y Categorías

##### Tabla `tests`
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | bigint (PK) | auto_increment | — |
| `user_id` | bigint (FK→users) | cascade delete, indexed | Creador del test |
| `title` | varchar(255) | not null | Título del test |
| `description` | text | nullable | Descripción |
| `is_public` | boolean | default: true, indexed | Visibilidad pública |
| `is_featured` | boolean | default: false | Test destacado |
| `password` | varchar(255) | nullable | Protección por contraseña |
| `time_limit` | unsigned int | nullable | Límite de tiempo (segundos) |
| `max_attempts` | int | default: -1 | Intentos máximos (-1 = ilimitados) |
| `pass_score` | int | default: 70 | Nota mínima para aprobar (%) |
| `shuffle_questions` | boolean | default: false | Barajar orden de preguntas |
| `shuffle_answers` | boolean | default: false | Barajar orden de respuestas |
| `show_correct_answers` | boolean | default: true | Mostrar respuestas correctas al finalizar |
| `show_explanations` | boolean | default: true | Mostrar explicaciones |
| `difficulty` | enum | default: 'medium', indexed | `easy`, `medium`, `hard` |
| `status` | enum | default: 'draft', indexed | `draft`, `published`, `archived` |
| `views_count` | unsigned int | default: 0 | Contador de visualizaciones |
| `attempts_count` | unsigned int | default: 0 | Contador de intentos |
| `average_score` | decimal(5,2) | nullable | Nota media |
| `published_at` | timestamp | nullable | Fecha de publicación |

##### Tabla `categories`
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | bigint (PK) | auto_increment | — |
| `name` | varchar(100) | unique | Nombre de la categoría |
| `slug` | varchar(100) | unique | Slug URL-friendly |
| `description` | text | nullable | Descripción |
| `icon` | varchar(50) | nullable | Clase CSS del ícono (ej: `fa-code`) |
| `color` | varchar(7) | nullable | Color hex (ej: `#3B82F6`) |
| `parent_id` | bigint (FK→categories) | nullable, cascade delete | Categoría padre (jerárquica) |
| `tests_count` | unsigned int | default: 0 | Contador de tests en esta categoría |

##### Tabla `test_categories` (tabla pivote)
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `test_id` | bigint (FK→tests) | PK compuesto, cascade delete | — |
| `category_id` | bigint (FK→categories) | PK compuesto, cascade delete | — |

---

#### Dominio: Preguntas

##### Tabla `questions`
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | bigint (PK) | auto_increment | — |
| `test_id` | bigint (FK→tests) | cascade delete, indexed | Test al que pertenece |
| `type` | enum | indexed | `multiple_choice`, `multiple_select`, `true_false`, `short_answer`, `fill_blank`, `order_items`, `numeric` |
| `content` | text | not null | Texto/enunciado de la pregunta |
| `explanation` | text | nullable | Explicación de la respuesta correcta |
| `points` | int | default: 1 | Puntos que vale la pregunta |
| `order_index` | int | default: 0 | Orden dentro del test |
| `image_url` | varchar(255) | nullable | Imagen adjunta |
| `audio_url` | varchar(255) | nullable | Audio adjunto |
| `video_url` | varchar(255) | nullable | Vídeo adjunto |
| `time_limit` | unsigned int | nullable | Límite de tiempo individual (seg) |

##### Tabla `question_options`
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | bigint (PK) | auto_increment | — |
| `question_id` | bigint (FK→questions) | cascade delete | Pregunta a la que pertenece |
| `content` | text | not null | Texto de la opción |
| `is_correct` | boolean | default: false | Si es la respuesta correcta |
| `order_index` | int | default: 0 | Orden de aparición |
| `image_url` | varchar(255) | nullable | Imagen de la opción |

##### Tabla `question_blanks` (para tipo fill_blank)
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | bigint (PK) | auto_increment | — |
| `question_id` | bigint (FK→questions) | cascade delete | — |
| `blank_index` | int | — | Índice del hueco |
| `correct_answers` | json | — | Array de respuestas válidas |
| `case_sensitive` | boolean | default: false | Si distingue mayúsculas |

##### Tabla `question_order_items` (para tipo order_items)
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | bigint (PK) | auto_increment | — |
| `question_id` | bigint (FK→questions) | cascade delete | — |
| `content` | text | — | Texto del elemento |
| `correct_order` | int | — | Posición correcta |

##### Tabla `question_stats`
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | bigint (PK) | auto_increment | — |
| `question_id` | bigint (FK→questions) | unique, cascade delete | 1:1 con pregunta |
| `times_answered` | unsigned int | default: 0 | Veces respondida |
| `times_correct` | unsigned int | default: 0 | Veces acertada |
| `times_incorrect` | unsigned int | default: 0 | Veces fallada |
| `difficulty_score` | decimal(5,2) | default: 0 | Dificultad calculada |
| `average_time` | unsigned int | default: 0 | Tiempo medio de respuesta |

---

#### Dominio: Intentos y Respuestas

##### Tabla `test_attempts`
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | bigint (PK) | auto_increment | — |
| `user_id` | bigint (FK→users) | cascade delete, indexed | Usuario que hizo el intento |
| `test_id` | bigint (FK→tests) | cascade delete, indexed | Test realizado |
| `score` | decimal(5,2) | default: 0 | Nota obtenida (0-100) |
| `points_earned` | int | default: 0 | Puntos ganados |
| `total_points` | int | default: 0 | Puntos totales posibles |
| `correct_answers` | int | default: 0 | Respuestas correctas |
| `incorrect_answers` | int | default: 0 | Respuestas incorrectas |
| `unanswered` | int | default: 0 | Sin responder |
| `time_spent` | unsigned int | default: 0 | Tiempo empleado (seg) |
| `is_completed` | boolean | default: false, indexed | Si se completó |
| `passed` | boolean | default: false | Si aprobó |
| `mode` | enum | default: `exam` | `exam` o `review` (repaso de fallos). Los repasos no gastan intentos ni cuentan en medias ni estadísticas: usa el scope `TestAttempt::completedExams()` |
| `started_at` | timestamp | nullable | Hora de inicio |
| `completed_at` | timestamp | nullable | Hora de finalización |
| `ip_address` | varchar(45) | nullable | IP del usuario |
| `user_agent` | text | nullable | Navegador/dispositivo |

##### Tabla `question_responses`
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | bigint (PK) | auto_increment | — |
| `attempt_id` | bigint (FK→test_attempts) | cascade delete | Intento al que pertenece |
| `question_id` | bigint (FK→questions) | cascade delete | Pregunta respondida |
| `given_answer` | json | nullable | Respuesta dada (formato varía según tipo) |
| `is_correct` | boolean | default: false | Si fue correcta |
| `points_earned` | int | default: 0 | Puntos obtenidos |
| `time_spent` | unsigned int | default: 0 | Tiempo en esta pregunta |

---

#### Dominio: Comunidad

##### Tabla `test_stats`
| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `test_id` | bigint (FK→tests) | unique, cascade delete | 1:1 con test |
| `views_count` | unsigned int | default: 0 | Visualizaciones |
| `attempts_count` | unsigned int | default: 0 | Intentos totales |
| `completions_count` | unsigned int | default: 0 | Completados |
| `average_score` | decimal(5,2) | default: 0 | Nota media |
| `pass_rate` | decimal(5,2) | default: 0 | Tasa de aprobados |
| `rating_avg` | decimal(3,2) | default: 0 | Puntuación media (estrellas) |
| `ratings_count` | unsigned int | default: 0 | Número de valoraciones |
| `comments_count` | unsigned int | default: 0 | Número de comentarios |

##### Tabla `comments`
| Columna | Tipo | Descripción |
|---|---|---|
| `id` | bigint (PK) | — |
| `user_id` | bigint (FK→users) | Autor |
| `test_id` | bigint (FK→tests) | Test comentado |
| `parent_id` | bigint (FK→comments) | Comentario padre (respuestas anidadas) |
| `content` | text | Contenido del comentario |
| `is_edited` | boolean | Si fue editado |
| `is_hidden` | boolean | Si está oculto (moderación) |

##### Tabla `ratings`
| Columna | Tipo | Descripción |
|---|---|---|
| `user_id` | bigint (FK→users) | Restricción unique con test_id |
| `test_id` | bigint (FK→tests) | Test valorado |
| `rating` | tinyint | Puntuación 1-5 |

##### Tabla `follows`
| Columna | Tipo | Descripción |
|---|---|---|
| `follower_id` | bigint (FK→users) | PK compuesto, quien sigue |
| `following_id` | bigint (FK→users) | PK compuesto, a quien sigue |

---

#### Dominio: Grupos

##### Tabla `groups`
| Columna | Tipo | Descripción |
|---|---|---|
| `id` | bigint (PK) | — |
| `owner_id` | bigint (FK→users) | Creador/dueño del grupo |
| `name` | varchar(100) | Nombre del grupo |
| `description` | text (nullable) | Descripción |
| `avatar` | varchar(255) (nullable) | Imagen del grupo |
| `is_private` | boolean (default: false) | Si es privado |
| `join_code` | varchar(20) (unique, nullable) | Código de invitación |
| `max_members` | int (nullable) | Máximo de miembros |

##### Tabla `group_members` (pivote)
| Columna | Tipo | Descripción |
|---|---|---|
| `group_id` | bigint (FK→groups) | unique con user_id |
| `user_id` | bigint (FK→users) | — |
| `role` | enum | `owner`, `admin`, `member` |

##### Tabla `group_tests` (pivote)
| Columna | Tipo | Descripción |
|---|---|---|
| `group_id` | bigint (FK→groups) | unique con test_id |
| `test_id` | bigint (FK→tests) | — |
| `added_by` | bigint (FK→users) | Quien añadió el test |

---

#### Dominio: Sistema

| Tabla | Descripción |
|---|---|
| `point_history` | Registro de transacciones de puntos (razón: test_completed, test_created, perfect_score, streak, badge_earned, other) |
| `settings` | Configuración clave-valor del sistema (tipo: string, integer, boolean, json) |
| `activity_logs` | Registro de auditoría (acción: create_test, edit_test, delete_test, attempt_test, login, register, other) |
| `password_resets` | Tokens de restablecimiento de contraseña con expiración |
| `password_reset_tokens` | Tabla estándar de Laravel para reset de password |
| `sessions` | Sesiones de Laravel |
| `personal_access_tokens` | Tokens de API (Sanctum) |
| `cache` / `cache_locks` | Caché de Laravel |

---

## 6. Backend — Laravel

### 6.1 Modelos y Relaciones Eloquent

Se definen **14 modelos** en `app/Models/`. A continuación se detalla cada uno con sus relaciones:

#### User

```
User
├── hasMany → Test (tests creados)
├── hasMany → TestAttempt (intentos realizados)
├── hasMany → Comment (comentarios escritos)
├── hasMany → Rating (valoraciones dadas)
├── hasOne  → UserPoint (puntos/gamificación)
├── hasOne  → UserStat (estadísticas agregadas)
├── belongsToMany → User (followers, via 'follows')
├── belongsToMany → User (following, via 'follows')
└── belongsToMany → Group (grupos, via 'group_members')
```

**Nota importante:** El modelo `User` sobreescribe `getAuthPassword()` para retornar `$this->password_hash` en lugar del estándar `$this->password`, ya que la columna en la BD se llama `password_hash`.

#### Test

```
Test
├── belongsTo    → User (creador)
├── hasMany      → Question (preguntas)
├── hasMany      → TestAttempt (intentos)
├── hasMany      → Comment (comentarios)
├── hasMany      → Rating (valoraciones)
├── belongsToMany → Category (categorías, via 'test_categories')
└── hasOne       → TestStat (estadísticas)
```

#### Question

```
Question
├── belongsTo → Test
├── hasMany   → QuestionOption (opciones de respuesta)
├── hasMany   → QuestionBlank (huecos — fill_blank)
├── hasMany   → QuestionOrderItem (elementos — order_items)
├── hasMany   → QuestionResponse (respuestas dadas)
└── hasOne    → QuestionStat (estadísticas)
```

#### Modelos auxiliares

| Modelo | Relación principal |
|---|---|
| `QuestionOption` | `belongsTo` → Question |
| `QuestionBlank` | `belongsTo` → Question (casts `correct_answers` como array) |
| `QuestionOrderItem` | `belongsTo` → Question |
| `QuestionStat` | `belongsTo` → Question |
| `TestAttempt` | `belongsTo` → User, Test · `hasMany` → QuestionResponse |
| `QuestionResponse` | `belongsTo` → TestAttempt, Question (casts `given_answer` como array) |
| `Category` | `belongsTo` → Category (parent) · `hasMany` → Category (children) · `belongsToMany` → Test |
| `Comment` | `belongsTo` → User, Test, Comment (parent) · `hasMany` → Comment (replies) |
| `Rating` | `belongsTo` → User, Test |
| `Group` | `belongsTo` → User (owner) · `belongsToMany` → User (members), Test |
| `UserPoint` | `belongsTo` → User |
| `UserStat` | `belongsTo` → User |
| `TestStat` | `belongsTo` → Test |

---

### 6.2 API REST — Endpoints

Todos los endpoints están bajo el prefijo `/api`. La autenticación se gestiona mediante tokens Sanctum (Bearer Token).

#### Endpoints Públicos (sin autenticación)

| Método | Endpoint | Controlador | Descripción |
|---|---|---|---|
| `POST` | `/api/register` | `AuthController@register` | Registrar un nuevo usuario |
| `POST` | `/api/login` | `AuthController@login` | Iniciar sesión |
| `POST` | `/api/reset-password` | `AuthController@resetPassword` | Restablecer contraseña |
| `GET` | `/api/tests` | `TestController@index` | Listar tests públicos/publicados |
| `GET` | `/api/tests/stats` | `TestController@stats` | Estadísticas globales (homepage) |
| `GET` | `/api/categories` | `TestController@categories` | Listar todas las categorías |
| `GET` | `/api/tests/{id}` | `TestController@show` | Ver detalle de un test |
| `GET` | `/api/profile/{id}` | `ProfileController@show` | Ver perfil de usuario |

#### Endpoints Protegidos (requieren `Authorization: Bearer {token}`)

| Método | Endpoint | Controlador | Descripción |
|---|---|---|---|
| `POST` | `/api/logout` | `AuthController@logout` | Cerrar sesión (revoca token) |
| `GET` | `/api/me` | `AuthController@me` | Obtener datos del usuario autenticado |
| `POST` | `/api/tests` | `TestController@store` | Crear un test con preguntas |
| `GET` | `/api/tests/{id}/questions` | `TestController@questions` | Obtener preguntas para realizar test (`?mode=review`: solo las pendientes de repasar, sin límite de tiempo) |
| `POST` | `/api/attempts/submit` | `AttemptController@submit` | Enviar intento de test (`mode`: `exam` por defecto o `review`) |
| `GET` | `/api/dashboard?tz=Europe/Madrid` | `DashboardController@show` | Mi progreso: resumen, racha y calendario (en la zona horaria `tz`), dominio por categoría y fallos pendientes por test |
| `GET` | `/api/dashboard/attempts?page=&mode=` | `DashboardController@attempts` | Historial de intentos propio, paginado (15) y filtrable por `exam`/`review` |
| `GET` | `/api/tests/featured` | `TestController@featured` | Tests destacados (públicos y publicados), el más reciente primero |
| `POST` | `/api/tests/{id}/unlock` | `TestController@unlock` | Desbloquea un test con contraseña para la sesión (5 intentos/min). Sin desbloquear, preguntas y envío responden `423` |
| `GET` | `/api/admin/metrics` | `Admin\AdminController@metrics` | Métricas globales y series diarias de 30 días (solo admin) |
| `GET/PATCH/DELETE` | `/api/admin/tests[/{id}]` | `Admin\AdminController` | Listar, destacar, cambiar estado y borrar tests |
| `GET/PATCH` | `/api/admin/users[/{id}]` | `Admin\AdminController` | Listar, cambiar rol y suspender/reactivar. Un admin no puede cambiarse a sí mismo ni suspender a otro admin |
| `GET/POST/PUT/DELETE` | `/api/admin/categories[/{id}]` | `Admin\AdminCategoryController` | CRUD de categorías |
| `GET` | `/api/admin/audit` | `Admin\AdminController@audit` | Registro de acciones de administración |
| `GET` | `/api/attempts/{attemptId}/results` | `AttemptController@results` | Ver resultados de un intento |

#### Ruta web (catch-all)

```php
Route::get('/{any?}', fn() => view('app'))->where('any', '.*');
```

Sirve siempre la misma vista Blade que carga la SPA de Vue.js.

---

### 6.3 Autenticación (Sanctum)

El sistema usa **Laravel Sanctum** con tokens Bearer (no cookies de sesión).

#### Flujo de autenticación

```
1. REGISTRO / LOGIN
   Cliente → POST /api/register o /api/login
   Servidor → Crea token con $user->createToken('auth-token')
   Servidor → Devuelve { user, token }

2. ALMACENAMIENTO
   Cliente → Guarda 'token' y 'user' en localStorage

3. PETICIONES AUTENTICADAS
   Cliente → Añade header: Authorization: Bearer {token}
   (gestionado automáticamente por el interceptor de Axios)

4. VERIFICACIÓN
   Servidor → Middleware 'auth:sanctum' valida el token
   Servidor → Inyecta el usuario autenticado en $request->user()

5. LOGOUT
   Cliente → POST /api/logout
   Servidor → $request->user()->currentAccessToken()->delete()
   Cliente → Limpia localStorage

6. MANEJO DE 401
   Si el servidor devuelve 401 (token inválido/expirado):
   El interceptor de Axios limpia localStorage y redirige a /login
```

#### Campo de contraseña personalizado

La base de datos usa `password_hash` en lugar del estándar `password` de Laravel. Se soluciona con:

```php
// En app/Models/User.php
public function getAuthPassword(): string
{
    return $this->password_hash;
}
```

---

### 6.4 Controladores en Detalle

#### AuthController (`app/Http/Controllers/Api/AuthController.php`)

##### `register(Request $request)` → POST `/api/register`

**Validación:**
```
name     → required, string, max:100
email    → required, email, unique:users
password → required, string, min:6, confirmed (requiere password_confirmation)
```

**Proceso:**
1. Crea el usuario con `password_hash` hasheado (bcrypt)
2. Crea registros iniciales en `user_stats` y `user_points` (todo a 0)
3. Genera un token Sanctum
4. Devuelve `{ user, token, message }` con código **201**

**Respuesta ejemplo:**
```json
{
  "user": {
    "id": 1,
    "name": "Juan",
    "email": "juan@email.com",
    "avatar": null,
    "bio": null,
    "role": "user",
    "created_at": "2026-02-25T12:00:00Z",
    "last_login": null
  },
  "token": "1|abc123...",
  "message": "Cuenta creada exitosamente."
}
```

##### `login(Request $request)` → POST `/api/login`

**Validación:**
```
email    → required, email
password → required, string
```

**Proceso:**
1. Busca el usuario por email
2. Verifica el hash de la contraseña
3. Actualiza `last_login`
4. Genera un token Sanctum
5. Devuelve `{ user, token }`

**Error 422:** `"Email o contraseña incorrectos."`

##### `logout(Request $request)` → POST `/api/logout` (auth)

Revoca el token actual. Devuelve `{ "message": "Sesión cerrada correctamente." }`

##### `me(Request $request)` → GET `/api/me` (auth)

Carga relaciones `stats` y `points` del usuario autenticado. Devuelve `{ user }`.

##### `resetPassword(Request $request)` → POST `/api/reset-password`

**⚠️ Nota:** Implementación simplificada que actualiza la contraseña directamente con solo el email, sin verificación por token.

---

#### TestController (`app/Http/Controllers/Api/TestController.php`)

##### `index(Request $request)` → GET `/api/tests`

Lista tests públicos y publicados con paginación.

**Parámetros de query (opcionales):**

| Parámetro | Tipo | Descripción |
|---|---|---|
| `category_id` | int | Filtrar por categoría |
| `difficulty` | string | Filtrar por dificultad (`easy`, `medium`, `hard`) |
| `search` | string | Búsqueda en título y descripción |
| `sort` | string | Ordenar: `recent` (defecto), `attempts`, `longest`, `shortest` |
| `per_page` | int | Elementos por página (defecto: 20) |

**Respuesta:** Paginación estándar de Laravel (con `data`, `current_page`, `last_page`, `total`, etc.)

Cada test incluye:
- `user` (id, name) — creador
- `categories` (id, name, icon)
- `questions_count` — número de preguntas
- `completed_attempts_count` — intentos completados

##### `stats()` → GET `/api/tests/stats`

**Respuesta:**
```json
{
  "total_tests": 42,
  "total_users": 150,
  "total_attempts": 1200
}
```

##### `show(int $id)` → GET `/api/tests/{id}`

Muestra un test individual con detalles extendidos.

**Respuesta adicional:**
- `avg_score` — nota media de todos los intentos completados
- `user_attempts` — (solo si autenticado) número de intentos del usuario actual
- `last_attempt` — (solo si autenticado) datos del último intento (score, completed_at)

**Efecto lateral:** Incrementa `views_count` del test.

##### `store(Request $request)` → POST `/api/tests` (auth)

Crea un test completo con todas sus preguntas en una sola transacción.

**Body esperado:**
```json
{
  "title": "Test de Química",
  "description": "Conceptos básicos",
  "category_id": 1,
  "difficulty": "medium",
  "time_limit": 30,
  "max_attempts": 3,
  "pass_score": 70,
  "is_public": true,
  "shuffle_questions": false,
  "shuffle_answers": false,
  "show_correct_answers": true,
  "show_explanations": true,
  "status": "published",
  "questions": [
    {
      "type": "multiple_choice",
      "content": "¿Cuál es el símbolo del agua?",
      "explanation": "El agua se compone de 2 hidrógenos y 1 oxígeno",
      "points": 1,
      "options": [
        { "content": "H2O", "is_correct": true, "order_index": 0 },
        { "content": "CO2", "is_correct": false, "order_index": 1 },
        { "content": "NaCl", "is_correct": false, "order_index": 2 }
      ]
    },
    {
      "type": "true_false",
      "content": "La Tierra es plana",
      "correct": "false",
      "points": 1
    },
    {
      "type": "short_answer",
      "content": "¿Capital de Francia?",
      "correct_answer": "París",
      "points": 2
    }
  ]
}
```

**Notas:**
- `time_limit` se recibe en **minutos** y se almacena en **segundos** (× 60)
- Las preguntas vacías (contenido en blanco) se omiten automáticamente
- Al publicar, se asigna `published_at`
- Se crea un registro en `test_stats`
- Se incrementa `tests_created` en las stats del usuario

##### `questions(int $id)` → GET `/api/tests/{id}/questions` (auth)

Obtiene las preguntas para realizar el test.

**Seguridad:** No envía `is_correct` al cliente para evitar que el usuario vea las respuestas correctas.

**Respeta configuración:**
- `shuffle_questions` → preguntas en orden aleatorio
- `shuffle_answers` → opciones en orden aleatorio

**Respuesta:**
```json
{
  "test": {
    "id": 1,
    "title": "Test de Química",
    "time_limit": 1800,
    "shuffle_questions": false
  },
  "questions": [
    {
      "id": 1,
      "type": "multiple_choice",
      "content": "¿Cuál es el símbolo del agua?",
      "points": 1,
      "options": [
        { "id": 1, "question_id": 1, "content": "H2O", "order_index": 0 },
        { "id": 2, "question_id": 1, "content": "CO2", "order_index": 1 }
      ]
    }
  ]
}
```

##### `categories()` → GET `/api/categories`

Devuelve todas las categorías ordenadas por nombre.

---

#### AttemptController (`app/Http/Controllers/Api/AttemptController.php`)

##### `submit(Request $request)` → POST `/api/attempts/submit` (auth)

Envía y corrige automáticamente un intento de test.

**Body esperado:**
```json
{
  "test_id": 1,
  "started_at": 1708876800,
  "answers": {
    "1": "3",
    "2": "true",
    "3": "París"
  }
}
```

Donde el key es el `question_id` y el value es:
- Para `multiple_choice` → `option_id` seleccionado
- Para `true_false` → `"true"` o `"false"`
- Para `short_answer` → texto libre

**Proceso detallado:** Ver sección [8.2 Corrección Automática](#82-corrección-automática-de-respuestas).

**Respuesta:**
```json
{
  "attempt_id": 15,
  "score": 85.5,
  "passed": true,
  "message": "¡Aprobado!"
}
```

**Error 422:** `"Has alcanzado el número máximo de intentos"` si se excede `max_attempts`.

##### `results(int $attemptId)` → GET `/api/attempts/{attemptId}/results` (auth)

Muestra resultados detallados de un intento (solo del usuario autenticado).

**Respuesta:**
```json
{
  "attempt": {
    "id": 15,
    "score": 85.5,
    "passed": true,
    "correct_answers": 8,
    "incorrect_answers": 1,
    "unanswered": 1,
    "time_spent": 420,
    "test": { "id": 1, "title": "Test de Química", "show_correct_answers": true, "show_explanations": true }
  },
  "responses": [
    {
      "question_id": 1,
      "given_answer": "3",
      "is_correct": true,
      "points_earned": 1,
      "question": { "id": 1, "content": "...", "type": "multiple_choice", "explanation": "..." },
      "options": [
        { "id": 3, "content": "H2O", "is_correct": true },
        { "id": 4, "content": "CO2", "is_correct": false }
      ]
    }
  ]
}
```

---

#### ProfileController (`app/Http/Controllers/Api/ProfileController.php`)

##### `show(Request $request, int $id)` → GET `/api/profile/{id}`

Devuelve el perfil completo de un usuario con 4 secciones de datos:

1. **Datos del usuario** — nombre, avatar, bio, rol, fechas. El email solo se muestra si es el propio perfil.
2. **Estadísticas** — tests creados, completados, aprobados, nota media, rachas.
3. **Tests creados** — lista de tests públicos/publicados con conteo de preguntas e intentos.
4. **Tests completados** — últimos 20 intentos completados en tests públicos.
5. **Actividad reciente** — unión de tests creados + intentos completados, ordenados por fecha, máx 30 elementos.

---

## 7. Frontend — Vue.js

### 7.1 Estructura de Archivos

```
Vue/
├── App.vue                    ← Componente raíz
├── api/
│   └── axios.js               ← Instancia de Axios configurada
├── assets/
│   ├── styles.css              ← Estilos globales CSS
│   └── images/                 ← Imágenes estáticas
├── components/
│   ├── AppHeader.vue           ← Cabecera (navegación, búsqueda, tema)
│   └── AppFooter.vue           ← Pie de página
├── pages/
│   ├── Home.vue                ← Página principal (lista de tests)
│   ├── Login.vue               ← Formulario de inicio de sesión
│   ├── Register.vue            ← Formulario de registro
│   ├── ViewTest.vue            ← Detalle de un test
│   ├── TakeTest.vue            ← Realización del test (examen)
│   ├── Results.vue             ← Resultados de un intento
│   ├── CreateTest.vue          ← Creación de un test nuevo
│   ├── Profile.vue             ← Perfil de usuario
│   └── NotFound.vue            ← Página 404
├── router/
│   └── index.js                ← Definición de rutas Vue Router
└── stores/
    └── auth.js                 ← Store de autenticación (Pinia)
```

### 7.2 Router y Navegación

Definido en `Vue/router/index.js`. Usa `createWebHistory()` (URLs sin hash).

| Ruta | Componente | Requiere auth | Descripción |
|---|---|---|---|
| `/` | `Home` | No | Página principal |
| `/login` | `Login` | No | Inicio de sesión |
| `/register` | `Register` | No | Registro |
| `/test/:id` | `ViewTest` | No | Ver detalles de un test |
| `/test/:id/take` | `TakeTest` | **Sí** | Realizar el test |
| `/results/:attemptId` | `Results` | **Sí** | Ver resultados |
| `/create-test` | `CreateTest` | **Sí** | Crear un test |
| `/profile/:id` | `Profile` | No | Perfil de usuario |
| `/:pathMatch(.*)*` | `NotFound` | No | Página 404 |

#### Guard de navegación

Antes de cada navegación, se comprueba si la ruta requiere autenticación (`meta.requiresAuth`). Si el usuario no está logueado, se redirige a `/login`.

```javascript
router.beforeEach((to, from, next) => {
    const authStore = useAuthStore();
    if (to.meta.requiresAuth && !authStore.isLoggedIn) {
        next({ name: 'login' });
    } else {
        next();
    }
});
```

### 7.3 Store (Pinia)

Un único store `auth` en `Vue/stores/auth.js` gestiona toda la autenticación.

#### Estado

```javascript
state: () => ({
    user: JSON.parse(localStorage.getItem('user') || 'null'),
    token: localStorage.getItem('token') || null,
})
```

Se hidrata desde `localStorage` al cargar la aplicación, manteniendo la sesión entre recargas.

#### Getters

| Getter | Retorna |
|---|---|
| `isLoggedIn` | `true` si hay un token (controla toda la UI condicional) |
| `userId` | ID del usuario |
| `userName` | Nombre del usuario |
| `userRole` | Rol del usuario |

#### Acciones

| Acción | Descripción |
|---|---|
| `login(email, password)` | POST a `/api/login`, guarda auth |
| `register(name, email, password, password_confirmation)` | POST a `/api/register`, guarda auth |
| `logout()` | POST a `/api/logout`, limpia estado y localStorage |
| `fetchUser()` | GET a `/api/me`, actualiza datos del usuario |
| `setAuth(user, token)` | Guarda usuario y token en estado + localStorage |
| `clearAuth()` | Borra usuario y token de estado + localStorage |

### 7.4 Capa API (Axios)

Configurada en `Vue/api/axios.js`.

```javascript
const api = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});
```

#### Interceptor de peticiones

Añade automáticamente el header `Authorization: Bearer {token}` a todas las peticiones si existe un token en localStorage.

#### Interceptor de respuestas

Si recibe un error **401** (no autorizado):
1. Borra `token` y `user` de localStorage
2. Redirige a `/login`

### 7.5 Páginas

#### Home (`Vue/pages/Home.vue`)

**Funcionalidad principal:** Página de inicio que muestra la lista de tests disponibles.

**Secciones:**
- Estadísticas globales en la parte superior (total de tests, usuarios, intentos)
- Barra de filtros: campo de búsqueda, selector de categoría, selector de dificultad
- Selector de ordenamiento: "Más recientes", "Más vistos", "Más realizados"
- Tabla de tests con columnas: título, categoría, dificultad (badge), preguntas, intentos, creador
- Cada fila enlaza a `/test/{id}`
- Paginación inferior

**API calls:**
- `GET /api/tests` — lista de tests (con filtros y paginación)
- `GET /api/tests/stats` — estadísticas globales
- `GET /api/categories` — lista de categorías para el filtro

#### Login (`Vue/pages/Login.vue`)

Formulario con campos email y contraseña. Muestra errores del servidor bajo el formulario. Enlace a registro y reset de contraseña. Al hacer login exitoso, redirige a `/` (home).

#### Register (`Vue/pages/Register.vue`)

Formulario con nombre, email, contraseña y confirmación de contraseña. Valida que las contraseñas coincidan en el cliente antes de enviar. Al registrarse, redirige a home.

#### ViewTest (`Vue/pages/ViewTest.vue`)

**Muestra información detallada del test:**
- Título, descripción, creador
- Categorías (badges)
- Configuración: dificultad, tiempo límite, nota mínima, intentos máximos
- Opciones: barajar preguntas, barajar respuestas, mostrar respuestas correctas, mostrar explicaciones
- Estadísticas: número de preguntas, intentos completados, nota media
- Si el usuario está autenticado: número de intentos del usuario e información del último intento
- Botón "Realizar Test" (lleva a `/test/{id}/take`). Si no está autenticado, muestra enlace a login.

#### TakeTest (`Vue/pages/TakeTest.vue`)

**Experiencia de examen completa:**
- Carga las preguntas del test (sin respuestas correctas)
- Muestra un temporizador en la esquina superior si el test tiene `time_limit`
- Renderiza las preguntas según su tipo:
  - **multiple_choice** → radio buttons con las opciones
  - **true_false** → radio buttons "Verdadero" / "Falso"
  - **short_answer** → input de texto libre
- Botón "Enviar Test" con confirmación (modal de confirmación antes de enviar)
- Diálogo de confirmación si el usuario intenta abandonar la página (`beforeunload`)
- Auto-submit cuando el temporizador llega a 0
- Al enviar, redirige a `/results/{attemptId}`

**Datos enviados:**
```javascript
{
    test_id: this.testId,
    started_at: this.startedAt,      // timestamp Unix (segundos)
    answers: { questionId: answer }   // objeto clave-valor
}
```

#### Results (`Vue/pages/Results.vue`)

**Muestra los resultados detallados del intento:**
- Resumen superior: nota (porcentaje), aprobado/suspenso (badge verde/rojo)
- Estadísticas: correctas, incorrectas, sin responder, tiempo empleado
- Detalle pregunta por pregunta:
  - Texto de la pregunta
  - Respuesta dada (resaltada en verde si correcta, rojo si incorrecta)
  - Todas las opciones con indicador visual de cuál es la correcta
  - Explicación de la pregunta (si `show_explanations` está activado)
- Botón para volver al test o a home

#### CreateTest (`Vue/pages/CreateTest.vue`)

**Formulario completo para crear tests:**

*Sección 1 — Información Básica:*
- Título (requerido, max 255)
- Descripción (textarea)
- Categoría (select dinámico)
- Dificultad (easy/medium/hard)

*Sección 2 — Configuración:*
- Tiempo límite (minutos, 0 = sin límite)
- Intentos máximos (-1 = ilimitados)
- Nota para aprobar (0-100, slider o input)
- Toggles: test público, barajar preguntas/respuestas, mostrar respuestas/explicaciones

*Sección 3 — Constructor de Preguntas:*
- Botones para añadir pregunta: "Opción Múltiple", "Verdadero/Falso", "Respuesta Corta"
- Cada pregunta tiene:
  - Selector de tipo (deshabilitado una vez creada)
  - Campo de contenido
  - Puntos (input numérico)
  - Explicación (textarea opcional)
  - Campos específicos según tipo:
    - **multiple_choice**: opciones dinámicas (añadir/eliminar), checkbox para marcar correcta
    - **true_false**: radio "Verdadero" / "Falso"
    - **short_answer**: campo de respuesta correcta
  - Botón eliminar pregunta

*Sección 4 — Publicación:*
- Botón "Guardar como borrador" → status='draft'
- Botón "Publicar" → status='published'

#### Profile (`Vue/pages/Profile.vue`)

**Perfil de usuario con 4 pestañas:**

| Pestaña | Contenido |
|---|---|
| **Tests Creados** | Lista de tests públicos del usuario con visualizaciones, preguntas, e intentos |
| **Actividad Reciente** | Timeline de acciones: tests creados y tests completados, con fechas |
| **Tests Completados** | Lista de tests realizados con nota obtenida y fecha |
| **Estadísticas** | Métricas numéricas: tests creados, completados, aprobados, nota media, racha actual. Barra de progreso para tasa de aprobados |

**Datos mostrados del perfil:**
- Avatar (placeholder si no tiene)
- Nombre
- Bio
- Rol (badge)
- Miembro desde (fecha)
- Email (solo visible en perfil propio)

#### NotFound (`Vue/pages/NotFound.vue`)

Página 404 simple con mensaje y enlace a home.

### 7.6 Componentes

#### AppHeader (`Vue/components/AppHeader.vue`)

Cabecera de la aplicación presente en todas las páginas.

**Elementos:**
- Logo "TestMaster" (enlace a home)
- Barra de búsqueda (ejecuta búsqueda redirigiendo a home con query)
- Botón de cambio de tema (claro/oscuro)
- Navegación condicional:
  - **Sin autenticación:** "Iniciar Sesión", "Registrarse"
  - **Con autenticación:** "Crear Test", "Mi Perfil", "Cerrar Sesión"

#### AppFooter (`Vue/components/AppFooter.vue`)

Pie de página con logo, copyright y enlaces placeholder (Sobre Nosotros, Contacto, Privacidad, Términos).

---

## 8. Lógica de Negocio

### 8.1 Creación de Tests

La creación de un test es una operación **transaccional** que involucra múltiples tablas:

```
DB::transaction
├── 1. Crear registro en 'tests'
│   └── time_limit: minutos × 60 → segundos
│   └── status: 'draft' o 'published'
│   └── published_at: now() si se publica
│
├── 2. Vincular categoría (si se especifica)
│   └── tests.categories()->attach(category_id)
│   └── Category::increment('tests_count')
│
├── 3. Crear preguntas (iterar array)
│   ├── Omitir preguntas con contenido vacío
│   ├── Asignar order_index secuencial
│   └── Por cada pregunta, según tipo:
│       │
│       ├── multiple_choice
│       │   └── Crear QuestionOption por cada opción
│       │       (content, is_correct, order_index)
│       │
│       ├── true_false
│       │   └── Crear 2 QuestionOption fijas:
│       │       "Verdadero" (is_correct según input)
│       │       "Falso"    (is_correct inverso)
│       │
│       └── short_answer
│           └── Crear 1 QuestionOption con la respuesta correcta
│               (content=respuesta, is_correct=true)
│
├── 4. Crear registro en 'test_stats' (todo a 0)
│
└── 5. Incrementar user_stats.tests_created del creador
```

### 8.2 Corrección Automática de Respuestas

Cuando un usuario envía un intento (`POST /api/attempts/submit`), el sistema corrige automáticamente cada respuesta:

#### Algoritmo de corrección

```
Para cada pregunta del test:
│
├── Si no se proporcionó respuesta (givenAnswer es null):
│   └── unanswered += 1, is_correct = false, points = 0
│
├── multiple_choice:
│   └── Buscar QuestionOption.id == givenAnswer
│   └── is_correct = option.is_correct
│
├── true_false:
│   └── Buscar la opción correcta del question (is_correct = true)
│   └── Mapear la respuesta del usuario:
│       "true"  → "Verdadero"
│       "false" → "Falso"
│   └── Comparar con correctOption.content
│
└── short_answer:
    └── Obtener todas las opciones correctas de la pregunta
    └── Comparar givenAnswer con cada opción (case-insensitive, trim)
    └── is_correct si coincide con alguna
```

#### Cálculo de nota

```
score = (pointsEarned / totalPoints) × 100

Donde:
- totalPoints = suma de question.points de todas las preguntas
- pointsEarned = suma de question.points de las correctas
- passed = score >= test.pass_score
```

#### Actualización de estadísticas

Tras guardar el intento y las respuestas:

```
UserStat:
├── tests_completed += 1
├── tests_passed += 1 (si aprobó)
└── average_score = promedio de todos los intentos completados del usuario

TestStat:
├── attempts_count += 1
├── completions_count += 1
└── average_score = promedio de todos los intentos completados del test
```

### 8.3 Sistema de Puntuación y Estadísticas

El sistema implementa gamificación a través de:

**Tablas de gamificación (definidas, parcialmente implementadas):**

| Concepto | Tabla | Estado |
|---|---|---|
| Puntos/nivel/experiencia | `user_points` | Tabla creada, no se actualiza en los controladores |
| Historial de puntos | `point_history` | Tabla creada, no se usa |
| Rachas (streaks) | `user_stats.current_streak` | Campo existe, no se calcula |
| Insignias | `user_stats.badges_earned` | Campo existe, no hay lógica |
| Estadísticas de usuario | `user_stats` | **Implementado parcialmente** (tests_created, tests_completed, tests_passed, average_score) |
| Estadísticas de test | `test_stats` | **Implementado parcialmente** (attempts_count, completions_count, average_score) |
| Rankings/leaderboard | — | No implementado |

### 8.4 Perfiles de Usuario

El perfil muestra información pública del usuario:

- **Datos personales:** nombre, avatar, bio, rol, fecha de registro. El email solo es visible por el propietario.
- **Tests creados:** solo tests públicos y publicados.
- **Tests completados:** últimos 20 intentos en tests públicos.
- **Actividad reciente:** combinación de creaciones y completados, máximo 30 items, ordenada por fecha descendente.
- **Estadísticas:** extraídas de `user_stats`.

---

## 9. Seeders y Datos de Prueba

### DatabaseSeeder

Crea 2 usuarios de prueba:

| Nombre | Email | Contraseña | Rol |
|---|---|---|---|
| Test User | `test@example.com` | `password` | `user` |
| Admin User | `admin@example.com` | `adminpassword` | `admin` |

### QuestionSeeder

Genera un conjunto completo de datos realistas:

**8 categorías creadas:**
| Categoría | Slug | Ícono | Color | Subcategoría de |
|---|---|---|---|---|
| Programación | programacion | fa-code | #3B82F6 | — |
| Laravel | laravel | fa-laravel | #FF2D20 | Programación |
| JavaScript | javascript | fa-js | #F7DF1E | Programación |
| Ciencias | ciencias | fa-flask | #10B981 | — |
| Historia | historia | fa-landmark | #8B5CF6 | — |
| Geografía | geografia | fa-globe | #06B6D4 | — |
| Bases de Datos | bases-de-datos | fa-database | #F59E0B | Programación |
| Inglés | ingles | fa-language | #EC4899 | — |

**8 tests con 87 preguntas totales**, cubriendo los 3 tipos implementados (multiple_choice, true_false, short_answer). Cada test tiene datos realistas como contadores de vistas, intentos y notas medias.

---

## 10. Funcionalidades Planificadas (No Implementadas)

Las siguientes tablas y modelos existen en la base de datos pero **no tienen controladores, endpoints ni interfaz de usuario**:

| Funcionalidad | Tablas involucradas | Estado |
|---|---|---|
| **Sistema de seguidores** | `follows` | Modelo con relaciones, sin API/UI |
| **Comentarios en tests** | `comments` | Modelo con relaciones, sin API/UI |
| **Valoraciones (estrellas)** | `ratings` | Modelo con relaciones, sin API/UI |
| **Grupos/clases** | `groups`, `group_members`, `group_tests` | Modelo con relaciones, sin API/UI |
| **Notificaciones** | `notifications` | Tabla creada, sin modelo/API/UI |
| **Reportes de contenido** | `reports` | Tabla creada, sin modelo/API/UI |
| **Historial de puntos** | `point_history` | Tabla creada, sin modelo/API/UI |
| **Sistema de configuración** | `settings` | Tabla creada, sin modelo/API/UI |
| **Logs de actividad** | `activity_logs` | Tabla creada, sin modelo/API/UI |
| **Multimedia en preguntas** | `questions.image_url/audio_url/video_url` | Campos en BD, sin upload ni presentación |
| **Gamificación avanzada** | `user_points`, `point_history` | Tablas creadas, sin cálculos |

---

## 11. Notas de Seguridad

| Aspecto | Estado | Descripción |
|---|---|---|
| **Autenticación** | ✅ Correcta | Sanctum con tokens Bearer, bcrypt para hashing |
| **Ocultación de respuestas** | ✅ Correcta | `is_correct` se elimina de las opciones enviadas al cliente (`questions` endpoint) |
| **Autorización de resultados** | ✅ Correcta | Solo el dueño del intento puede ver sus resultados |
| **Validación de inputs** | ✅ Correcta | Validaciones en todos los controladores con reglas Laravel |
| **Transacciones** | ✅ Correcta | Operaciones críticas (crear test, enviar intento) usan `DB::transaction` |
| **Reset de contraseña** | ⚠️ Inseguro | Permite cambiar contraseña solo con email, sin verificación por token |
| **CORS** | ⚠️ Configurar | Depende de la configuración de Sanctum/CORS |
| **Rate limiting** | ⚠️ No hay | No se aplica rate limiting a login/register |
| **Roles de usuario** | ⚠️ Sin uso | Los roles existen (user/premium/educator/admin) pero no hay middleware de permisos |
| **Inyección SQL** | ✅ Protegido | Eloquent ORM parametriza las consultas automáticamente |
| **XSS** | ✅ Protegido | Vue.js escapa el contenido por defecto en templates |

---

> **Documento generado para el equipo de desarrollo de TestMaster.**
> Última actualización: Febrero 2026
