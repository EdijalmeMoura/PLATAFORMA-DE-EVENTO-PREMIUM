# Plataforma de Evento Premium — imagem para Render (Web Service / Docker)
FROM php:8.2-apache

# Extensões usadas pelo app: PDO SQLite/MySQL, mbstring, curl, openssl
RUN apt-get update && apt-get install -y --no-install-recommends \
    libcurl4-openssl-dev \
  && docker-php-ext-install pdo pdo_mysql pdo_sqlite mbstring curl \
  && a2enmod rewrite headers \
  && rm -rf /var/lib/apt/lists/*

# Permite .htaccess (URLs amigáveis) e silencia aviso de ServerName
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
  && echo "ServerName localhost" >> /etc/apache2/apache2.conf

WORKDIR /var/www/html
COPY . /var/www/html/

# Pastas graváveis para banco SQLite, logs e uploads
RUN mkdir -p storage/logs uploads/general \
  && chown -R www-data:www-data storage uploads \
  && chmod -R 775 storage uploads

# Ajusta o Apache para a porta do Render ($PORT) e inicia
COPY docker-start.sh /usr/local/bin/docker-start.sh
RUN chmod +x /usr/local/bin/docker-start.sh

EXPOSE 10000
CMD ["docker-start.sh"]
