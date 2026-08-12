# Documentation API — TELU BAOBAB

`openapi.yaml` est la spécification **OpenAPI 3.1** complète de l'API (`/api/*`), couvrant
les 143 endpoints : authentification Sanctum, OTP/mot de passe, les 5 profils métier, les
trois marketplaces (commerce, immobilier, emplois), paiements PayGate, abonnements,
portefeuilles & retraits, messagerie, notifications, notes/avis et le back-office admin.

## Visualiser

Aucune dépendance n'est ajoutée au projet. Choisissez l'une de ces options :

- **Swagger Editor en ligne** — copiez le contenu dans <https://editor.swagger.io>.
- **VS Code** — extension *OpenAPI (Swagger) Editor* (42Crunch) : ouvrez `docs/openapi.yaml`,
  puis « Preview ».
- **Redoc / Swagger UI en local** :
  ```bash
  npx @redocly/cli preview-docs docs/openapi.yaml
  # ou
  npx @redocly/cli build-docs docs/openapi.yaml -o docs/api.html
  ```

## Importer

- **Postman / Insomnia** : *Import* → sélectionnez `docs/openapi.yaml` (génère une collection).
- **Génération de clients** : `openapi-generator-cli generate -i docs/openapi.yaml -g <lang>`.

## Authentification

La plupart des routes exigent un jeton **Bearer** (Laravel Sanctum). Obtenez-en un via
`POST /api/auth/register` ou `POST /api/auth/login`, puis envoyez
`Authorization: Bearer <token>`. Les routes publiques (auth, OTP, mot de passe oublié,
catalogues, webhook PayGate) ne sont pas protégées.

## Maintenir à jour

Le fichier est **écrit à la main** — il n'est pas régénéré automatiquement depuis le code.
À chaque ajout/modification de route ou de règle de validation, mettez `openapi.yaml` à jour
en conséquence. Un contrôle de couverture rapide (routes du code ⇔ chemins du spec) peut se
faire en comparant `php artisan route:list --path=api` aux `paths` du YAML.
