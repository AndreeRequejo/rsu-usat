# Docker - Guía de Inicio

# Primera ejecución

## 1. Crear el archivo de entorno de producción y key

```bash
cp .env.docker .env
php artisan key:generate
```

---

## 2. Construir las imágenes

```bash
docker compose build --no-cache
```

---

## 3. Levantar los contenedores

```bash
docker compose up -d
```

---

## 4. Verificar el estado

```bash
docker compose ps
```

Deberían aparecer los servicios:

* app
* nginx
* mysql

---

## 5. Generar APP_KEY

Si es la primera vez:

```bash
docker compose exec app php artisan key:generate
```

---

## 6. Limpiar caché

```bash
docker compose exec app php artisan optimize:clear
```

---

## 7. Ejecutar migraciones

```bash
docker compose exec app php artisan migrate
```

---

## 8. Ejecutar seeders

```bash
docker compose exec app php artisan db:seed
```

---

## 9. Acceder a la aplicación

Abrir:

```text
http://localhost:8080
```

---

# Comandos útiles

## Ver contenedores activos

```bash
docker compose ps
```

---

## Ver logs de todos los servicios

```bash
docker compose logs -f
```

---

## Ver logs de Laravel

```bash
docker compose logs -f app
```

---

## Ver logs de Nginx

```bash
docker compose logs -f nginx
```

---

## Ver logs de MySQL

```bash
docker compose logs -f mysql
```

---

## Entrar al contenedor Laravel

```bash
docker compose exec app sh
```

---

## Ejecutar Artisan

```bash
docker compose exec app php artisan
```

Ejemplos:

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan route:list
```

---

## Ejecutar Composer

```bash
docker compose exec app composer install
```

---

# Reiniciar servicios

## Reiniciar todos

```bash
docker compose restart
```

---

## Reiniciar Laravel

```bash
docker compose restart app
```

---

## Reiniciar Nginx

```bash
docker compose restart nginx
```

---

## Reiniciar MySQL

```bash
docker compose restart mysql
```

---

# Detener los contenedores

```bash
docker compose down
```

---

# Eliminar contenedores y volúmenes

```bash
docker compose down -v
```

---

# Reconstruir imágenes

```bash
docker compose down

docker builder prune -af

docker compose build --no-cache

docker compose up -d
```

---

# Solución de problemas

## Error: MissingAppKeyException

Generar la clave:

```bash
docker compose exec app php artisan key:generate
```

---

## Error 500

Limpiar caché:

```bash
docker compose exec app php artisan optimize:clear
```

Revisar logs:

```bash
docker compose exec app tail -100 storage/logs/laravel.log
```

---

## Error 502 Bad Gateway

Verificar estado:

```bash
docker compose ps
```

Revisar logs:

```bash
docker compose logs app
docker compose logs nginx
```

---

## Verificar conexión a MySQL

```bash
docker compose exec app php artisan migrate:status
```