# Sử dụng image PHP 8.2 chính thức
FROM php:8.2-fpm

# Cài đặt các extension cần thiết cho Laravel
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    curl \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Cài composer (trình quản lý package PHP)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Đặt thư mục làm việc mặc định trong container
WORKDIR /var/www

# Copy toàn bộ mã nguồn vào container
COPY . .

# Cấp quyền cho thư mục storage và bootstrap/cache
RUN chmod -R 777 storage bootstrap/cache

# Mở cổng 9000 để php-fpm hoạt động
EXPOSE 9000

# Chạy php-fpm khi container khởi động
CMD ["php-fpm"]
