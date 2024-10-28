# Set the base image
FROM webdevops/php-apache-dev:7.4

# Set the working directory in Docker
WORKDIR /var/www/html

# Copy the existing application directory contents
COPY . /var/www/html

# Expose ports 80, 443
EXPOSE 80
EXPOSE 443

ARG uid
RUN if id "devuser" &>/dev/null; then \
        echo "User devuser already exists"; \
    else \
        useradd -G www-data,root -u $uid -d /home/devuser devuser && \
        mkdir -p /home/devuser/.composer && \
        chown -R devuser:devuser /home/devuser; \
    fi
