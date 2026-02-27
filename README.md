
# TestMaster_Laravel-Vue

TestMaster es una plataforma web integral para la gestión, creación y resolución de exámenes en línea. Está desarrollada con **Laravel** (backend) y **Vue.js** (frontend SPA), permitiendo una experiencia moderna, rápida y segura tanto para administradores como para usuarios finales.

---

## Tabla de Contenidos

1. [Descripción General](#descripción-general)
2. [Características Principales](#características-principales)
3. [Tecnologías Utilizadas](#tecnologías-utilizadas)
4. [Estructura del Proyecto](#estructura-del-proyecto)
5. [Instalación y Configuración](#instalación-y-configuración)
6. [Flujo de Trabajo](#flujo-de-trabajo)
7. [Ejemplos de Uso](#ejemplos-de-uso)
8. [Scripts Útiles](#scripts-útiles)
9. [Contribución](#contribución)
10. [Licencia](#licencia)

---

## Descripción General

TestMaster permite a los usuarios crear, resolver y administrar tests de diferentes tipos, con funcionalidades de gamificación, estadísticas avanzadas y gestión de comunidades. Es ideal para instituciones educativas, academias, empresas o cualquier entorno donde se requiera evaluar conocimientos de manera flexible y escalable.

## Características Principales

- **Gestión de usuarios:** Registro, inicio de sesión, recuperación de contraseña, roles (admin, usuario), estadísticas individuales.
- **Creación de tests:**
	- Preguntas de opción múltiple, respuesta corta, ordenamiento, completar huecos, etc.
	- Soporte para categorías y niveles de dificultad.
	- Edición y clonación de tests.
- **Intentos y resultados:**
	- Los usuarios pueden realizar varios intentos.
	- Visualización de resultados detallados y respuestas correctas/incorrectas.
	- Estadísticas por test, usuario y grupo.
- **Comunidades y grupos:**
	- Creación y gestión de grupos de usuarios.
	- Estadísticas y rankings por grupo.
- **API RESTful:**
	- Endpoints seguros para todas las operaciones principales.
	- Autenticación con tokens (Laravel Sanctum).
- **Frontend moderno:**
	- SPA con Vue.js, navegación fluida y componentes reutilizables.
	- Responsive: usable en móvil, tablet y escritorio.

## Tecnologías Utilizadas

- **Backend:** PHP 8+, Laravel 10+
- **Frontend:** Vue.js 3, Vite
- **Base de datos:** MySQL/MariaDB (configurable)
- **Autenticación:** Laravel Sanctum
- **Testing:** PHPUnit, Laravel Test
- **Otros:** Composer, NPM, Docker (opcional)

## Estructura del Proyecto

- `/app`, `/routes`, `/database`: Lógica y modelos de Laravel
- `/public`: Archivos públicos y punto de entrada web
- `/resources`: Vistas Blade, JS y CSS (legacy)
- `/Vue`: Aplicación Vue.js (SPA)
- `/PHPOriginal`: Código legado o de referencia
- `/tests`: Pruebas unitarias y funcionales

### Estructura de carpetas relevante

```
app/Http/Controllers/         # Controladores de la API y web
app/Models/                   # Modelos Eloquent (User, Test, Question, etc.)
database/migrations/          # Migraciones de base de datos
database/seeders/             # Seeders para datos de ejemplo
routes/api.php                # Rutas de la API REST
Vue/                          # Código fuente del frontend SPA
public/                       # Archivos públicos y build frontend
```

## Instalación y Configuración

### 1. Clonar el repositorio

```bash
git clone https://github.com/HiImGhost666/TestMaster_Laravel-Vue.git
cd TestMaster_Laravel-Vue
```

### 2. Backend (Laravel)

```bash
composer install
cp .env.example .env
php artisan key:generate
# Configura tu base de datos en el archivo .env
php artisan migrate --seed
```

### 3. Frontend (Vue.js)

```bash
cd Vue
npm install
npm run dev
```

### 4. Iniciar el servidor

```bash
php artisan serve
```
Accede a [http://localhost:8000](http://localhost:8000) o según la configuración.

## Flujo de Trabajo

1. **Registro e inicio de sesión:** Los usuarios se registran y acceden a la plataforma.
2. **Creación de tests:** Los administradores o usuarios autorizados crean tests y preguntas desde el panel.
3. **Resolución de tests:** Los usuarios seleccionan un test, lo resuelven y envían sus respuestas.
4. **Visualización de resultados:** El sistema muestra resultados detallados, respuestas correctas y estadísticas.
5. **Gestión de grupos:** Los usuarios pueden unirse o crear grupos/comunidades para competir o colaborar.

## Ejemplos de Uso

- **Crear un test:**
	1. Accede como administrador.
	2. Ve a la sección "Tests" y haz clic en "Crear nuevo test".
	3. Añade preguntas, opciones y guarda.
- **Resolver un test:**
	1. Inicia sesión como usuario.
	2. Selecciona un test disponible y haz clic en "Comenzar".
	3. Responde las preguntas y envía.
	4. Consulta tu resultado y repite si lo deseas.
- **Ver estadísticas:**
	1. Accede a tu perfil o al panel de grupo.
	2. Visualiza tus intentos, medias, etc.

## Scripts Útiles

- `php artisan migrate:fresh --seed` — Reinicia la base de datos y la rellena con datos de ejemplo.
- `npm run dev` — Inicia el frontend en modo desarrollo (hot reload).
- `php artisan test` — Ejecuta los tests automáticos del backend.

## Contribución

¡Las contribuciones son bienvenidas! Para colaborar:

1. Haz un fork del repositorio.
2. Crea una rama para tu feature o fix (`git checkout -b mi-feature`).
3. Realiza tus cambios y añade pruebas si es necesario.
4. Haz commit y push a tu fork.
5. Abre un Pull Request detallando tus cambios.

Por favor, sigue las buenas prácticas de código y añade documentación cuando sea necesario.

## Licencia

MIT. Consulta el archivo LICENSE para más detalles.

---

**TestMaster** — La forma más completa, sencilla y potente de gestionar tus exámenes en línea.
