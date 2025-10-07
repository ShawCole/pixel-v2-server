server {
    include /etc/nginx/snippets/server_names_hook.conf;
    root /var/www/pixel-v2/public;
    index webhook.php;
    location /health { return 200 "ok\n"; add_header Content-Type text/plain; }
    location ~ /\.env { deny all; }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }
}
