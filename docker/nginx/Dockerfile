FROM nginx:alpine

# Copiar configuración personalizada de Nginx
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Copiar archivos estáticos (public) de Laravel
COPY --chown=nginx:nginx public /var/www/public

WORKDIR /var/www

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]