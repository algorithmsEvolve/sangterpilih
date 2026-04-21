FROM php:8.3-fpm-alpine

# Install system dependencies termasuk Node.js dan npm
RUN apk add --no-cache \
    curl \
    git \
    libpng-dev \
    libxml2-dev \
    sqlite-dev \
    zip \
    unzip \
    nodejs \
    npm \
    linux-headers \
    ${PHPIZE_DEPS}

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql pdo_sqlite bcmath pcntl gd

# Install ekstensi C-Redis dilewati karena koneksi PECL sedang bermasalah dan kita murni menggunakan predis (PHP)

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Hak akses pengguna (Opsional untuk lingkungan eksekusi tertentu)
RUN chown -R www-data:www-data /var/www

# Buka akses port (bisa 8000 untuk Artisan, Vite, dan Reverb)
EXPOSE 8000 8080 5173
