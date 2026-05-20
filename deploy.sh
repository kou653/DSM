#!/bin/bash
# =============================================================
# Script de déploiement DSM-back — LWS / api.dronekapp.com
# Lancer via SSH depuis le dossier racine de l'API (dsm-api/)
# =============================================================

set -e

echo "=== Déploiement DSM API — Production ==="

# 1. Vérifier que le .env de production est en place
if [ ! -f .env ]; then
    echo "[ERREUR] Fichier .env introuvable."
    echo "Copiez .env.production vers .env et remplissez les valeurs."
    exit 1
fi

# 2. Passer en mode maintenance
echo "[1/9] Activation du mode maintenance..."
php artisan down --retry=60

# 3. Installer les dépendances Composer (sans dev)
echo "[2/9] Installation des dépendances PHP..."
composer install --no-dev --optimize-autoloader --no-interaction

# 4. Générer la clé d'application si absente
if grep -q "APP_KEY=$" .env || grep -q "APP_KEY= $" .env; then
    echo "[3/9] Génération de la clé d'application..."
    php artisan key:generate --force
else
    echo "[3/9] Clé d'application déjà présente. OK."
fi

# 5. Exécuter les migrations
echo "[4/9] Exécution des migrations..."
php artisan migrate --force

# 6. Seeder (rôles + admin)
echo "[5/9] Seed des données initiales..."
php artisan db:seed --force

# 7. Créer le lien symbolique storage
echo "[6/9] Création du lien symbolique storage..."
php artisan storage:link --force

# 8. Optimisation du cache
echo "[7/9] Mise en cache de la configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 9. Permissions des dossiers
echo "[8/9] Réglage des permissions..."
chmod -R 755 storage bootstrap/cache
chmod -R 644 storage/logs

# 10. Désactiver le mode maintenance
echo "[9/9] Désactivation du mode maintenance..."
php artisan up

echo ""
echo "=== Déploiement terminé avec succès ! ==="
echo "API disponible sur : https://api.dronekapp.com"
