# Portfolio Serveur

Page d'accueil qui liste les projets hébergés sur mon serveur. Chaque projet s'affiche sous forme de carte avec un aperçu en direct (iframe), une description et les technologies utilisées. Un espace admin protégé par mot de passe permet de gérer les projets sans toucher au code.

## Stack

- **PHP 8.1+** (PDO) + **MySQL**
- **Tailwind CSS** via CDN (aucun build nécessaire)
- Manifest PWA + icônes (`manifest.json`, `icon-192.png`, `icon-512.png`, `apple-touch-icon.png`, `favicon.ico`)

## Structure

| Fichier         | Rôle                                                                         |
| --------------- | ---------------------------------------------------------------------------- |
| `index.php`     | Page publique : liste des projets triés par `ordre`                          |
| `orque.php`     | Back-office : connexion, ajout, modification, suppression et ordre des projets |
| `assets/`       | Styles et script du back-office (`admin-projects.css`, `admin-projects.js`) et logos des technologies (`tech-logos/`) |
| `robots.txt`    | Consignes pour les moteurs de recherche et lien vers le sitemap              |
| `sitemap.xml`   | Liste des pages à indexer (à envoyer dans la Google Search Console)          |
| `config.php`    | Identifiants BDD et mot de passe admin (**non versionné**)                   |
| `manifest.json` | Manifest PWA (nom, icônes)                                                   |
| `logo_orque.png`| Logo                                                                         |

## Installation en local (Laragon)

1. Cloner le dépôt dans `C:\laragon\www\PortfolioServeur`.
2. Créer la base de données et la table `projets` (voir ci-dessous).
3. Créer le fichier `config.php` à la racine (voir ci-dessous).
4. Ouvrir `http://localhost/PortfolioServeur/` (ou `http://portfolioserveur.test` avec les hôtes virtuels de Laragon).

### Base de données

```sql
CREATE DATABASE portfolio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE portfolio;

CREATE TABLE projets (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    titre        VARCHAR(255) NOT NULL,
    description  TEXT NOT NULL,
    image_url    VARCHAR(255) DEFAULT '#',
    lien_projet  VARCHAR(255) NOT NULL,
    technologie  VARCHAR(255) NOT NULL,
    espace_admin TINYINT(1) NOT NULL DEFAULT 0,
    ordre        INT NOT NULL DEFAULT 0
);
```

> `image_url` n'est plus utilisé par l'affichage (l'aperçu se fait par iframe), mais `orque.php` l'insère toujours avec la valeur `'#'`.

### Fichier `config.php`

Ce fichier est ignoré par Git (voir `.gitignore`), il faut donc le créer à la main sur chaque machine :

```php
<?php
// config.php - Ce fichier ne sera jamais envoyé sur Git
$host = 'localhost';
$dbname = 'portfolio';
$username = 'root';
$password = '';

// Mot de passe pour accéder à la page d'administration (orque.php)
$mot_de_passe_admin = 'choisir-un-mot-de-passe-solide';
```

## Utilisation

### Site public

`index.php` récupère tous les projets (`ORDER BY ordre ASC`) et affiche pour chacun :

- un aperçu en direct du site via iframe ;
- le titre, la description et les technologies ;
- la mention « Espace admin » si la case correspondante est cochée.

### Back-office

Aller sur `orque.php` et se connecter avec `$mot_de_passe_admin`. La page n'est liée nulle part sur le site et porte une balise `noindex` pour ne pas apparaître dans les moteurs de recherche.

- **Ajouter** un projet : titre, ordre, URL, technologies (séparées par ` · `), description, case « espace d'administration ».
- **Modifier** : cliquer sur « Modifier » dans la liste, le formulaire se pré-remplit.
- **Supprimer** : bouton « Supprimer » (avec confirmation).
- Les formulaires sont protégés par un jeton CSRF et l'URL du projet doit commencer par `http://` ou `https://`.
- **Ordre d'affichage** : plus le nombre est petit, plus le projet apparaît tôt.

## Déploiement sur le VPS

1. Envoyer les fichiers dans le dossier web du serveur (ex. `/var/www/html/`).
2. Créer la base et la table `projets` sur le serveur MySQL.
3. Créer un `config.php` avec les identifiants de production (ne jamais le committer).
4. Vérifier que les projets listés acceptent d'être affichés en iframe (pas d'en-tête `X-Frame-Options: DENY` ni de `frame-ancestors` restrictif), sinon l'aperçu restera vide.
