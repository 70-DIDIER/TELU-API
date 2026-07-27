# TELU BAOBAB — API

Backend (API REST) de **TELU BAOBAB**, une plateforme numérique multifonctionnelle pour le marché africain (Togo) qui met en relation quatre catégories d'utilisateurs — **vendeurs**, **clients**, **livreurs**, **recruteurs / travailleurs journaliers** — autour de trois modules :

- **Commerce & Livraison** — les vendeurs publient leurs produits, les clients commandent, des livreurs affiliés assurent la livraison.
- **Immobilier** — hôtels, chambres, studios, appartements et maisons à louer, réservables directement.
- **Emploi journalier** — les recruteurs publient des besoins, les chercheurs d'emploi postulent.

Fonctionnalités transverses : géolocalisation, messagerie, système d'évaluation, paiement mobile money via **PayGate Global** (Flooz et TMoney / Mixx by Yas), notifications automatiques.

## Stack technique

- **PHP 8.3** · **Laravel 13**
- **Laravel Sanctum** — authentification par jeton (token)
- **PostgreSQL** (base `telu`) — configurable via `.env`
- Clés primaires **UUID** sur toutes les tables
- Tests : **PHPUnit** · Formatage : **Laravel Pint**

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate

# Configurer la connexion base de données dans .env (PostgreSQL), puis :
php artisan migrate --seed
```

Le seeder crée un jeu de données de démonstration cohérent et deux comptes de test :

| Compte | Email | Mot de passe | Type |
|---|---|---|---|
| Admin | `admin@telu.tg` | `password` | admin |
| Client | `client@telu.tg` | `password` | client |

## Lancer le projet

```bash
php artisan serve      # serveur de développement (http://127.0.0.1:8000)
composer dev           # serveur + worker de file + logs (pail) + vite, en parallèle
composer test          # lance la suite de tests
vendor/bin/pint        # formate le code
```

## Authentification

Toutes les routes protégées attendent un en-tête `Authorization: Bearer <token>`.
Le jeton est renvoyé par `register` et `login`. Le champ `login` accepte **un email ou un numéro de téléphone** (auto-détecté).

```bash
# Exemple : inscription puis appel authentifié
curl -X POST http://127.0.0.1:8000/api/auth/register \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"full_name":"Ama","phone":"+228 90 00 00 01","password":"secret123","password_confirmation":"secret123","user_type":"vendor"}'
```

## Endpoints disponibles

### Authentification
| Méthode | Route | Auth | Description |
|---|---|---|---|
| POST | `/api/auth/register` | — | Créer un compte + renvoyer un token |
| POST | `/api/auth/login` | — | Connexion (email ou téléphone) |
| GET | `/api/auth/me` | ✅ | Utilisateur connecté |
| POST | `/api/auth/logout` | ✅ | Révoquer le token courant |

### Profils métier
Chaque compte (selon son `user_type`) possède **un** profil métier qu'il crée et gère lui-même.

| Méthode | Route | Type requis |
|---|---|---|
| GET · POST · PUT | `/api/vendor` | vendor |
| GET · POST · PUT | `/api/driver` | driver |
| GET · POST · PUT | `/api/property-owner` | property_owner |
| GET · POST · PUT | `/api/recruiter` | recruiter |
| GET · POST · PUT | `/api/job-seeker` | job_seeker |

Règles : le `user_type` doit correspondre (**403** sinon), un seul profil par compte (**409** en doublon).

### Commerce — Produits
| Méthode | Route | Auth | Description |
|---|---|---|---|
| GET | `/api/products` | ✅ | Catalogue public (filtres : `search`, `category`, `vendor_id`, `min_price`, `max_price`) |
| GET | `/api/products/{product}` | ✅ | Détail d'un produit |
| GET | `/api/vendor/products` | ✅ | Mes produits (vendeur) |
| POST | `/api/vendor/products` | ✅ | Créer un produit |
| GET | `/api/vendor/products/{product}` | ✅ | Voir un de mes produits |
| PUT | `/api/vendor/products/{product}` | ✅ | Modifier un de mes produits |
| DELETE | `/api/vendor/products/{product}` | ✅ | Supprimer un de mes produits |

Un vendeur ne voit et ne modifie que **ses propres** produits.

### Commerce — Commandes (côté client)
| Méthode | Route | Auth | Description |
|---|---|---|---|
| GET | `/api/orders` | ✅ | Mes commandes |
| POST | `/api/orders` | ✅ | Passer une commande |
| GET | `/api/orders/{order}` | ✅ | Détail d'une de mes commandes |
| POST | `/api/orders/{order}/confirm-receipt` | ✅ | Confirmer la réception (clôt commande + livraison) |

À la création, le **total est calculé côté serveur** ; tous les articles doivent provenir du même vendeur, être disponibles et en stock.

### Commerce — Livraison (côté livreur)
| Méthode | Route | Auth | Description |
|---|---|---|---|
| GET | `/api/driver/deliveries/available` | ✅ | Livraisons en attente (pool ouvert) |
| GET | `/api/driver/deliveries` | ✅ | Mes livraisons (filtre `?status=`) |
| POST | `/api/driver/deliveries/{delivery}/claim` | ✅ | S'attribuer une livraison (anti-concurrence) |
| POST | `/api/driver/deliveries/{delivery}/pickup` | ✅ | Récupérer le colis (commande → `in_delivery`) |

**Parcours complet** : commande (client) → acceptation (vendeur, stock déduit + livraison créée + livreurs notifiés) → `claim`/`pickup` (livreur) → confirmation de réception (client). Notifications automatiques à chaque étape clé.

### Commerce — Commandes (côté vendeur)
| Méthode | Route | Auth | Description |
|---|---|---|---|
| GET | `/api/vendor/orders` | ✅ | Commandes reçues (filtre `?status=`) |
| GET | `/api/vendor/orders/{order}` | ✅ | Détail d'une commande reçue |
| PATCH | `/api/vendor/orders/{order}/status` | ✅ | Changer le statut |

Transitions autorisées : `pending → accepted | cancelled`, `accepted → preparing | cancelled`, `preparing → cancelled`. Le **stock est déduit à l'acceptation** et **restauré** si une commande acceptée est annulée.

## Tester avec Postman

Une collection prête à l'emploi est fournie : `postman/TELU-BAOBAB.postman_collection.json` (8 dossiers).

Importe-la dans Postman, puis lance les requêtes **dans l'ordre** au sein d'un dossier :

- **Auth** et les 5 dossiers **Profil …** — autonomes (chacun commence par son `Register`, le token est capturé automatiquement).
- **Commerce - Produits** — CRUD vendeur + catalogue public (capture du `product_id`).
- **Commerce - Commandes & Livraison (parcours complet)** — enchaîne les 3 acteurs (vendeur, client, livreur) avec des tokens séparés (`vendor_token`, `client_token`, `driver_token`) : création profil/produit → commande → acceptation → attribution/récupération livreur → confirmation client. Les IDs (`vendor_id`, `product_id`, `order_id`, `delivery_id`) sont capturés et réutilisés automatiquement, et des tests (`pm.test`) vérifient les statuts et le total.

> La collection est **générée** ; ne pas éditer le JSON à la main (régénérer depuis le script de build).

## État d'avancement

- ✅ Base de données (schéma complet des 3 modules)
- ✅ Modèles Eloquent, factories, seeder
- ✅ Authentification (Sanctum)
- ✅ Profils métier (5 types)
- ✅ Commerce — produits (catalogue + gestion vendeur)
- ✅ Commerce — commandes & livraison (parcours complet : commande → acceptation → livraison → confirmation)
- ✅ Immobilier (annonces, réservations)
- ✅ Emploi (offres, candidatures)
- ✅ Transverses (messagerie, évaluations, notifications)
- ✅ Paiements mobile money PayGate Global (Flooz / TMoney)
- ✅ Vérification du numéro par code OTP SMS (AfrikSMS)
- ✅ Back-office administrateur (`/api/admin/*`)

### Configuration PayGate Global

Renseigner dans `.env` (voir `.env.example`) :

```
PAYGATE_API_KEY=<clé API marchand>
PAYGATE_BASE_URL=https://paygateglobal.com
PAYGATE_CALLBACK_URL="${APP_URL}/api/payments/callback"
```

L'URL de callback doit également être déclarée sur le tableau de bord marchand PayGate.
Le webhook n'étant pas signé, l'API re-interroge systématiquement PayGate
(`/api/v2/status`) avant d'écrire le statut d'un paiement.

### Configuration AfrikSMS (codes OTP)

```
AFRIKSMS_DRIVER=log            # log = SMS écrit dans les logs (dev) ; http = envoi réel
AFRIKSMS_CLIENT_ID=<Identifiant Api>
AFRIKSMS_API_KEY=<Clé d'authentification Api>
AFRIKSMS_SENDER_ID=TELUBAOBAB  # 11 caractères max, à déclarer chez AfrikSMS

OTP_REQUIRED_FOR_REGISTRATION=false   # true = numéro vérifié obligatoire à l'inscription
```

Parcours d'inscription vérifiée : `POST /api/auth/otp/send` → SMS → `POST /api/auth/otp/verify`
(rend un `verification_token`) → `POST /api/auth/register` avec `otp_token`.
Un utilisateur déjà connecté vérifie son propre numéro via `POST /api/otp/send` puis `POST /api/otp/verify`.
Les réglages (durée de vie, quotas, gabarit du SMS) sont dans [`config/otp.php`](config/otp.php).

## Architecture

Voir [`CLAUDE.md`](CLAUDE.md) pour les conventions du code (attributs de modèles Laravel 13, UUID, géolocalisation, patrons des contrôleurs) et le détail de l'état d'implémentation.
