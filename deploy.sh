#!/bin/bash
#
# Despliegue de producción con compose.prod.yaml (app + MySQL + Caddy).
# Si un paso falla, el script continúa, pero al final lista los fallos y sale con código 1.

cd "$(dirname "$0")" || exit 1

COMPOSE="docker compose -f compose.prod.yaml"
# Los comandos se ejecutan como el usuario "sail" (el mismo que sirve la app): si se
# ejecutan como root, los logs y cachés quedan con dueño root y la web da error 500.
APP="$COMPOSE exec -T -u sail laravel.test"
FAILED=()

step() {
    local name="$1"; shift
    echo "▶️  $name..."
    if ! "$@"; then
        echo "❌ Error en: $name (continuando...)"
        FAILED+=("$name")
    fi
}

# ─── Comprobaciones previas ────────────────────────────────────────────
if [[ ! -f .env ]]; then
    echo "❌ No existe .env. Cópialo de .env.example y configúralo antes de desplegar."
    exit 1
fi
if ! grep -qE '^APP_DOMAIN=.+' .env; then
    echo "❌ APP_DOMAIN no está definido en .env (Caddy lo necesita para el certificado HTTPS)."
    exit 1
fi

echo "⚠️  Esto va a reiniciar los contenedores y ejecutar comandos dentro."
read -p "¿Seguro que quieres continuar? (y/n): " confirm
if [[ "$confirm" != "y" ]]; then
    echo "Cancelado."
    exit 1
fi

# ─── Contenedores ─────────────────────────────────────────────────────
step "Parar contenedores" $COMPOSE down
step "Construir y levantar contenedores" $COMPOSE up -d --build

echo "⏳ Esperando a que MySQL esté listo (máx. 3 min)..."
# Se usa el healthcheck del servicio: en el primer arranque MySQL se reinicia tras
# inicializarse, y un simple ping puede responder antes de tiempo.
MYSQL_READY=false
for _ in $(seq 1 90); do
    cid=$($COMPOSE ps -q mysql)
    status=$([[ -n "$cid" ]] && docker inspect -f '{{.State.Health.Status}}' "$cid" 2>/dev/null)
    if [[ "$status" == "healthy" ]]; then
        MYSQL_READY=true
        break
    fi
    sleep 2
done

if [[ "$MYSQL_READY" != true ]]; then
    echo "❌ MySQL no está listo tras 3 minutos. Revisa: $COMPOSE logs mysql"
    exit 1
fi
echo "✅ MySQL está listo."

# ─── Aplicación ───────────────────────────────────────────────────────
step "Instalar dependencias PHP" $APP composer install --no-dev --optimize-autoloader --no-interaction

if ! grep -qE '^APP_KEY=.+' .env; then
    step "Generar APP_KEY (primera instalación)" $APP php artisan key:generate --force
fi

step "Limpiar caché Laravel" $APP php artisan optimize:clear
step "Ejecutar migraciones" $APP php artisan migrate --force
step "Enlazar storage (avatares)" $APP php artisan storage:link --force

# ─── Frontend ─────────────────────────────────────────────────────────
step "Instalar dependencias npm" $APP npm ci
step "Build de assets" $APP npm run build

# ─── Optimización ─────────────────────────────────────────────────────
step "Cachear configuración, rutas y vistas" $APP php artisan optimize

# ─── Resumen ──────────────────────────────────────────────────────────
echo
if [[ ${#FAILED[@]} -eq 0 ]]; then
    echo "✅ Despliegue completado sin errores."
else
    echo "⚠️  Despliegue terminado con ${#FAILED[@]} error(es):"
    printf '   - %s\n' "${FAILED[@]}"
    exit 1
fi
