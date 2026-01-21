**Le 88 Comptoir du Cheveu**

Site vitrine et e-boutique pour Le 88 Comptoir du Cheveu, salon de coiffure mixte avec prestations barbe et services associés.

Description
Ce projet propose un site complet pour un salon de coiffure classique : présentation du salon, prestations, avis clients, galerie photos et boutique en ligne.
​
Il inclut une gestion des avis publics, un formulaire de contact, une e-boutique avec paiement Stripe et un historique des commandes accessible aux clients connectés.
​

Fonctionnalités
Site vitrine : page d’accueil, présentation du salon, prestations, galerie photos, informations de contact.
​

Avis clients : consultation de tous les avis et ajout d’un avis via un formulaire public.
​

Formulaire de contact : enregistrement des messages en local dans var/messages/contact.php (créé au premier message).
​

E-boutique : page liste produits, panier, paiement via Stripe, historique des commandes pour les utilisateurs connectés.
​

Gestion des médias : photos de la galerie stockées physiquement dans public/images/photos avec URL enregistrée en base.
​

Rôles et permissions
Visiteur non connecté :

Peut consulter le site vitrine, les avis, ajouter les avis, la boutique et laisser un message/une question.
​

Utilisateur connecté (rôle user) :

Peut ajouter des produits au panier, accéder à la page panier pour le paiement Stripe et consulter son historique de commandes.
​

Administrateur (rôle admin) :

Peut créer, modifier et supprimer des produits de la boutique.
​

Peut ajouter et supprimer des photos de la galerie.
​

Peut consulter les messages envoyés via le formulaire de contact, stockés en local.
​

Stack technique
Framework : Symfony 7.3 (projet full-stack).
​

Conteneurisation : Docker / Docker Compose.
​

Backend : PHP 8.3 avec Apache.
​

Base de données relationnelle : MySQL 8.0 (base bdd-88).
​

Base documentaire : MongoDB via doctrine/mongodb-odm-bundle.
​

Outils : phpMyAdmin pour l’administration MySQL.
​

OS de développement : Windows 10, mais le projet est portable sur tout OS supportant Docker (Linux, macOS, Windows).
​

Modèle de données (principales tables)
user : gestion des comptes, rôles ROLE_USER et ROLE_ADMIN.
​

products : catalogue produits de la boutique.
​

order et order_products : commandes et lignes de commande liées aux produits.
​

photos : enregistrement des photos de la galerie (URL en base, fichier dans public/images/photos).
​

Prérequis
Docker et Docker Compose installés.
​

Accès à des clés API :

Clé Stripe (paiements).
​

Clé API météo.
​

PHP et Composer éventuels en local uniquement si tu veux lancer certaines commandes hors conteneur.
​

Installation
Cloner le dépôt (exemple) :

bash
git clone https://github.com/ton-pseudo/le-88-comptoir-du-cheveu.git](https://github.com/Thomas-Durandiere/88.git
cd 88
​
2. Me contacter pour récupérer les clés API Stripe et météo (elles ne sont pas versionnées).
​
3. Démarrer l’environnement Docker :

bash
docker compose up -d --build
​
4. Entrer dans le conteneur PHP :

bash
docker compose exec php bash
​
5. Installer les dépendances :

bash
composer install
​
6. Créer la base de données (bdd-88), puis lancer les migrations :

bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
​
7. Installer le bundle MongoDB ODM (si nécessaire dans l’environnement) :

bash
composer require doctrine/mongodb-odm-bundle
​

Pour arrêter l’environnement :

bash
docker compose down


Tests
Lancer un test ciblé :

bash
php bin/phpunit tests/nom_du_test.php
​

Générer un rapport de couverture :

bash
php bin/phpunit --coverage-html var/coverage
​

Intégration Stripe (webhook)
Pour que les paiements Stripe soient correctement traités en local, il faut écouter les événements et les rediriger vers le webhook Symfony :

bash
stripe listen --forward-to http://127.0.0.1:8080/stripe/webhook


Bonnes pratiques de contribution
La branche principale de développement est main.​
Celle sur laquelle je travail est dev.

Après avoir cloné le projet, il est recommandé de créer une branche personnelle pour tes modifications :

bash
git checkout -b feature/mon-feature
​

Ouvrir ensuite une pull request vers main en suivant les conventions Git habituelles (commits propres, description claire de la fonctionnalité).
