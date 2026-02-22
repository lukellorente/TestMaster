# 📊 Estructura de Base de Datos - TestMaster

## 📋 Tabla de Contenidos
1. [Gestión de Usuarios](#usuarios)
2. [Tests y Preguntas](#tests-y-preguntas)
3. [Intentos y Respuestas](#intentos-y-respuestas)
4. [Categorías y Estadísticas](#categorías-y-estadísticas)
5. [Comunidad y Interacciones](#comunidad-e-interacciones)
6. [Grupos](#grupos)
7. [Sistema de Puntos](#sistema-de-puntos)
8. [Configuración](#configuración)

---

## 👥 Usuarios

### `users`
Tabla principal de usuarios del sistema.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT UNSIGNED | ID único (PK) |
| `email` | VARCHAR(255) | Email único del usuario |
| `password_hash` | VARCHAR(255) | Contraseña hasheada con bcrypt |
| `name` | VARCHAR(100) | Nombre completo |
| `avatar` | VARCHAR(255) | URL del avatar |
| `bio` | TEXT | Biografía del usuario |
| `role` | ENUM | Rol: user, premium, educator, admin |
| `email_verified_at` | TIMESTAMP | Fecha de verificación del email |
| `created_at` | TIMESTAMP | Fecha de creación |
| `updated_at` | TIMESTAMP | Última actualización |
| `last_login` | TIMESTAMP | Último inicio de sesión |

**Índices**: `id` (PK), `email` (UNIQUE)

---

### `user_points`
Gestión de puntos y nivel de usuarios.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT UNSIGNED | ID único (PK) |
| `user_id` | INT UNSIGNED | FK a users |
| `points` | INT | Puntos totales acumulados |
| `level` | INT | Nivel actual del usuario |
| `experience` | INT | Experiencia acumulada |
| `streak_days` | INT | Días de racha consecutivos |
| `last_activity_date` | DATE | Última fecha de actividad |
| `created_at` | TIMESTAMP | Fecha de creación |
| `updated_at` | TIMESTAMP | Última actualización |

**Índices**: `user_id` (UNIQUE)

---

### `user_stats`
Estadísticas y logros del usuario.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT UNSIGNED | ID único (PK) |
| `user_id` | INT UNSIGNED | FK a users (UNIQUE) |
| `tests_created` | INT UNSIGNED | Cantidad de tests creados |
| `tests_completed` | INT UNSIGNED | Tests completados |
| `tests_passed` | INT UNSIGNED | Tests aprobados |
| `average_score` | DECIMAL(5,2) | Promedio de calificación |
| `total_study_time` | INT UNSIGNED | Tiempo total de estudio (segundos) |
| `perfect_scores` | INT UNSIGNED | Cantidad de 100% |
| `current_streak` | INT UNSIGNED | Racha actual |
| `longest_streak` | INT UNSIGNED | Racha más larga |
| `total_points` | INT | Puntos totales |
| `badges_earned` | INT UNSIGNED | Insignias ganadas |
| `followers_count` | INT UNSIGNED | Seguidores |
| `following_count` | INT UNSIGNED | Siguiendo |
| `updated_at` | TIMESTAMP | Última actualización |

---

## 📝 Tests y Preguntas

### `tests`
Tabla principal de tests/exámenes.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT UNSIGNED | ID único (PK) |
| `user_id` | INT UNSIGNED | FK a users (creador) |
| `title` | VARCHAR(255) | Título del test |
| `description` | TEXT | Descripción detallada |
| `is_public` | TINYINT | ¿Es público? (0/1) |
| `is_featured` | TINYINT | ¿Es destacado? (0/1) |
| `password` | VARCHAR(255) | Contraseña opcional |
| `time_limit` | INT UNSIGNED | Límite de tiempo (segundos) |
| `max_attempts` | INT | Máximo intentos (-1 = ilimitado) |
| `pass_score` | INT | Porcentaje requerido para aprobar (default: 70) |
| `shuffle_questions` | TINYINT | ¿Mezclar preguntas? (0/1) |
| `shuffle_answers` | TINYINT | ¿Mezclar respuestas? (0/1) |
| `show_correct_answers` | TINYINT | ¿Mostrar respuestas correctas? (0/1) |
| `show_explanations` | TINYINT | ¿Mostrar explicaciones? (0/1) |
| `difficulty` | ENUM | Dificultad: easy, medium, hard |
| `status` | ENUM | Estado: draft, published, archived |
| `views_count` | INT UNSIGNED | Número de vistas |
| `attempts_count` | INT UNSIGNED | Número de intentos |
| `average_score` | DECIMAL(5,2) | Calificación promedio |
| `created_at` | TIMESTAMP | Fecha de creación |
| `updated_at` | TIMESTAMP | Última actualización |
| `published_at` | TIMESTAMP | Fecha de publicación |

**Índices**: `id` (PK), `user_id`, `is_public`, `status`, `difficulty`

---

### `questions`
Preguntas dentro de los tests.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT UNSIGNED | ID único (PK) |
| `test_id` | INT UNSIGNED | FK a tests |
| `type` | ENUM | Tipo: multiple_choice, true_false, short_answer, fill_blank, drag_drop, order_items, numeric, image_hotspot |
| `content` | TEXT | Contenido de la pregunta |
| `explanation` | TEXT | Explicación de la respuesta |
| `points` | INT | Puntos si se responde correctamente |
| `order_index` | INT | Orden dentro del test |
| `image_url` | VARCHAR(255) | URL de imagen asociada |
| `audio_url` | VARCHAR(255) | URL de audio |
| `video_url` | VARCHAR(255) | URL de video |
| `time_limit` | INT UNSIGNED | Límite de tiempo para esta pregunta |
| `created_at` | TIMESTAMP | Fecha de creación |
| `updated_at` | TIMESTAMP | Última actualización |

**Índices**: `id` (PK), `test_id`, `type`

---

### `question_options`
Opciones de respuesta para preguntas de opción múltiple.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT UNSIGNED | ID único (PK) |
| `question_id` | INT UNSIGNED | FK a questions |
| `content` | TEXT | Contenido de la opción |
| `is_correct` | TINYINT | ¿Es la respuesta correcta? (0/1) |
| `order_index` | INT | Orden de visualización |
| `image_url` | VARCHAR(255) | URL de imagen (opcional) |
| `created_at` | TIMESTAMP | Fecha de creación |

---

### `question_blanks`
Espacios en blanco para preguntas de rellenar.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT UNSIGNED | ID único (PK) |
| `question_id` | INT UNSIGNED | FK a questions |
| `blank_index` | INT | Índice del espacio en blanco |
| `correct_answers` | TEXT (JSON) | Array JSON de respuestas correctas |
| `case_sensitive` | TINYINT | ¿Distinguir mayúsculas? (0/1) |
| `created_at` | TIMESTAMP | Fecha de creación |

---

### `question_order_items`
Elementos para preguntas de ordenamiento.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT UNSIGNED | ID único (PK) |
| `question_id` | INT UNSIGNED | FK a questions |
| `content` | TEXT | Contenido del elemento |
| `correct_order` | INT | Orden correcto (posición) |
| `created_at` | TIMESTAMP | Fecha de creación |

---

### `question_stats`
Estadísticas de rendimiento por pregunta.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT UNSIGNED | ID único (PK) |
| `question_id` | INT UNSIGNED | FK a questions (UNIQUE) |
| `times_answered` | INT UNSIGNED | Veces que fue respondida |
| `times_correct` | INT UNSIGNED | Veces respondida correctamente |
| `times_incorrect` | INT UNSIGNED | Veces respondida incorrectamente |
| `difficulty_score` | DECIMAL(5,2) | Puntuación de dificultad |
| `average_time` | INT UNSIGNED | Tiempo promedio de respuesta |
| `updated_at` | TIMESTAMP | Última actualización |

---

## 🎯 Intentos y Respuestas

### `test_attempts`
Registro de intentos de resolver tests.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT UNSIGNED | ID único (PK) |
| `user_id` | INT UNSIGNED | FK a users |
| `test_id` | INT UNSIGNED | FK a tests |
| `score` | DECIMAL(5,2) | Calificación en porcentaje |
| `points_earned` | INT | Puntos obtenidos |
| `total_points` | INT | Puntos totales posibles |
| `correct_answers` | INT | Respuestas correctas |
| `incorrect_answers` | INT | Respuestas incorrectas |
| `unanswered` | INT | Preguntas sin responder |
| `time_spent` | INT UNSIGNED | Tiempo invertido (segundos) |
| `is_completed` | TINYINT | ¿Completado? (0/1) |
| `passed` | TINYINT | ¿Aprobado? (0/1) |
| `started_at` | TIMESTAMP | Fecha/hora de inicio |
| `completed_at` | TIMESTAMP | Fecha/hora de finalización |
| `ip_address` | VARCHAR(45) | IP del usuario |
| `user_agent` | TEXT | User agent del navegador |

**Índices**: `id` (PK), `user_id`, `test_id`, `is_completed`

---

### `question_responses`
Respuestas individuales a cada pregunta.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT UNSIGNED | ID único (PK) |
| `attempt_id` | INT UNSIGNED | FK a test_attempts |
| `question_id` | INT UNSIGNED | FK a questions |
| `given_answer` | TEXT (JSON) | Respuesta dada (JSON) |
| `is_correct` | TINYINT | ¿Es correcta? (0/1) |
| `points_earned` | INT | Puntos obtenidos |
| `time_spent` | INT UNSIGNED | Tiempo invertido (segundos) |
| `created_at` | TIMESTAMP | Fecha de creación |

---

## 📂 Categorías y Estadísticas

### `categories`
Categorías de tests.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT UNSIGNED | ID único (PK) |
| `name` | VARCHAR(100) | Nombre de la categoría (UNIQUE) |
| `slug` | VARCHAR(100) | Slug para URL (UNIQUE) |
| `description` | TEXT | Descripción |
| `icon` | VARCHAR(50) | Emoji o icono |
| `color` | VARCHAR(7) | Color hex (#RRGGBB) |
| `parent_id` | INT UNSIGNED | FK a categories (para subcategorías) |
| `tests_count` | INT UNSIGNED | Cantidad de tests |
| `created_at` | TIMESTAMP | Fecha de creación |

---

### `test_categories`
Relación muchos-a-muchos entre tests y categorías.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `test_id` | INT UNSIGNED | FK a tests (PK1) |
| `category_id` | INT UNSIGNED | FK a categories (PK2) |

---

### `test_stats`
Estadísticas agregadas de cada test.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT UNSIGNED | ID único (PK) |
| `test_id` | INT UNSIGNED | FK a tests (UNIQUE) |
| `views_count` | INT UNSIGNED | Número de vistas |
| `attempts_count` | INT UNSIGNED | Número de intentos |
| `completions_count` | INT UNSIGNED | Completaciones exitosas |
| `average_score` | DECIMAL(5,2) | Calificación promedio |
| `average_time` | INT UNSIGNED | Tiempo promedio (segundos) |
| `pass_rate` | DECIMAL(5,2) | Tasa de aprobación (%) |
| `difficulty_rating` | DECIMAL(3,2) | Calificación de dificultad |
| `rating_avg` | DECIMAL(3,2) | Promedio de valoración |
| `ratings_count` | INT UNSIGNED | Cantidad de valoraciones |
| `comments_count` | INT UNSIGNED | Cantidad de comentarios |
| `bookmarks_count` | INT UNSIGNED | Cantidad de marcadores |
| `updated_at` | TIMESTAMP | Última actualización |

---

## 👥 Comunidad e Interacciones

### `comments`
Comentarios en tests.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT UNSIGNED | ID único (PK) |
| `user_id` | INT UNSIGNED | FK a users |
| `test_id` | INT UNSIGNED | FK a tests |
| `parent_id` | INT UNSIGNED | FK a comments (para respuestas) |
| `content` | TEXT | Contenido del comentario |
| `is_edited` | TINYINT | ¿Fue editado? (0/1) |
| `is_hidden` | TINYINT | ¿Está oculto? (0/1) |
| `created_at` | TIMESTAMP | Fecha de creación |
| `updated_at` | TIMESTAMP | Última actualización |

**Índices**: `id` (PK), `test_id`, `user_id`, `parent_id`

---

### `ratings`
Valoraciones/reseñas de tests.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT UNSIGNED | ID único (PK) |
| `user_id` | INT UNSIGNED | FK a users |
| `test_id` | INT UNSIGNED | FK a tests |
| `rating` | TINYINT | Valoración 1-5 |
| `created_at` | TIMESTAMP | Fecha de creación |
| `updated_at` | TIMESTAMP | Última actualización |

**Índices**: `unique_user_test` (user_id, test_id)

---

### `follows`
Sistema de seguimiento entre usuarios.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `follower_id` | INT UNSIGNED | Usuario que sigue (FK a users, PK1) |
| `following_id` | INT UNSIGNED | Usuario siendo seguido (FK a users, PK2) |
| `created_at` | TIMESTAMP | Fecha de creación |

---

### `notifications`
Sistema de notificaciones.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT UNSIGNED | ID único (PK) |
| `user_id` | INT UNSIGNED | FK a users |
| `type` | ENUM | Tipo: test_comment, new_follower, badge_earned, group_invite, test_shared, system |
| `title` | VARCHAR(255) | Título de la notificación |
| `content` | TEXT | Contenido |
| `data` | TEXT (JSON) | Datos adicionales (JSON) |
| `is_read` | TINYINT | ¿Leída? (0/1) |
| `related_id` | INT UNSIGNED | ID del recurso relacionado |
| `created_at` | TIMESTAMP | Fecha de creación |

**Índices**: `id` (PK), `user_id`, `is_read`

---

### `reports`
Sistema de reportes/denuncias.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT UNSIGNED | ID único (PK) |
| `reporter_id` | INT UNSIGNED | FK a users (quien reporta) |
| `reportable_type` | ENUM | Tipo: test, comment, user |
| `reportable_id` | INT UNSIGNED | ID del recurso reportado |
| `reason` | ENUM | Razón: spam, inappropriate, copyright, other |
| `description` | TEXT | Descripción del reporte |
| `status` | ENUM | Estado: pending, reviewing, resolved, dismissed |
| `reviewed_by` | INT UNSIGNED | FK a users (quien revisa) |
| `reviewed_at` | TIMESTAMP | Fecha de revisión |
| `created_at` | TIMESTAMP | Fecha de creación |

---

## 👥 Grupos

### `groups`
Grupos de usuarios/clases.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT UNSIGNED | ID único (PK) |
| `owner_id` | INT UNSIGNED | FK a users (propietario) |
| `name` | VARCHAR(100) | Nombre del grupo |
| `description` | TEXT | Descripción |
| `avatar` | VARCHAR(255) | URL del avatar |
| `is_private` | TINYINT | ¿Es privado? (0/1) |
| `join_code` | VARCHAR(20) | Código para unirse (UNIQUE) |
| `max_members` | INT | Miembros máximos |
| `members_count` | INT | Miembros actuales |
| `created_at` | TIMESTAMP | Fecha de creación |
| `updated_at` | TIMESTAMP | Última actualización |

---

### `group_members`
Miembros de un grupo.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT UNSIGNED | ID único (PK) |
| `group_id` | INT UNSIGNED | FK a groups |
| `user_id` | INT UNSIGNED | FK a users |
| `role` | ENUM | Rol: owner, admin, member |
| `joined_at` | TIMESTAMP | Fecha de unión |

**Índices**: `unique_group_user` (group_id, user_id)

---

### `group_tests`
Tests asignados a un grupo.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT UNSIGNED | ID único (PK) |
| `group_id` | INT UNSIGNED | FK a groups |
| `test_id` | INT UNSIGNED | FK a tests |
| `added_by` | INT UNSIGNED | FK a users (quien agregó) |
| `added_at` | TIMESTAMP | Fecha de adición |

**Índices**: `unique_group_test` (group_id, test_id)

---

## 🏆 Sistema de Puntos

### `point_history`
Historial de cambios de puntos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT UNSIGNED | ID único (PK) |
| `user_id` | INT UNSIGNED | FK a users |
| `points` | INT | Puntos agregados/restados |
| `reason` | ENUM | Razón: test_completed, test_created, perfect_score, streak, badge_earned, other |
| `description` | TEXT | Descripción adicional |
| `related_id` | INT UNSIGNED | ID del recurso relacionado |
| `created_at` | TIMESTAMP | Fecha de creación |

---

## ⚙️ Configuración

### `settings`
Configuración general del sistema.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT UNSIGNED | ID único (PK) |
| `key` | VARCHAR(100) | Clave única (UNIQUE) |
| `value` | TEXT | Valor de la configuración |
| `type` | ENUM | Tipo: string, integer, boolean, json |
| `description` | TEXT | Descripción |
| `updated_at` | TIMESTAMP | Última actualización |

---

### `activity_logs`
Registro de actividades del sistema.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT UNSIGNED | ID único (PK) |
| `user_id` | INT UNSIGNED | FK a users |
| `action` | ENUM | Acción: create_test, edit_test, delete_test, attempt_test, login, register, other |
| `description` | TEXT | Descripción de la acción |
| `ip_address` | VARCHAR(45) | Dirección IP |
| `user_agent` | TEXT | User agent del navegador |
| `metadata` | TEXT (JSON) | Datos adicionales (JSON) |
| `created_at` | TIMESTAMP | Fecha de creación |

---

### `password_resets`
Tokens para reseteo de contraseñas.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT UNSIGNED | ID único (PK) |
| `user_id` | INT UNSIGNED | FK a users |
| `token` | VARCHAR(255) | Token único |
| `expires_at` | TIMESTAMP | Fecha de expiración |
| `created_at` | TIMESTAMP | Fecha de creación |

---

## 📊 Relaciones Principales

```
users (1) ──→ (N) tests
users (1) ──→ (N) test_attempts
users (1) ──→ (N) comments
users (1) ──→ (N) ratings
users (1) ──→ (1) user_points
users (1) ──→ (1) user_stats

tests (1) ──→ (N) questions
tests (1) ──→ (N) test_attempts
tests (1) ──→ (N) comments
tests (1) ──→ (N) ratings
tests (M) ──→ (N) categories (via test_categories)
tests (1) ──→ (1) test_stats

questions (1) ──→ (N) question_options
questions (1) ──→ (N) question_blanks
questions (1) ──→ (N) question_order_items
questions (1) ──→ (1) question_stats

test_attempts (1) ──→ (N) question_responses

groups (1) ──→ (N) group_members
groups (1) ──→ (N) group_tests

users (M) ──→ (N) users (via follows)
users (1) ──→ (N) notifications
```

---

## 📌 Notas Importantes

- **Timestamps**: Todas las tablas tienen `created_at` y muchas tienen `updated_at`
- **Soft Deletes**: No hay implementación de soft deletes, los registros se eliminan con CASCADE
- **JSON**: Algunos campos usan formato JSON para datos complejos
- **Enumerables**: Se usan ENUM para campos con valores predefinidos
- **Índices**: Están optimizados para búsquedas frecuentes (user_id, test_id, status)
- **Relaciones**: Todas las FK tienen restricciones CASCADE para mantener integridad

