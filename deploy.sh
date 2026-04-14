#!/bin/bash

echo "⚠️  Esto va a reiniciar los contenedores y ejecutar comandos dentro."
read -p "¿Seguro que quieres continuar? (y/n): " confirm

if [[ "$confirm" != "y" ]]; then
    echo "Cancelado."
    exit 1
fi

echo "🛑 Parando contenedores..."
docker compose -f compose.prod.yaml down || echo "❌ Error en down, continuando..."

echo "🚀 Levantando contenedores..."
docker compose -f compose.prod.yaml up -d --build || echo "❌ Error en up, continuando..."
echo "⏳ Esperando a que MySQL esté listo..."

until docker compose -f compose.prod.yaml exec mysql mysqladmin ping -h "localhost" --silent; do
    echo "⌛ MySQL aún no responde..."
    sleep 2
done

echo "✅ MySQL está listo."
echo "🧹 Limpiando caché Laravel..."
docker compose -f compose.prod.yaml exec laravel.test php artisan optimize:clear || echo "❌ Error en optimize:clear"

echo "🗄️ Ejecutando migraciones..."
docker compose -f compose.prod.yaml exec laravel.test php artisan migrate --force || echo "❌ Error en migrate"

echo "📦 Instalando dependencias npm..."
docker compose -f compose.prod.yaml exec laravel.test npm ci || echo "❌ Error en npm ci"

echo "🏗️ Build de assets..."
docker compose -f compose.prod.yaml exec laravel.test npm run build || echo "❌ Error en npm build"

echo "✅ Script terminado (aunque algo haya fallado, porque sí, lo pediste así)."
