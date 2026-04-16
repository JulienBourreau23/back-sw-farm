# SW Farm — Backend Symfony

## Installation sur le LXC

```bash
# 1. Prérequis
apt update && apt install -y php8.2 php8.2-fpm php8.2-pgsql php8.2-xml \
    php8.2-mbstring php8.2-curl php8.2-intl php8.2-zip nginx composer

# 2. Cloner le projet
cd /opt
git clone git@github.com:ton-repo/sw-farm-backend.git symfony-sw
cd symfony-sw

# 3. Dépendances
composer install --no-dev --optimize-autoloader

# 4. Configuration
cp .env.example .env
nano .env  # Remplir les vraies valeurs

# 5. Générer les clés JWT
mkdir -p config/jwt
php bin/console lexik:jwt:generate-keypair

# 6. Vérifier la config
php bin/console debug:config security
php bin/console debug:router

# 7. Lancer (dev)
php -S 0.0.0.0:8000 -t public/
```

## Nginx (prod)

```nginx
server {
    listen 80;
    server_name api.tondomaine.com;
    root /opt/symfony-sw/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }
}
```

## Endpoints

| Méthode | URL | Auth | Description |
|---------|-----|------|-------------|
| POST | /auth/login | Public | Login → JWT |
| POST | /auth/refresh | Public | Refresh JWT |
| POST | /auth/logout | Public | Logout |
| POST | /auth/forgot-password | Public | Email reset mdp |
| POST | /auth/reset-password | Public | Reset mdp |
| GET | /admin/users | ROLE_ADMIN | Lister users |
| POST | /admin/users | ROLE_ADMIN | Créer user |
| PUT | /admin/users/{id} | ROLE_ADMIN | Modifier user |
| DELETE | /admin/users/{id} | ROLE_ADMIN | Supprimer user |
| POST | /runes/import | ROLE_USER | Import JSON SW |
| GET | /runes/averages | ROLE_USER | Moyennes substats |

## Documentation Swagger

http://IP_LXC_SYMFONY:8000/api/doc
```
