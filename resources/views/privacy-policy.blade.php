<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index, follow">
    <meta name="description" content="Politique de confidentialité de TELU BAOBAB — informations collectées, géolocalisation, paiements, vos droits et coordonnées de contact.">
    <title>Politique de confidentialité – TELU BAOBAB</title>
    <style>
        :root {
            --color-bg: #f8fafc;
            --color-surface: #ffffff;
            --color-text: #1e293b;
            --color-text-muted: #64748b;
            --color-accent: #0f766e;
            --color-accent-soft: #f0fdfa;
            --color-border: #e2e8f0;
            --color-heading: #0f172a;
            --radius: 10px;
            --shadow: 0 1px 3px rgba(15, 23, 42, 0.06), 0 8px 24px rgba(15, 23, 42, 0.05);
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 16px;
            line-height: 1.7;
            color: var(--color-text);
            background-color: var(--color-bg);
            -webkit-font-smoothing: antialiased;
        }

        a {
            color: var(--color-accent);
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 24px 20px 64px;
        }

        /* Header */
        .page-header {
            background: var(--color-surface);
            border-bottom: 1px solid var(--color-border);
        }

        .page-header .container {
            padding-bottom: 24px;
        }

        .page-header h1 {
            margin: 0 0 8px;
            font-size: 1.9rem;
            line-height: 1.2;
            color: var(--color-heading);
        }

        .page-header p {
            margin: 0;
            color: var(--color-text-muted);
        }

        .badge {
            display: inline-block;
            margin-bottom: 14px;
            padding: 4px 12px;
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--color-accent);
            background: var(--color-accent-soft);
            border: 1px solid #ccfbf1;
            border-radius: 999px;
        }

        /* Layout: sidebar (nav) + content */
        .page-body {
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr);
            gap: 40px;
            margin-top: 32px;
            align-items: start;
        }

        .toc {
            position: sticky;
            top: 24px;
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 16px 8px;
            max-height: calc(100vh - 48px);
            overflow-y: auto;
        }

        .toc-title {
            margin: 0 12px 8px;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--color-text-muted);
        }

        .toc ol {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .toc a {
            display: block;
            padding: 6px 12px;
            font-size: 0.9rem;
            color: var(--color-text);
            border-radius: 6px;
        }

        .toc a:hover {
            background: var(--color-accent-soft);
            color: var(--color-accent);
            text-decoration: none;
        }

        details.toc {
            display: none;
        }

        /* Article content */
        .content {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 32px 36px;
        }

        .content section {
            scroll-margin-top: 24px;
        }

        .content h2 {
            margin: 0 0 16px;
            padding-bottom: 10px;
            font-size: 1.35rem;
            color: var(--color-heading);
            border-bottom: 2px solid var(--color-accent-soft);
        }

        .content h3 {
            margin: 24px 0 8px;
            font-size: 1.05rem;
            color: var(--color-heading);
        }

        .content p {
            margin: 0 0 16px;
        }

        .content ul,
        .content ol {
            margin: 0 0 16px;
            padding-left: 24px;
        }

        .content li {
            margin-bottom: 6px;
        }

        .content strong {
            color: var(--color-heading);
        }

        .intro {
            margin-bottom: 32px;
            padding: 16px 20px;
            background: var(--color-accent-soft);
            border-left: 4px solid var(--color-accent);
            border-radius: 0 var(--radius) var(--radius) 0;
        }

        .intro p:last-child {
            margin-bottom: 0;
        }

        table.meta {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
            font-size: 0.95rem;
        }

        table.meta th,
        table.meta td {
            text-align: left;
            padding: 10px 12px;
            border: 1px solid var(--color-border);
        }

        table.meta th {
            width: 38%;
            background: var(--color-accent-soft);
            color: var(--color-heading);
            font-weight: 600;
        }

        /* Footer */
        .page-footer {
            border-top: 1px solid var(--color-border);
            background: var(--color-surface);
            color: var(--color-text-muted);
            font-size: 0.88rem;
        }

        .page-footer .container {
            padding: 24px 20px;
            text-align: center;
        }

        .page-footer a {
            color: var(--color-accent);
        }

        /* Responsive */
        @media (max-width: 880px) {
            .page-body {
                grid-template-columns: 1fr;
                gap: 16px;
                margin-top: 20px;
            }

            .toc:not(details) {
                display: none;
            }

            details.toc {
                display: block;
            }

            details.toc summary {
                cursor: pointer;
                font-weight: 600;
                color: var(--color-accent);
                padding: 12px 12px;
                user-select: none;
                border-radius: 6px;
                -webkit-tap-highlight-color: transparent;
            }

            details.toc summary:hover {
                background: var(--color-accent-soft);
            }

            details.toc[open] summary {
                margin-bottom: 8px;
            }

            details.toc ol {
                border-top: 1px solid var(--color-border);
                padding-top: 8px;
            }

            details.toc a {
                padding: 10px 12px;
                -webkit-tap-highlight-color: transparent;
            }

            .content {
                padding: 24px 20px;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }
        }

        /* Very small phones */
        @media (max-width: 480px) {
            .container {
                padding: 16px 12px 48px;
            }

            .page-header .container {
                padding-bottom: 16px;
            }

            .content {
                padding: 20px 16px;
            }

            .content h2 {
                font-size: 1.2rem;
            }

            .content ul,
            .content ol {
                padding-left: 20px;
            }

            /* Stack the contact table rows on narrow screens */
            table.meta,
            table.meta tbody,
            table.meta tr,
            table.meta th,
            table.meta td {
                display: block;
                width: 100%;
            }

            table.meta th {
                width: 100%;
                border-bottom: none;
            }

            table.meta td {
                border-top: none;
            }

            table.meta tr + tr {
                border-top: 1px solid var(--color-border);
            }

            /* Prevent long words / URLs from overflowing */
            table.meta td,
            .content p {
                word-break: break-word;
                overflow-wrap: anywhere;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            html {
                scroll-behavior: auto;
            }
        }
    </style>
</head>
<body>
    <header class="page-header">
        <div class="container">
            <span class="badge">Confidentialité</span>
            <h1>Politique de confidentialité de TELU BAOBAB</h1>
            <p>Dernière mise à jour : 19 juillet 2026</p>
        </div>
    </header>

    <div class="container">
        <div class="page-body">
            <nav class="toc" aria-label="Sommaire">
                <p class="toc-title">Sommaire</p>
                <ol>
                    <li><a href="#introduction">1. Introduction</a></li>
                    <li><a href="#informations-collectees">2. Informations collectées</a></li>
                    <li><a href="#informations-utilisateur">3. Informations fournies par l'utilisateur</a></li>
                    <li><a href="#geolocalisation">4. Géolocalisation</a></li>
                    <li><a href="#donnees-automatiques">5. Données collectées automatiquement</a></li>
                    <li><a href="#utilisation-donnees">6. Utilisation des données</a></li>
                    <li><a href="#paiements">7. Paiements</a></li>
                    <li><a href="#base-legale">8. Base légale du traitement</a></li>
                    <li><a href="#partage-tiers">9. Partage des données avec des tiers</a></li>
                    <li><a href="#services-tiers">10. Services tiers utilisés</a></li>
                    <li><a href="#conservation">11. Conservation des données</a></li>
                    <li><a href="#securite">12. Sécurité des données</a></li>
                    <li><a href="#droits-utilisateurs">13. Droits des utilisateurs</a></li>
                    <li><a href="#suppression">14. Suppression du compte et des données</a></li>
                    <li><a href="#cookies">15. Cookies et technologies similaires</a></li>
                    <li><a href="#mineurs">16. Données des mineurs</a></li>
                    <li><a href="#transferts-internationaux">17. Transferts internationaux de données</a></li>
                    <li><a href="#modifications">18. Modifications de la politique</a></li>
                    <li><a href="#contact">19. Contact</a></li>
                    <li><a href="#date-mise-a-jour">20. Date de dernière mise à jour</a></li>
                </ol>
            </nav>

            <details class="toc" aria-label="Sommaire">
                <summary>Sommaire</summary>
                <ol>
                    <li><a href="#introduction">1. Introduction</a></li>
                    <li><a href="#informations-collectees">2. Informations collectées</a></li>
                    <li><a href="#informations-utilisateur">3. Informations fournies par l'utilisateur</a></li>
                    <li><a href="#geolocalisation">4. Géolocalisation</a></li>
                    <li><a href="#donnees-automatiques">5. Données collectées automatiquement</a></li>
                    <li><a href="#utilisation-donnees">6. Utilisation des données</a></li>
                    <li><a href="#paiements">7. Paiements</a></li>
                    <li><a href="#base-legale">8. Base légale du traitement</a></li>
                    <li><a href="#partage-tiers">9. Partage des données avec des tiers</a></li>
                    <li><a href="#services-tiers">10. Services tiers utilisés</a></li>
                    <li><a href="#conservation">11. Conservation des données</a></li>
                    <li><a href="#securite">12. Sécurité des données</a></li>
                    <li><a href="#droits-utilisateurs">13. Droits des utilisateurs</a></li>
                    <li><a href="#suppression">14. Suppression du compte et des données</a></li>
                    <li><a href="#cookies">15. Cookies et technologies similaires</a></li>
                    <li><a href="#mineurs">16. Données des mineurs</a></li>
                    <li><a href="#transferts-internationaux">17. Transferts internationaux de données</a></li>
                    <li><a href="#modifications">18. Modifications de la politique</a></li>
                    <li><a href="#contact">19. Contact</a></li>
                    <li><a href="#date-mise-a-jour">20. Date de dernière mise à jour</a></li>
                </ol>
            </details>

            <main class="content" role="main">
                <div class="intro">
                    <p>
                        Cette politique de confidentialité décrit la manière dont TELU BAOBAB
                        (ci-après « nous », « notre » ou « nos ») collecte, utilise, conserve et protège les données personnelles
                        des utilisateurs de l'application TELU BAOBAB (ci-après « l'application »).
                    </p>
                </div>

                <section id="introduction">
                    <h2>1. Introduction</h2>
                    <p>
                        Nous attachons une grande importance à la protection de votre vie privée et à la confidentialité de vos données personnelles.
                        La présente politique a pour objet de vous informer, de manière claire et transparente, sur les traitements de données
                        que nous mettons en œuvre lorsque vous utilisez l'application, conformément aux réglementations applicables en matière de
                        protection des données personnelles.
                    </p>
                    <p>
                        En utilisant l'application, vous reconnaissez avoir pris connaissance de la présente politique de confidentialité.
                        Si vous n'êtes pas d'accord avec les termes de cette politique, veuillez ne pas utiliser l'application.
                    </p>
                </section>

                <section id="informations-collectees">
                    <h2>2. Informations collectées</h2>
                    <p>
                        Nous collectons les informations fournies lors de l'inscription (nom, numéro de téléphone, e-mail), les données de profil
                        professionnel (produits, annonces, zone de couverture), ainsi que les données nécessaires au fonctionnement du service :
                        commandes, livraisons, réservations, candidatures et messages échangés.
                    </p>
                    <p>
                        Ces informations se répartissent en deux catégories : les informations que vous nous fournissez directement et les données
                        collectées automatiquement lors de votre utilisation. Les données effectivement collectées peuvent varier selon le type de
                        compte créé (client, vendeur, livreur, propriétaire, recruteur, candidat).
                    </p>
                </section>

                <section id="informations-utilisateur">
                    <h2>3. Informations fournies par l'utilisateur</h2>
                    <ul>
                        <li><strong>Données d'inscription :</strong> nom, numéro de téléphone, adresse e-mail, type de compte.</li>
                        <li><strong>Données de profil professionnel :</strong> produits, annonces, zone de couverture, ainsi que les informations propres à votre profil (vendeur, livreur, propriétaire, recruteur, candidat).</li>
                        <li><strong>Données de transaction :</strong> commandes, livraisons, réservations, candidatures, évaluations et messages échangés avec d'autres utilisateurs.</li>
                        <li><strong>Adresses de livraison :</strong> adresses que vous enregistrez pour vos commandes.</li>
                        <li><strong>Données de paiement :</strong> numéro de téléphone associé à votre moyen de paiement. Nous ne stockons pas les données complètes de vos cartes bancaires sur nos serveurs.</li>
                    </ul>
                </section>

                <section id="geolocalisation">
                    <h2>4. Géolocalisation</h2>
                    <p>
                        Avec votre autorisation, votre position est utilisée pour localiser les vendeurs, hôtels et travailleurs proches de vous,
                        et pour suivre les livraisons en temps réel. Vous pouvez désactiver le partage de position à tout moment dans les paramètres,
                        certaines fonctionnalités pouvant alors être limitées.
                    </p>
                </section>

                <section id="donnees-automatiques">
                    <h2>5. Données collectées automatiquement</h2>
                    <p>
                        Lorsque vous utilisez l'application, certaines informations sont collectées automatiquement afin d'assurer son bon fonctionnement
                        et d'améliorer votre expérience :
                    </p>
                    <ul>
                        <li><strong>Données de localisation :</strong> coordonnées GPS, lorsque vous utilisez les fonctionnalités liées à la géolocalisation (localisation de proximité, suivi des livraisons, calcul des frais de livraison).</li>
                        <li><strong>Données techniques :</strong> type d'appareil, système d'exploitation, version de l'application, identifiants techniques.</li>
                        <li><strong>Journaux de connexion et d'utilisation :</strong> date et heure de connexion, pages et fonctionnalités consultées.</li>
                    </ul>
                </section>

                <section id="utilisation-donnees">
                    <h2>6. Utilisation des données</h2>
                    <p>Vos données servent à fournir et améliorer les services :</p>
                    <ul>
                        <li>La mise en relation entre les utilisateurs (clients, vendeurs, livreurs, propriétaires, recruteurs, candidats) ;</li>
                        <li>Le traitement des commandes et des paiements ;</li>
                        <li>Les notifications de nouvelles opportunités et les informations liées à votre compte ;</li>
                        <li>La prévention de la fraude et la sécurité de l'application ;</li>
                        <li>Le support client et l'amélioration de nos services.</li>
                    </ul>
                    <p>
                        <strong>Nous ne vendons pas vos données personnelles à des tiers.</strong>
                    </p>
                </section>

                <section id="paiements">
                    <h2>7. Paiements</h2>
                    <p>
                        Les transactions via Flooz, TMoney, Mobile Money ou carte bancaire sont traitées par des prestataires de paiement agréés.
                        Nous ne stockons pas les données complètes de vos cartes bancaires sur nos serveurs.
                    </p>
                </section>

                <section id="base-legale">
                    <h2>8. Base légale du traitement</h2>
                    <p>Nous traitons vos données personnelles sur les bases légales suivantes :</p>
                    <ul>
                        <li><strong>L'exécution du contrat :</strong> le traitement de vos données est nécessaire à la fourniture des services que vous avez demandés (création de compte, commandes, livraisons, réservations, candidatures, etc.) ;</li>
                        <li><strong>Votre consentement :</strong> notamment pour le partage de votre position, consentement que vous pouvez retirer à tout moment ;</li>
                        <li><strong>Nos intérêts légitimes :</strong> tels que la sécurité, la prévention de la fraude et l'amélioration de nos services ;</li>
                        <li><strong>Le respect d'obligations légales :</strong> lorsque la loi l'exige.</li>
                    </ul>
                </section>

                <section id="partage-tiers">
                    <h2>9. Partage des données avec des tiers</h2>
                    <p>
                        Certaines informations sont partagées entre utilisateurs uniquement dans la mesure nécessaire au service :
                    </p>
                    <ul>
                        <li>Le livreur voit l'adresse de récupération et de livraison ;</li>
                        <li>Le recruteur voit le profil des candidats ;</li>
                        <li>Le client voit les informations publiques du vendeur.</li>
                    </ul>
                    <p>
                        Nous ne vendons pas vos données personnelles. Elles peuvent par ailleurs être communiquées à des prestataires de services
                        agissant pour notre compte (hébergement, paiement, envoi de SMS), ainsi qu'aux autorités lorsque la loi l'exige.
                    </p>
                </section>

                <section id="services-tiers">
                    <h2>10. Services tiers utilisés</h2>
                    <p>
                        L'application s'appuie sur des services tiers pour certaines fonctionnalités. Ces tiers n'accèdent à vos données que dans la
                        mesure nécessaire à l'exécution de leur mission et sont tenus à une obligation de confidentialité :
                    </p>
                    <ul>
                        <li><strong>Prestataires de paiement agréés</strong> pour le traitement des transactions (Flooz, TMoney, Mobile Money, carte bancaire) ;</li>
                        <li><strong>Prestataires d'envoi de SMS</strong> (codes de vérification, notifications par SMS) ;</li>
                        <li><strong>Hébergement et stockage</strong> des données de l'application ;</li>
                        <li><strong>Outils d'analyse et de suivi des erreurs</strong>.</li>
                    </ul>
                    <p>
                        <em>Liste des services tiers à compléter :</em>
                        <span style="display:inline-block;padding:0 6px;font-family:SFMono-Regular,Consolas,Menlo,monospace;font-size:.85em;background:#fef3c7;border:1px solid #fde68a;border-radius:4px;color:#92400e;white-space:nowrap;">[LISTE DES SERVICES TIERS]</span>
                    </p>
                </section>

                <section id="conservation">
                    <h2>11. Conservation des données</h2>
                    <p>
                        Les données sont conservées pendant la durée d'utilisation du service et les délais légaux applicables. En particulier :
                    </p>
                    <ul>
                        <li>Les données de compte sont conservées tant que votre compte est actif ;</li>
                        <li>Les données de transaction (commandes, paiements, réservations, candidatures) sont conservées pendant la durée légale applicable ;</li>
                        <li>Les journaux techniques sont conservés pendant une durée limitée.</li>
                    </ul>
                    <p>
                        À l'expiration de ces durées, vos données sont supprimées ou anonymisées de manière irréversible.
                    </p>
                </section>

                <section id="securite">
                    <h2>12. Sécurité des données</h2>
                    <p>
                        Nous mettons en œuvre des mesures techniques et organisationnelles pour protéger vos données contre l'accès non autorisé,
                        la perte ou l'altération. Ces mesures incluent notamment :
                    </p>
                    <ul>
                        <li>Le chiffrement des données sensibles ;</li>
                        <li>Le contrôle des accès aux systèmes ;</li>
                        <li>La limitation des accès aux seules personnes autorisées ;</li>
                        <li>La supervision et la surveillance de nos systèmes.</li>
                    </ul>
                    <p>
                        Aucun système n'est infaillible. Nous ne pouvons donc garantir une sécurité absolue, mais nous nous engageons à mettre en œuvre
                        tous les moyens raisonnables pour protéger vos données.
                    </p>
                </section>

                <section id="droits-utilisateurs">
                    <h2>13. Droits des utilisateurs</h2>
                    <p>
                        Conformément à la réglementation applicable, vous disposez des droits suivants sur vos données personnelles :
                    </p>
                    <ul>
                        <li><strong>Droit d'accès :</strong> obtenir une copie des données que nous détenons à votre sujet ;</li>
                        <li><strong>Droit de rectification :</strong> corriger des données inexactes ou incomplètes ;</li>
                        <li><strong>Droit à l'effacement :</strong> demander la suppression de vos données dans les conditions prévues par la loi ;</li>
                        <li><strong>Droit à la limitation :</strong> restreindre le traitement de vos données dans certains cas ;</li>
                        <li><strong>Droit à la portabilité :</strong> recevoir vos données dans un format structuré, couramment utilisé et lisible par machine ;</li>
                        <li><strong>Droit d'opposition :</strong> vous opposer, pour des raisons légitimes, au traitement de vos données ;</li>
                        <li><strong>Droit de retirer votre consentement :</strong> à tout moment, notamment pour le partage de votre position, lorsque le traitement repose sur votre consentement ;</li>
                        <li><strong>Droit d'introduire une réclamation :</strong> auprès de l'autorité de protection des données compétente.</li>
                    </ul>
                    <p>
                        Pour exercer ces droits, contactez-nous à <a href="mailto:support@telubaobab.com">support@telubaobab.com</a>
                        ou via la rubrique Aide et support de l'application. Nous vous répondrons dans les meilleurs délais et, en tout état de cause,
                        dans les délais prévus par la loi.
                    </p>
                </section>

                <section id="suppression">
                    <h2>14. Suppression du compte et des données</h2>
                    <p>
                        Vous pouvez demander la suppression de votre compte et de vos données personnelles à tout moment, en nous contactant à
                        <a href="mailto:support@telubaobab.com">support@telubaobab.com</a> ou via la rubrique Aide et support de l'application.
                    </p>
                    <p>
                        La suppression de votre compte entraîne la suppression ou l'anonymisation de vos données personnelles, sous réserve des données
                        que nous sommes tenus de conserver en vertu de nos obligations légales (par exemple, les données de facturation ou de transaction)
                        et des informations conservées dans un intérêt légitime.
                    </p>
                </section>

                <section id="cookies">
                    <h2>15. Cookies et technologies similaires</h2>
                    <p>
                        L'application peut utiliser des cookies ou des technologies similaires pour assurer son fonctionnement, mémoriser vos préférences
                        et mesurer son audience. Vous pouvez configurer votre navigateur ou votre appareil pour refuser les cookies ou être informé
                        de leur dépôt.
                    </p>
                    <p>
                        <em>Description à compléter le cas échéant :</em>
                        <span style="display:inline-block;padding:0 6px;font-family:SFMono-Regular,Consolas,Menlo,monospace;font-size:.85em;background:#fef3c7;border:1px solid #fde68a;border-radius:4px;color:#92400e;white-space:nowrap;">[LISTE DES COOKIES OU TECHNOLOGIES UTILISÉS]</span>
                    </p>
                </section>

                <section id="mineurs">
                    <h2>16. Données des mineurs</h2>
                    <p>
                        L'application n'est pas destinée aux personnes mineures et nous ne collectons pas sciemment de données personnelles les concernant.
                        Si vous estimez qu'un mineur nous a fourni des données personnelles sans l'autorisation d'un représentant légal,
                        contactez-nous à <a href="mailto:support@telubaobab.com">support@telubaobab.com</a> afin que nous puissions supprimer ces données.
                    </p>
                </section>

                <section id="transferts-internationaux">
                    <h2>17. Transferts internationaux de données</h2>
                    <p>
                        Dans le cadre de l'utilisation de prestataires de services, vos données peuvent être hébergées ou traitées en dehors de votre
                        pays de résidence. Dans ce cas, nous nous assurons que des garanties appropriées sont mises en place pour protéger vos données,
                        conformément à la réglementation applicable.
                    </p>
                    <p>
                        <em>Pays d'hébergement et garanties à compléter :</em>
                        <span style="display:inline-block;padding:0 6px;font-family:SFMono-Regular,Consolas,Menlo,monospace;font-size:.85em;background:#fef3c7;border:1px solid #fde68a;border-radius:4px;color:#92400e;white-space:nowrap;">[LIEU D'HÉBERGEMENT DES DONNÉES]</span>
                    </p>
                </section>

                <section id="modifications">
                    <h2>18. Modifications de la politique</h2>
                    <p>
                        Nous pouvons être amenés à modifier la présente politique de confidentialité afin de refléter l'évolution de nos services,
                        de la technologie ou de nos obligations légales. La version en vigueur sera toujours accessible via l'application et comportera
                        sa date de dernière mise à jour. Nous vous informerons de toute modification substantielle.
                    </p>
                </section>

                <section id="contact">
                    <h2>19. Contact</h2>
                    <p>Pour toute question relative à la présente politique de confidentialité ou à la gestion de vos données personnelles, vous pouvez nous contacter :</p>
                    <table class="meta">
                        <tbody>
                            <tr>
                                <th>Application</th>
                                <td>TELU BAOBAB</td>
                            </tr>
                            <tr>
                                <th>Adresse e-mail</th>
                                <td><a href="mailto:support@telubaobab.com">support@telubaobab.com</a></td>
                            </tr>
                            <tr>
                                <th>Rubrique Aide et support</th>
                                <td>Disponible directement dans l'application</td>
                            </tr>
                            <tr>
                                <th>Site web</th>
                                <td><a href="http://telu3.com">telu3.com</a></td>
                            </tr>
                            <tr>
                                <th>Adresse postale</th>
                                <td><span style="display:inline-block;padding:0 6px;font-family:SFMono-Regular,Consolas,Menlo,monospace;font-size:.85em;background:#fef3c7;border:1px solid #fde68a;border-radius:4px;color:#92400e;white-space:nowrap;">[ADRESSE POSTALE]</span></td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                <section id="date-mise-a-jour">
                    <h2>20. Date de dernière mise à jour</h2>
                    <p>
                        La présente politique de confidentialité a été mise à jour pour la dernière fois le 19 juillet 2026.
                    </p>
                </section>
            </main>
        </div>
    </div>

    <footer class="page-footer">
        <div class="container">
            <p>
                &copy; 2026 TELU BAOBAB — Tous droits réservés.
                <br>
                <a href="http://telu3.com">telu3.com</a> · Politique de confidentialité · <a href="/">Accueil</a>
            </p>
        </div>
    </footer>
</body>
</html>
