<?php

/**
 * Builder for the TELU BAOBAB Postman collection.
 *
 * The collection JSON is GENERATED — never hand-edit it. Edit this script and
 * regenerate:  php postman/build_collection.php
 *
 * Each domain folder is a self-contained "parcours" that registers its own
 * actors and captures tokens / ids into collection variables, so the whole
 * collection can be run top-to-bottom with the Postman Collection Runner.
 */

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Build a URL object from a slash path (segments may contain {{vars}}).
 *
 * @param  array<string,string>  $query
 */
function urlObj(string $path, array $query = []): array
{
    $segments = explode('/', trim($path, '/'));
    $raw = '{{base_url}}/'.implode('/', $segments);
    $url = ['raw' => $raw, 'host' => ['{{base_url}}'], 'path' => $segments];

    if ($query) {
        $pairs = [];
        $qs = [];
        foreach ($query as $k => $v) {
            $pairs[] = ['key' => $k, 'value' => (string) $v];
            $qs[] = $k.'='.$v;
        }
        $url['raw'] = $raw.'?'.implode('&', $qs);
        $url['query'] = $pairs;
    }

    return $url;
}

/**
 * Build a request item.
 *
 * @param  array{body?:array<mixed>,auth?:string,query?:array<string,string>,tests?:list<string>,desc?:string}  $o
 */
