# Используем официальный образ PHP 5.6
FROM php:5.6-apache

# Включаем необходимые расширения
RUN docker-php-ext-install json mbstring

# Настраиваем php.ini
COPY php.ini /usr/local/etc/php/

# Копируем код в контейнер
COPY . /var/www/html/

# Устанавливаем права доступа
RUN chown -R www-data:www-data /var/www/html

# Открываем порт 80
EXPOSE 80
