
# TestMaster_Laravel-Vue

TestMaster es una plataforma para la gestión, creación y resolución de tests en línea, desarrollada con Laravel (backend) y Vue.js (frontend). El objetivo es ofrecer una solución sencilla y eficiente para crear exámenes, gestionar usuarios y analizar resultados.

## Características principales

- **Gestión de usuarios:** Registro, inicio de sesión, roles y estadísticas.
- **Creación de tests:** Soporte para preguntas de opción múltiple, respuesta corta, ordenamiento y más.
- **Intentos y resultados:** Los usuarios pueden realizar tests, ver sus resultados y estadísticas.
- **Sistema de puntos y ranking:** Gamificación mediante puntos y clasificaciones.
- **Comunidades y grupos:** Organización de usuarios en grupos y comunidades.
- **API RESTful:** Backend robusto y seguro con Laravel.
- **Frontend moderno:** Interfaz intuitiva y responsiva con Vue.js.

## Estructura del proyecto

- **/app, /routes, /database:** Código backend (Laravel)
- **/public:** Archivos públicos y punto de entrada web
- **/resources:** Recursos frontend (Vistas, JS, CSS)
- **/Vue:** Aplicación Vue.js (SPA)
- **/PHPOriginal:** Código legado o de referencia

## Instalación rápida

1. **Clona el repositorio:**
	```bash
	git clone https://github.com/HiImGhost666/TestMaster_Laravel-Vue.git
	cd TestMaster_Laravel-Vue
	```
2. **Instala dependencias:**
	- Backend (Laravel):
	  ```bash
	  composer install
	  cp .env.example .env
	  php artisan key:generate
	  # Configura tu base de datos en .env
	  php artisan migrate --seed
	  ```
	- Frontend (Vue):
	  ```bash
	  cd Vue
	  npm install
	  npm run dev
	  ```
3. **Inicia el servidor:**
	```bash
	php artisan serve
	```
	Accede a [http://localhost:8000](http://localhost:8000) o según la configuración.

## Uso

- Accede a la web, regístrate o inicia sesión.
- Crea, edita o resuelve tests.
- Consulta estadísticas y rankings.
- Administra grupos y comunidades.

## Scripts útiles

- `php artisan migrate:fresh --seed` — Reinicia la base de datos con datos de ejemplo.
- `npm run dev` — Inicia el frontend en modo desarrollo.

## Contribución

1. Haz un fork del repositorio.
2. Crea una rama para tu feature o fix.
3. Haz tus cambios y abre un Pull Request.

## Licencia

MIT. Consulta el archivo LICENSE para más detalles.

---

**TestMaster** — Simple, directo y potente para gestionar tus tests en línea.