function req(string $name, string $method, string $path, array $o = []): array
{
    $header = [['key' => 'Accept', 'value' => 'application/json']];

    $request = ['method' => $method, 'header' => $header];

    if (array_key_exists('body', $o)) {
        $request['header'][] = ['key' => 'Content-Type', 'value' => 'application/json'];
        $request['body'] = [
            'mode' => 'raw',
            'raw' => json_encode($o['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ];
    }

    $auth = $o['auth'] ?? 'token';
    $request['auth'] = $auth === 'noauth'
        ? ['type' => 'noauth']
        : ['type' => 'bearer', 'bearer' => [['key' => 'token', 'value' => '{{'.$auth.'}}', 'type' => 'string']]];

    $request['url'] = urlObj($path, $o['query'] ?? []);

    if (! empty($o['desc'])) {
        $request['description'] = $o['desc'];
    }

    $item = ['name' => $name, 'request' => $request];

    if (! empty($o['tests'])) {
        $item['event'] = [[
            'listen' => 'test',
            'script' => ['type' => 'text/javascript', 'exec' => $o['tests']],
        ]];
    }

    return $item;
}

/** Assert an HTTP status. */
function assertStatus(int $code): string
{
    return "pm.test('status $code', () => pm.response.to.have.status($code));";
}

/** Capture json.token into a collection variable. */
function captureToken(string $var): string
{
    return "{ const j = pm.response.json(); if (j.token) pm.collectionVariables.set('$var', j.token); }";
}

/** Capture json.user.id (register/login) into a collection variable. */
function captureUserId(string $var): string
{
    return "{ const j = pm.response.json(); const uid = (j.user && j.user.id) || j.id; if (uid) pm.collectionVariables.set('$var', uid); }";
}

/** Capture json.id into a collection variable. */
function captureId(string $var): string
{
    return "{ const j = pm.response.json(); if (j.id) pm.collectionVariables.set('$var', j.id); }";
}

/** Capture json.data[0].id (paginated list) into a collection variable. */
function captureFirstDataId(string $var): string
{
    return "{ const j = pm.response.json(); if (j.data && j.data[0]) pm.collectionVariables.set('$var', j.data[0].id); }";
}

/** A register request that captures a token (and optionally the user id). */
function register(string $label, string $userType, string $tokenVar, ?string $userIdVar = null): array
{
    $tests = [assertStatus(201), captureToken($tokenVar)];
    if ($userIdVar) {
        $tests[] = captureUserId($userIdVar);
    }

    // phone and email are unique in the users table — use {{$guid}} (a UUID)
    // so parallel/repeated registrations across folders never collide.
    return req("Register ($userType) -> $tokenVar", 'POST', 'api/auth/register', [
        'auth' => 'noauth',
        'body' => [
            'full_name' => ucfirst($userType).' '.$label,
            'phone' => '+228-{{$guid}}',
            'email' => $userType.'-{{$guid}}@telu.tg',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'user_type' => $userType,
        ],
        'tests' => $tests,
    ]);
}

function folder(string $name, array $items): array
{
    return ['name' => $name, 'item' => $items];
}

// ---------------------------------------------------------------------------
// Folders
// ---------------------------------------------------------------------------

$folders = [];

// --- Auth -------------------------------------------------------------------
$folders[] = folder('Auth', [
    register('auth', 'vendor', 'vendor_token'),
    register('auth', 'client', 'client_token', 'client_user_id'),
    req('Login', 'POST', 'api/auth/login', [
        'auth' => 'noauth',
        'body' => ['login' => 'client@telu.tg', 'password' => 'password'],
        'tests' => [assertStatus(200), captureToken('token')],
    ]),
    req('Me', 'GET', 'api/auth/me', ['auth' => 'client_token', 'tests' => [assertStatus(200)]]),
    req('Logout', 'POST', 'api/auth/logout', ['auth' => 'client_token', 'tests' => [assertStatus(200)]]),
]);

// --- OTP - verification du numero par SMS -----------------------------------
// Le code n'est jamais renvoye par l'API (il part uniquement par SMS) : ce
// parcours est semi-manuel, on colle le code recu dans la variable {{otp_code}}.
$folders[] = folder('OTP - Verification SMS', [
    req('01. Envoyer un code (200)', 'POST', 'api/auth/otp/send', [
        'auth' => 'noauth',
        'body' => ['phone' => '{{otp_phone}}'],
        'tests' => ["pm.test('200 (SMS parti), 409 (deja inscrit) ou 502 (passerelle KO)', function () { pm.expect([200, 409, 502]).to.include(pm.response.code); });"],
        'desc' => 'Renseignez {{otp_phone}} avec un vrai numero togolais. Renvoi limite a 1 code / 60 s et 5 codes / heure par numero.',
    ]),
    req('02. Verifier le code (200)', 'POST', 'api/auth/otp/verify', [
        'auth' => 'noauth',
        'body' => ['phone' => '{{otp_phone}}', 'code' => '{{otp_code}}'],
        'tests' => [
            "pm.test('200 (verifie) ou 422 (code faux/expire)', function () { pm.expect([200, 422]).to.include(pm.response.code); });",
            "{ const j = pm.response.json(); if (j.verification_token) pm.collectionVariables.set('otp_token', j.verification_token); }",
        ],
        'desc' => 'Collez dans {{otp_code}} le code recu par SMS. En cas de succes le jeton est capture dans {{otp_token}} (valable 30 min).',
    ]),
    req('03. Inscription avec le numero verifie (201)', 'POST', 'api/auth/register', [
        'auth' => 'noauth',
        'body' => [
            'full_name' => 'Client verifie',
            'phone' => '{{otp_phone}}',
            'email' => 'otp-{{$guid}}@telu.tg',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'user_type' => 'client',
            'otp_token' => '{{otp_token}}',
        ],
        'tests' => [assertStatus(201), captureToken('otp_user_token')],
        'desc' => 'Avec un otp_token valide le compte est cree avec is_verified=true et phone_verified_at renseigne.',
    ]),
    req('04. Envoyer un code sur mon propre numero (200)', 'POST', 'api/otp/send', [
        'auth' => 'otp_user_token',
        'tests' => ["pm.test('200, 409 (deja verifie) ou 502', function () { pm.expect([200, 409, 502]).to.include(pm.response.code); });"],
        'desc' => 'Flux connecte : le code part vers le numero du compte, sans le passer dans le body.',
    ]),
    req('05. Verifier mon numero (200)', 'POST', 'api/otp/verify', [
        'auth' => 'otp_user_token',
        'body' => ['code' => '{{otp_code}}'],
        'tests' => ["pm.test('200, 409 (deja verifie) ou 422', function () { pm.expect([200, 409, 422]).to.include(pm.response.code); });"],
    ]),
]);

// --- Business profiles (one folder each) ------------------------------------
$profiles = [
    ['Profil Vendeur', 'vendor', 'vendor', ['shop_name' => 'Boutique {{$randomInt}}', 'description' => 'Vente au detail']],
    ['Profil Livreur', 'driver', 'driver', ['vehicle_type' => 'moto', 'is_available' => true]],
    ['Profil Proprietaire', 'property_owner', 'property-owner', ['owner_type' => 'individual', 'company_name' => 'Agence {{$randomInt}}']],
    ['Profil Recruteur', 'recruiter', 'recruiter', ['company_name' => 'BTP {{$randomInt}}', 'industry' => 'construction']],
    ['Profil Chercheur emploi', 'job_seeker', 'job-seeker', ['profession' => 'macon', 'skills' => 'maconnerie', 'experience' => '3 ans', 'availability' => 'immediate']],
];
foreach ($profiles as [$folderName, $userType, $slug, $body]) {
    $tok = $userType.'_prof_token';
    $folders[] = folder($folderName, [
        register('prof', $userType, $tok),
        req('Create profile (201)', 'POST', 'api/'.$slug, ['auth' => $tok, 'body' => $body, 'tests' => [assertStatus(201)]]),
        req('Get my profile (200)', 'GET', 'api/'.$slug, ['auth' => $tok, 'tests' => [assertStatus(200)]]),
        req('Update profile (200)', 'PUT', 'api/'.$slug, ['auth' => $tok, 'body' => $body, 'tests' => [assertStatus(200)]]),
    ]);
}

// --- Commerce - Produits ----------------------------------------------------
$folders[] = folder('Commerce - Produits', [
    register('prod', 'vendor', 'vendor_token', 'vendor_user_id'),
    req('1. Creer profil vendeur (201)', 'POST', 'api/vendor', [
        'auth' => 'vendor_token',
        'body' => ['shop_name' => 'Boutique {{$randomInt}}', 'description' => 'Epicerie'],
        'tests' => [assertStatus(201), captureId('vendor_id')],
    ]),
    req('2. Creer produit (201)', 'POST', 'api/vendor/products', [
        'auth' => 'vendor_token',
        'body' => ['name' => 'Riz 25kg', 'description' => 'Sac de riz', 'price' => 12000, 'category' => 'alimentaire', 'stock' => 50],
        'tests' => [assertStatus(201), captureId('product_id')],
    ]),
    req('3. Lister mes produits (200)', 'GET', 'api/vendor/products', ['auth' => 'vendor_token', 'tests' => [assertStatus(200)]]),
    req('4. Voir un produit (200)', 'GET', 'api/vendor/products/{{product_id}}', ['auth' => 'vendor_token', 'tests' => [assertStatus(200)]]),
    req('5. Modifier un produit (200)', 'PUT', 'api/vendor/products/{{product_id}}', [
        'auth' => 'vendor_token',
        'body' => ['price' => 12500],
        'tests' => [assertStatus(200)],
    ]),
    req('6. Catalogue public - liste (200)', 'GET', 'api/products', ['auth' => 'vendor_token', 'tests' => [assertStatus(200)]]),
    req('7. Catalogue public - recherche (200)', 'GET', 'api/products', ['auth' => 'vendor_token', 'query' => ['search' => 'Riz'], 'tests' => [assertStatus(200)]]),
    req('8. Catalogue public - detail (200)', 'GET', 'api/products/{{product_id}}', ['auth' => 'vendor_token', 'tests' => [assertStatus(200)]]),
    req('9. Supprimer un produit (200)', 'DELETE', 'api/vendor/products/{{product_id}}', ['auth' => 'vendor_token', 'tests' => [assertStatus(200)]]),
]);

// --- Commerce - Commandes & Livraison (parcours complet) --------------------
$folders[] = folder('Commerce - Commandes & Livraison (parcours complet)', [
    register('flow', 'vendor', 'vendor_token', 'vendor_user_id'),
    req('01. Vendeur cree son profil', 'POST', 'api/vendor', [
        'auth' => 'vendor_token',
        'body' => ['shop_name' => 'Boutique flow {{$randomInt}}'],
        'tests' => [assertStatus(201), captureId('vendor_id')],
    ]),
    req('02. Vendeur cree un produit', 'POST', 'api/vendor/products', [
        'auth' => 'vendor_token',
        'body' => ['name' => 'Cafe', 'price' => 2500, 'stock' => 100],
        'tests' => [assertStatus(201), captureId('product_id')],
    ]),
    register('flow', 'driver', 'driver_token'),
    req('03. Livreur cree son profil (disponible)', 'POST', 'api/driver', [
        'auth' => 'driver_token',
        'body' => ['vehicle_type' => 'moto', 'is_available' => true],
        'tests' => [assertStatus(201)],
    ]),
    register('flow', 'client', 'client_token'),
    req('04. Client passe commande', 'POST', 'api/orders', [
        'auth' => 'client_token',
        'body' => [
            'vendor_id' => '{{vendor_id}}',
            'delivery_address' => 'Tokoin, Lome',
            'items' => [['product_id' => '{{product_id}}', 'quantity' => 2]],
        ],
        'tests' => [assertStatus(201), captureId('order_id')],
    ]),
    req('05. Vendeur voit ses commandes recues', 'GET', 'api/vendor/orders', ['auth' => 'vendor_token', 'tests' => [assertStatus(200)]]),
    req('06. Vendeur accepte la commande', 'PATCH', 'api/vendor/orders/{{order_id}}/status', [
        'auth' => 'vendor_token',
        'body' => ['status' => 'accepted'],
        'tests' => [assertStatus(200)],
    ]),
    req('07. Livreur voit le pool disponible (capture delivery_id)', 'GET', 'api/driver/deliveries/available', [
        'auth' => 'driver_token',
        'tests' => [assertStatus(200), captureFirstDataId('delivery_id')],
    ]),
    req('08. Livreur prend la livraison', 'POST', 'api/driver/deliveries/{{delivery_id}}/claim', ['auth' => 'driver_token', 'tests' => [assertStatus(200)]]),
    req('09. Livreur recupere le colis', 'POST', 'api/driver/deliveries/{{delivery_id}}/pickup', ['auth' => 'driver_token', 'tests' => [assertStatus(200)]]),
    req('10. Client confirme la reception', 'POST', 'api/orders/{{order_id}}/confirm-receipt', ['auth' => 'client_token', 'tests' => [assertStatus(200)]]),
]);

// --- Immobilier - Biens -----------------------------------------------------
$folders[] = folder('Immobilier - Biens', [
    register('prop', 'property_owner', 'owner_token'),
    req('1. Creer profil proprietaire (201)', 'POST', 'api/property-owner', [
        'auth' => 'owner_token',
        'body' => ['owner_type' => 'individual', 'company_name' => 'Agence {{$randomInt}}'],
        'tests' => [assertStatus(201)],
    ]),
    req('2. Creer un bien (201)', 'POST', 'api/property-owner/properties', [
        'auth' => 'owner_token',
        'body' => ['title' => 'Studio meuble', 'property_type' => 'studio', 'address' => 'Bd du Mono', 'price' => 45000, 'price_unit' => 'month', 'bedrooms' => 1],
        'tests' => [assertStatus(201), captureId('property_id')],
    ]),
    req('3. Lister mes biens (200)', 'GET', 'api/property-owner/properties', ['auth' => 'owner_token', 'tests' => [assertStatus(200)]]),
    req('4. Voir un bien (200)', 'GET', 'api/property-owner/properties/{{property_id}}', ['auth' => 'owner_token', 'tests' => [assertStatus(200)]]),
    req('5. Modifier un bien (200)', 'PUT', 'api/property-owner/properties/{{property_id}}', ['auth' => 'owner_token', 'body' => ['price' => 50000], 'tests' => [assertStatus(200)]]),
    req('6. Catalogue public - liste (200)', 'GET', 'api/properties', ['auth' => 'owner_token', 'query' => ['property_type' => 'studio'], 'tests' => [assertStatus(200)]]),
    req('7. Catalogue public - detail (200)', 'GET', 'api/properties/{{property_id}}', ['auth' => 'owner_token', 'tests' => [assertStatus(200)]]),
]);

// --- Immobilier - Reservations (parcours) -----------------------------------
$folders[] = folder('Immobilier - Reservations (parcours)', [
    register('resa', 'property_owner', 'owner_token'),
    req('01. Proprietaire cree son profil', 'POST', 'api/property-owner', ['auth' => 'owner_token', 'body' => ['owner_type' => 'hotel', 'company_name' => 'Hotel {{$randomInt}}'], 'tests' => [assertStatus(201)]]),
    req('02. Proprietaire publie un bien', 'POST', 'api/property-owner/properties', [
        'auth' => 'owner_token',
        'body' => ['title' => 'Chambre nuitee', 'property_type' => 'room', 'address' => 'Kodjoviakope', 'price' => 10000, 'price_unit' => 'night'],
        'tests' => [assertStatus(201), captureId('property_id')],
    ]),
    register('resa', 'client', 'client_token'),
    req('03. Client reserve (total serveur)', 'POST', 'api/reservations', [
        'auth' => 'client_token',
        'body' => ['property_id' => '{{property_id}}', 'check_in' => '2026-09-01', 'check_out' => '2026-09-04'],
        'tests' => [assertStatus(201), captureId('reservation_id')],
    ]),
    req('04. Client liste ses reservations', 'GET', 'api/reservations', ['auth' => 'client_token', 'tests' => [assertStatus(200)]]),
    req('05. Client voit une reservation', 'GET', 'api/reservations/{{reservation_id}}', ['auth' => 'client_token', 'tests' => [assertStatus(200)]]),
    req('06. Proprietaire voit les reservations', 'GET', 'api/property-owner/reservations', ['auth' => 'owner_token', 'tests' => [assertStatus(200)]]),
    req('07. Proprietaire confirme', 'PATCH', 'api/property-owner/reservations/{{reservation_id}}/status', ['auth' => 'owner_token', 'body' => ['status' => 'confirmed'], 'tests' => [assertStatus(200)]]),
    req('08. Proprietaire complete', 'PATCH', 'api/property-owner/reservations/{{reservation_id}}/status', ['auth' => 'owner_token', 'body' => ['status' => 'completed'], 'tests' => [assertStatus(200)]]),
]);

// --- Emploi - Offres --------------------------------------------------------
$folders[] = folder('Emploi - Offres', [
    register('offre', 'recruiter', 'recruiter_token'),
    req('1. Creer profil recruteur (201)', 'POST', 'api/recruiter', ['auth' => 'recruiter_token', 'body' => ['company_name' => 'BTP {{$randomInt}}', 'industry' => 'construction'], 'tests' => [assertStatus(201)]]),
    req('2. Publier une offre (201)', 'POST', 'api/recruiter/job-offers', [
        'auth' => 'recruiter_token',
        'body' => ['title' => 'Macon chantier', 'location' => 'Adidogome', 'daily_rate' => 7500, 'start_date' => '2026-08-01', 'duration' => '5 jours', 'people_needed' => 3, 'required_skills' => 'maconnerie'],
        'tests' => [assertStatus(201), captureId('job_offer_id')],
    ]),
    req('3. Lister mes offres (200)', 'GET', 'api/recruiter/job-offers', ['auth' => 'recruiter_token', 'tests' => [assertStatus(200)]]),
    req('4. Voir une offre (200)', 'GET', 'api/recruiter/job-offers/{{job_offer_id}}', ['auth' => 'recruiter_token', 'tests' => [assertStatus(200)]]),
    req('5. Modifier une offre (200)', 'PUT', 'api/recruiter/job-offers/{{job_offer_id}}', ['auth' => 'recruiter_token', 'body' => ['daily_rate' => 8000], 'tests' => [assertStatus(200)]]),
    req('6. Board public - liste (200)', 'GET', 'api/job-offers', ['auth' => 'recruiter_token', 'query' => ['search' => 'Macon'], 'tests' => [assertStatus(200)]]),
    req('7. Board public - detail (200)', 'GET', 'api/job-offers/{{job_offer_id}}', ['auth' => 'recruiter_token', 'tests' => [assertStatus(200)]]),
]);

// --- Emploi - Candidatures (parcours) ---------------------------------------
$folders[] = folder('Emploi - Candidatures (parcours)', [
    register('cand', 'recruiter', 'recruiter_token'),
    req('01. Recruteur cree son profil', 'POST', 'api/recruiter', ['auth' => 'recruiter_token', 'body' => ['company_name' => 'Chantiers {{$randomInt}}'], 'tests' => [assertStatus(201)]]),
    req('02. Recruteur publie une offre', 'POST', 'api/recruiter/job-offers', [
        'auth' => 'recruiter_token',
        'body' => ['title' => 'Peintre', 'location' => 'Be', 'daily_rate' => 6000, 'start_date' => '2026-08-10', 'people_needed' => 2],
        'tests' => [assertStatus(201), captureId('job_offer_id')],
    ]),
    register('cand', 'job_seeker', 'seeker_token'),
    req('03. Chercheur cree son profil', 'POST', 'api/job-seeker', ['auth' => 'seeker_token', 'body' => ['profession' => 'peintre', 'skills' => 'peinture', 'experience' => '2 ans', 'availability' => 'immediate'], 'tests' => [assertStatus(201)]]),
    req('04. Chercheur postule (201)', 'POST', 'api/job-offers/{{job_offer_id}}/apply', ['auth' => 'seeker_token', 'tests' => [assertStatus(201), captureId('application_id')]]),
    req('05. Chercheur liste ses candidatures', 'GET', 'api/job-seeker/applications', ['auth' => 'seeker_token', 'tests' => [assertStatus(200)]]),
    req('06. Recruteur voit les candidatures de l offre', 'GET', 'api/recruiter/job-offers/{{job_offer_id}}/applications', ['auth' => 'recruiter_token', 'tests' => [assertStatus(200)]]),
    req('07. Recruteur accepte (200)', 'PATCH', 'api/recruiter/applications/{{application_id}}/status', ['auth' => 'recruiter_token', 'body' => ['status' => 'accepted'], 'tests' => [assertStatus(200)]]),
    req('08. Recruteur clot (completed)', 'PATCH', 'api/recruiter/applications/{{application_id}}/status', ['auth' => 'recruiter_token', 'body' => ['status' => 'completed'], 'tests' => [assertStatus(200)]]),
]);

// --- Messagerie (parcours) --------------------------------------------------
$folders[] = folder('Messagerie', [
    register('msg', 'client', 'msg_a_token', 'msg_a_user_id'),
    register('msg', 'vendor', 'msg_b_token', 'msg_b_user_id'),
    req('1. A envoie a B (201)', 'POST', 'api/messages', [
        'auth' => 'msg_a_token',
        'body' => ['receiver_id' => '{{msg_b_user_id}}', 'content' => 'Bonjour, cet article est-il dispo ?'],
        'tests' => [assertStatus(201)],
    ]),
    req('2. B liste ses conversations (200)', 'GET', 'api/conversations', ['auth' => 'msg_b_token', 'tests' => [assertStatus(200)]]),
    req('3. B ouvre le fil avec A (marque lu) (200)', 'GET', 'api/messages/{{msg_a_user_id}}', ['auth' => 'msg_b_token', 'tests' => [assertStatus(200)]]),
    req('4. B repond a A (201)', 'POST', 'api/messages', [
        'auth' => 'msg_b_token',
        'body' => ['receiver_id' => '{{msg_a_user_id}}', 'content' => 'Oui, en stock.'],
        'tests' => [assertStatus(201)],
    ]),
    req('5. A: compteur de messages non-lus (200)', 'GET', 'api/messages/unread-count', ['auth' => 'msg_a_token', 'tests' => [assertStatus(200)]]),
]);

// --- Notifications ----------------------------------------------------------
$folders[] = folder('Notifications', [
    req('1. Lister mes notifications (200)', 'GET', 'api/notifications', ['auth' => 'client_token', 'tests' => [assertStatus(200), captureFirstDataId('notification_id')]]),
    req('2. Filtrer non-lues (200)', 'GET', 'api/notifications', ['auth' => 'client_token', 'query' => ['unread' => '1'], 'tests' => [assertStatus(200)]]),
    req('3. Compteur non-lues (200)', 'GET', 'api/notifications/unread-count', ['auth' => 'client_token', 'tests' => [assertStatus(200)]]),
    req('4. Marquer une comme lue (200)', 'PATCH', 'api/notifications/{{notification_id}}/read', ['auth' => 'client_token', 'tests' => [assertStatus(200)]]),
    req('5. Tout marquer lu (200)', 'POST', 'api/notifications/read-all', ['auth' => 'client_token', 'tests' => [assertStatus(200)]]),
]);

// --- Evaluations ------------------------------------------------------------
$folders[] = folder('Evaluations', [
    req('1. Noter un vendeur (201)', 'POST', 'api/ratings', [
        'auth' => 'client_token',
        'body' => ['target_type' => 'vendor', 'target_id' => '{{vendor_id}}', 'score' => 4, 'comment' => 'Bon service'],
        'tests' => [assertStatus(201)],
    ]),
    req('2. Notes d un vendeur (moyenne + count) (200)', 'GET', 'api/ratings/vendor/{{vendor_id}}', ['auth' => 'client_token', 'tests' => [assertStatus(200)]]),
    req('3. Mes evaluations (200)', 'GET', 'api/my-ratings', ['auth' => 'client_token', 'tests' => [assertStatus(200)]]),
]);

// --- Paiements (PayGate Global, parcours auto-suffisant) --------------------
// Self-contained: builds its own order so the payer owns the reference,
// independent of client_token being reassigned by earlier folders.
$folders[] = folder('Paiements', [
    register('pay', 'vendor', 'pay_vendor_token'),
    req('01. Vendeur cree son profil', 'POST', 'api/vendor', ['auth' => 'pay_vendor_token', 'body' => ['shop_name' => 'Boutique pay {{$randomInt}}'], 'tests' => [assertStatus(201), captureId('pay_vendor_id')]]),
    req('02. Vendeur cree un produit', 'POST', 'api/vendor/products', ['auth' => 'pay_vendor_token', 'body' => ['name' => 'Sucre', 'price' => 3000, 'stock' => 100], 'tests' => [assertStatus(201), captureId('pay_product_id')]]),
    register('pay', 'client', 'pay_client_token'),
    req('03. Client passe commande', 'POST', 'api/orders', [
        'auth' => 'pay_client_token',
        'body' => ['vendor_id' => '{{pay_vendor_id}}', 'delivery_address' => 'Lome', 'items' => [['product_id' => '{{pay_product_id}}', 'quantity' => 3]]],
        'tests' => [assertStatus(201), captureId('pay_order_id')],
    ]),
    req('04. Payer la commande - flooz (201)', 'POST', 'api/payments', [
        'auth' => 'pay_client_token',
        'body' => ['reference_type' => 'order', 'reference_id' => '{{pay_order_id}}', 'payment_method' => 'flooz', 'phone_number' => '{{paygate_test_phone}}'],
        'tests' => [
            "pm.test('201 (push envoye) ou 502 (refus PayGate)', function () { pm.expect([201, 502]).to.include(pm.response.code); });",
            "{ const j = pm.response.json(); if (j.payment && j.payment.id) pm.collectionVariables.set('payment_id', j.payment.id); }",
        ],
        'desc' => 'Le montant est calcule serveur depuis la commande ; ne pas l envoyer dans le body. Moyens acceptes : flooz | tmoney. Un vrai push USSD part vers PayGate : renseignez {{paygate_test_phone}} avec un numero mobile money valide, sinon la reponse est 502.',
    ]),
    req('05. Verifier l etat du paiement (200)', 'POST', 'api/payments/{{payment_id}}/check', [
        'auth' => 'pay_client_token',
        'tests' => ["pm.test('200 (etat lu) ou 502 (gateway injoignable)', function () { pm.expect([200, 502]).to.include(pm.response.code); });"],
        'desc' => "Interroge PayGate (/api/v1/status). Tant que le client n a pas valide sur son telephone, le paiement reste 'pending'.",
    ]),
    req('06. Lister mes paiements (200)', 'GET', 'api/payments', ['auth' => 'pay_client_token', 'tests' => [assertStatus(200)]]),
    req('07. Filtrer par statut (200)', 'GET', 'api/payments', ['auth' => 'pay_client_token', 'query' => ['status' => 'success'], 'tests' => [assertStatus(200)]]),
    req('08. Voir un paiement (200)', 'GET', 'api/payments/{{payment_id}}', ['auth' => 'pay_client_token', 'tests' => [assertStatus(200)]]),
]);

// ---------------------------------------------------------------------------
// Collection variables
// ---------------------------------------------------------------------------

$variableKeys = [
    'base_url' => 'http://127.0.0.1:8000',
    'token' => '',
    'vendor_token' => '', 'client_token' => '', 'driver_token' => '',
    'owner_token' => '', 'recruiter_token' => '', 'seeker_token' => '',
    'vendor_id' => '', 'product_id' => '', 'order_id' => '', 'delivery_id' => '',
    'property_id' => '', 'reservation_id' => '',
    'job_offer_id' => '', 'application_id' => '',
    'notification_id' => '', 'payment_id' => '',
    'paygate_test_phone' => '90000000',
    'otp_phone' => '90000000', 'otp_code' => '', 'otp_token' => '', 'otp_user_token' => '',
    'msg_a_token' => '', 'msg_b_token' => '', 'msg_a_user_id' => '', 'msg_b_user_id' => '',
    'client_user_id' => '', 'vendor_user_id' => '',
    'pay_vendor_token' => '', 'pay_client_token' => '', 'pay_vendor_id' => '', 'pay_product_id' => '', 'pay_order_id' => '',
];
$variables = [];
foreach ($variableKeys as $k => $v) {
    $variables[] = ['key' => $k, 'value' => $v];
}

$collection = [
    'info' => [
        'name' => 'TELU BAOBAB API',
        'description' => 'Collection generee par postman/build_collection.php — ne pas editer a la main. Couvre auth, les 5 profils metier, Commerce/Livraison, Immobilier, Emploi, Notifications, Messagerie, Evaluations et Paiements (PayGate Global — Flooz / TMoney).',
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
    ],
    'auth' => ['type' => 'bearer', 'bearer' => [['key' => 'token', 'value' => '{{token}}', 'type' => 'string']]],
    'variable' => $variables,
    'item' => $folders,
];

$out = __DIR__.'/TELU-BAOBAB.postman_collection.json';
file_put_contents($out, json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");

$count = 0;
foreach ($folders as $f) {
    $count += count($f['item']);
}
echo 'Wrote '.count($folders).' folders, '.$count.' requests to '.$out.PHP_EOL;
