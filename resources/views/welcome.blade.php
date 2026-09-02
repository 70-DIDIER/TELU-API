<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index, follow">
    <meta name="description" content="TELU BAOBAB — la super-app togolaise : commerce & livraison, immobilier et emploi journalier, réunis dans une seule application avec paiement mobile money.">
    <title>TELU BAOBAB — La super-app du Togo</title>
    <link rel="icon" href="{{ asset('images/logo-full.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-bg: #ffffff;
            --color-surface: #f4f6f9;
            --color-primary: #081129;
            --color-text: #0a1229;
            --color-text-muted: #5a5f6e;
            --color-accent: #316ef3;
            --color-accent-soft: #eaf1fe;
            --color-border: #e3e7ec;
            --color-success: #1fb57a;
            --color-success-soft: #e4f7ef;
            --radius: 16px;
            --radius-lg: 28px;
            --shadow: 0 12px 32px rgba(8, 17, 41, 0.08);
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            font-family: 'Urbanist', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--color-text);
            background: var(--color-bg);
            -webkit-font-smoothing: antialiased;
            line-height: 1.6;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        img {
            max-width: 100%;
            display: block;
        }

        .container {
            max-width: 1160px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* Header */
        .site-header {
            position: sticky;
            top: 0;
            z-index: 20;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: saturate(180%) blur(10px);
            border-bottom: 1px solid var(--color-border);
        }

        .site-header .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-block: 14px;
        }

        .brand img {
            height: 34px;
            width: auto;
        }

        .nav-links {
            display: none;
            align-items: center;
            gap: 28px;
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--color-text);
        }

        .nav-links a:hover {
            color: var(--color-accent);
        }

        @media (min-width: 720px) {
            .nav-links {
                display: flex;
            }
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.95rem;
            transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
            white-space: nowrap;
        }

        .btn-primary {
            background: var(--color-accent);
            color: #fff;
            box-shadow: 0 10px 24px rgba(49, 110, 243, 0.28);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 28px rgba(49, 110, 243, 0.34);
        }

        .btn-ghost {
            background: var(--color-accent-soft);
            color: var(--color-accent);
        }

        .btn-ghost:hover {
            background: #dbe8fd;
        }

        .header-cta {
            display: none;
        }

        @media (min-width: 720px) {
            .header-cta {
                display: inline-flex;
            }
        }

        /* Store badges */
        .store-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .store-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 18px;
            border-radius: 12px;
            background: var(--color-primary);
            color: #fff;
            transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
        }

        .store-badge:hover {
            transform: translateY(-1px);
            background: #14203f;
        }

        .store-badge svg {
            width: 22px;
            height: 22px;
            flex-shrink: 0;
        }

        .store-badge .store-badge-text {
            display: flex;
            flex-direction: column;
            line-height: 1.15;
        }

        .store-badge small {
            font-size: 0.68rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.72);
        }

        .store-badge strong {
            font-size: 0.98rem;
            font-weight: 700;
        }

        .store-badge.on-dark {
            background: rgba(255, 255, 255, 0.12);
        }

        .store-badge.on-dark:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Hero */
        .hero {
            position: relative;
            overflow: hidden;
            padding-block: 72px 96px;
            background: radial-gradient(circle at 85% -10%, var(--color-accent-soft) 0%, rgba(234, 241, 254, 0) 55%);
        }

        .hero .container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 48px;
            align-items: center;
        }

        @media (min-width: 960px) {
            .hero .container {
                grid-template-columns: 1.05fr 0.95fr;
            }
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 999px;
            background: var(--color-accent-soft);
            color: var(--color-accent);
            font-weight: 700;
            font-size: 0.8rem;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        .hero h1 {
            font-size: clamp(2.1rem, 4.2vw, 3.4rem);
            font-weight: 800;
            line-height: 1.1;
            margin: 0 0 20px;
            color: var(--color-primary);
        }

        .hero h1 span {
            color: var(--color-accent);
        }

        .hero p.lead {
            font-size: 1.15rem;
            color: var(--color-text-muted);
            max-width: 520px;
            margin: 0 0 32px;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 36px;
        }

        .hero-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 32px;
        }

        .hero-stats div strong {
            display: block;
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--color-primary);
        }

        .hero-stats div span {
            font-size: 0.85rem;
            color: var(--color-text-muted);
        }

        .hero-art {
            position: relative;
            display: flex;
            justify-content: center;
        }

        .hero-art .mark-badge {
            width: min(420px, 88vw);
            border-radius: 32px;
            background: linear-gradient(160deg, var(--color-primary), #14203f);
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 40px;
        }

        .hero-art .mark-badge img {
            width: 100%;
            height: auto;
            filter: brightness(0) invert(1);
        }

        .hero-art .float-card {
            position: absolute;
            background: #fff;
            border-radius: 16px;
            box-shadow: var(--shadow);
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .hero-art .float-card .dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: var(--color-success);
            flex-shrink: 0;
        }

        .hero-art .float-card.one {
            top: 6%;
            left: -4%;
        }

        .hero-art .float-card.two {
            bottom: 8%;
            right: -6%;
        }

        @media (max-width: 480px) {
            .hero-art .float-card {
                display: none;
            }
        }

        /* Section shell */
        section {
            padding-block: 80px;
        }

        .section-head {
            max-width: 640px;
            margin: 0 auto 48px;
            text-align: center;
        }

        .section-head .eyebrow {
            margin-bottom: 16px;
        }

        .section-head h2 {
            font-size: clamp(1.7rem, 3vw, 2.3rem);
            font-weight: 800;
            color: var(--color-primary);
            margin: 0 0 12px;
        }

        .section-head p {
            color: var(--color-text-muted);
            margin: 0;
            font-size: 1.05rem;
        }

        /* Modules */
        .modules-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
        }

        @media (min-width: 720px) {
            .modules-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        .module-card {
            background: var(--color-bg);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            padding: 32px 28px;
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
        }

        .module-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow);
            border-color: transparent;
        }

        .module-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .module-icon svg {
            width: 26px;
            height: 26px;
        }

        .module-icon.blue {
            background: var(--color-accent-soft);
            color: var(--color-accent);
        }

        .module-icon.green {
            background: var(--color-success-soft);
            color: var(--color-success);
        }

        .module-icon.amber {
            background: #fdf1de;
            color: #b9791a;
        }

        .module-card h3 {
            margin: 0 0 10px;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--color-primary);
        }

        .module-card p {
            margin: 0 0 16px;
            color: var(--color-text-muted);
            font-size: 0.96rem;
        }

        .module-card ul {
            margin: 0;
            padding: 0;
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .module-card li {
            display: flex;
            align-items: baseline;
            gap: 8px;
            font-size: 0.9rem;
            color: var(--color-text);
        }

        .module-card li::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: var(--color-accent);
            flex-shrink: 0;
            transform: translateY(-2px);
        }

        /* How it works */
        .steps {
            background: var(--color-surface);
        }

        .steps-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 32px;
            counter-reset: step;
        }

        @media (min-width: 720px) {
            .steps-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        .step {
            counter-increment: step;
            position: relative;
            padding-top: 8px;
        }

        .step::before {
            content: counter(step);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 999px;
            background: var(--color-primary);
            color: #fff;
            font-weight: 800;
            font-size: 1rem;
            margin-bottom: 16px;
        }

        .step h4 {
            margin: 0 0 8px;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--color-primary);
        }

        .step p {
            margin: 0;
            color: var(--color-text-muted);
            font-size: 0.92rem;
        }

        /* Features */
        .features-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }

        @media (min-width: 640px) {
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 960px) {
            .features-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        .feature {
            border: 1px solid var(--color-border);
            border-radius: var(--radius);
            padding: 24px 20px;
        }

        .feature .icon {
            margin-bottom: 12px;
            color: var(--color-accent);
        }

        .feature .icon svg {
            width: 22px;
            height: 22px;
        }

        .feature h4 {
            margin: 0 0 6px;
            font-size: 1rem;
            font-weight: 700;
            color: var(--color-primary);
        }

        .feature p {
            margin: 0;
            font-size: 0.88rem;
            color: var(--color-text-muted);
        }

        /* CTA band */
        .cta-band {
            background: linear-gradient(135deg, var(--color-primary), #16224a);
            border-radius: var(--radius-lg);
            padding: 56px 40px;
            text-align: center;
            color: #fff;
        }

        .cta-band h2 {
            margin: 0 0 12px;
            font-size: clamp(1.6rem, 3vw, 2.1rem);
            font-weight: 800;
        }

        .cta-band p {
            margin: 0 0 28px;
            color: rgba(255, 255, 255, 0.72);
            max-width: 480px;
            margin-inline: auto;
        }

        .cta-band .hero-actions {
            justify-content: center;
            margin-bottom: 0;
        }

        .cta-band .hero-actions + .hero-actions {
            margin-top: 20px;
        }

        .cta-band .btn-ghost {
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
        }

        .cta-band .btn-ghost:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Footer */
        .site-footer {
            border-top: 1px solid var(--color-border);
            padding-block: 40px;
        }

        .site-footer .container {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .footer-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            color: var(--color-primary);
        }

        .footer-brand img {
            height: 26px;
            width: auto;
        }

        .footer-links {
            display: flex;
            flex-wrap: wrap;
            gap: 24px;
            font-size: 0.9rem;
            color: var(--color-text-muted);
            font-weight: 600;
        }

        .footer-links a:hover {
            color: var(--color-accent);
        }

        .footer-copy {
            width: 100%;
            padding-top: 20px;
            margin-top: 20px;
            border-top: 1px solid var(--color-border);
            font-size: 0.82rem;
            color: var(--color-text-muted);
            text-align: center;
        }
    </style>
</head>
<body>

    <header class="site-header">
        <div class="container">
            <a href="/" class="brand">
                <img src="{{ asset('images/logo-full.png') }}" alt="TELU BAOBAB">
            </a>
            <nav class="nav-links">
                <a href="#modules">Modules</a>
                <a href="#fonctionnalites">Fonctionnalités</a>
                <a href="#comment-ca-marche">Comment ça marche</a>
            </nav>
            <a href="#telecharger" class="btn btn-primary header-cta">Télécharger l'app</a>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="container">
                <div>
                    <span class="eyebrow">Faite pour le Togo</span>
                    <h1>Une seule app pour <span>acheter</span>, <span>habiter</span> et <span>travailler</span></h1>
                    <p class="lead">
                        TELU BAOBAB réunit le commerce &amp; la livraison, l'immobilier et l'emploi journalier
                        dans une seule super-app, avec géolocalisation en temps réel et paiement Flooz &amp; TMoney.
                    </p>
                    <div class="hero-actions">
                        <a href="#" class="store-badge" aria-label="Disponible sur Google Play">
                            <svg viewBox="0 0 512 512" fill="currentColor"><path d="M325.3 234.3L104.6 13l280.8 161.2-60.1 60.1zM47 0C34 6.8 25.3 19.2 25.3 35.3v441.3c0 16.1 8.7 28.5 21.7 35.3l256.6-256L47 0zm425.2 225.6l-58.9-34.1-65.7 64.5 65.7 64.5 60.1-34.1c18-14.3 18-46.5-1.2-60.8zM104.6 499l280.8-161.2-60.1-60.1L104.6 499z"/></svg>
                            <span class="store-badge-text">
                                <small>Disponible sur</small>
                                <strong>Google Play</strong>
                            </span>
                        </a>
                        <a href="#" class="store-badge" aria-label="Télécharger sur l'App Store">
                            <svg viewBox="0 0 512 512" fill="currentColor"><path d="M318.7 268.7c-.2-36.7 16.4-64.4 50-84.8-18.8-26.9-47.2-41.7-84.7-44.6-35.5-2.8-74.3 20.7-88.5 20.7-15 0-49.4-19.7-76.4-19.7C63.3 141.2 4 184.8 4 273.5q0 39.3 14.4 81.2c12.8 36.7 59 126.7 107.2 125.2 25.2-.6 43-17.9 75.8-17.9 31.8 0 48.3 17.9 76.4 17.9 48.6-.7 90.4-82.5 102.6-119.3-65.2-30.7-61.7-90-61.7-91.9zm-56.6-164.2c27.3-32.4 24.8-61.9 24-72.5-24.1 1.4-52 16.4-67.9 34.9-17.5 19.8-27.8 44.3-25.6 71.9 26.1 2 49.9-11.4 69.5-34.3z"/></svg>
                            <span class="store-badge-text">
                                <small>Télécharger sur</small>
                                <strong>App Store</strong>
                            </span>
                        </a>
                    </div>
                    <div class="hero-actions">
                        <a href="#modules" class="btn btn-ghost">Découvrir les modules</a>
                    </div>
                    <div class="hero-stats">
                        <div>
                            <strong>3</strong>
                            <span>Marketplaces réunies</span>
                        </div>
                        <div>
                            <strong>100%</strong>
                            <span>Mobile money local</span>
                        </div>
                        <div>
                            <strong>24/7</strong>
                            <span>Suivi en temps réel</span>
                        </div>
                    </div>
                </div>
                <div class="hero-art">
                    <div class="mark-badge">
                        <img src="{{ asset('images/logo-full.png') }}" alt="TELU BAOBAB">
                    </div>
                    <div class="float-card one">
                        <span class="dot"></span>
                        Livraison en cours
                    </div>
                    <div class="float-card two">
                        <span class="dot"></span>
                        Paiement confirmé
                    </div>
                </div>
            </div>
        </section>

        <section id="modules">
            <div class="container">
                <div class="section-head">
                    <span class="eyebrow">Nos univers</span>
                    <h2>Trois marketplaces, une seule app</h2>
                    <p>Chaque module est pensé pour son métier, connecté à un profil unique et à un portefeuille commun.</p>
                </div>
                <div class="modules-grid">
                    <div class="module-card">
                        <div class="module-icon blue">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8h12l-1 12H7L6 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>
                        </div>
                        <h3>Commerce &amp; Livraison</h3>
                        <p>Commandez auprès des vendeurs proches de vous et suivez la livraison en direct.</p>
                        <ul>
                            <li>Catalogue vendeurs de proximité</li>
                            <li>Frais de livraison calculés à la distance</li>
                            <li>Suivi de commande en temps réel</li>
                        </ul>
                    </div>
                    <div class="module-card">
                        <div class="module-icon green">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 11.5 12 4l8 7.5"/><path d="M6 10v9a1 1 0 0 0 1 1h4v-6h2v6h4a1 1 0 0 0 1-1v-9"/></svg>
                        </div>
                        <h3>Immobilier</h3>
                        <p>Trouvez ou publiez un logement, réservez à la nuit ou au mois en toute simplicité.</p>
                        <ul>
                            <li>Annonces vérifiées avec photos</li>
                            <li>Réservation avec dates protégées</li>
                            <li>Mise en avant pour les propriétaires abonnés</li>
                        </ul>
                    </div>
                    <div class="module-card">
                        <div class="module-icon amber">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M3 13h18"/></svg>
                        </div>
                        <h3>Emploi journalier</h3>
                        <p>Publiez une offre ou postulez à des missions près de chez vous en quelques clics.</p>
                        <ul>
                            <li>Offres et profils géolocalisés</li>
                            <li>Candidature et suivi en un tap</li>
                            <li>Messagerie directe recruteur / candidat</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <section id="comment-ca-marche" class="steps">
            <div class="container">
                <div class="section-head">
                    <span class="eyebrow">Simple et rapide</span>
                    <h2>Comment ça marche</h2>
                    <p>Un seul compte, quatre étapes, pour accéder à tous les services TELU BAOBAB.</p>
                </div>
                <div class="steps-grid">
                    <div class="step">
                        <h4>Créez votre compte</h4>
                        <p>Inscription par téléphone avec vérification par SMS.</p>
                    </div>
                    <div class="step">
                        <h4>Choisissez votre profil</h4>
                        <p>Client, vendeur, livreur, propriétaire, recruteur ou candidat.</p>
                    </div>
                    <div class="step">
                        <h4>Explorez près de vous</h4>
                        <p>Produits, logements et offres géolocalisés autour de vous.</p>
                    </div>
                    <div class="step">
                        <h4>Payez et suivez</h4>
                        <p>Paiement Flooz ou TMoney et suivi en temps réel dans l'app.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="fonctionnalites">
            <div class="container">
                <div class="section-head">
                    <span class="eyebrow">Sous le capot</span>
                    <h2>Pensée pour le terrain togolais</h2>
                    <p>Des fonctionnalités transverses qui servent les trois modules.</p>
                </div>
                <div class="features-grid">
                    <div class="feature">
                        <div class="icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-6.1-7-11a7 7 0 0 1 14 0c0 4.9-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                        </div>
                        <h4>Géolocalisation</h4>
                        <p>Trouvez vendeurs, logements et missions les plus proches, en temps réel.</p>
                    </div>
                    <div class="feature">
                        <div class="icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="2" width="12" height="20" rx="2"/><path d="M11 18h2"/></svg>
                        </div>
                        <h4>Paiement mobile money</h4>
                        <p>Flooz et TMoney (Mixx by Yas) intégrés nativement pour chaque transaction.</p>
                    </div>
                    <div class="feature">
                        <div class="icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1.2-4.8A8 8 0 1 1 21 12Z"/></svg>
                        </div>
                        <h4>Messagerie intégrée</h4>
                        <p>Échangez directement avec vendeurs, livreurs, propriétaires ou recruteurs.</p>
                    </div>
                    <div class="feature">
                        <div class="icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3 2.6 5.6 6.1.8-4.5 4.2 1.2 6-5.4-3-5.4 3 1.2-6-4.5-4.2 6.1-.8L12 3Z"/></svg>
                        </div>
                        <h4>Avis &amp; notation</h4>
                        <p>Une réputation construite sur des évaluations vérifiées après chaque service.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="telecharger">
            <div class="container">
                <div class="cta-band">
                    <h2>Rejoignez TELU BAOBAB dès aujourd'hui</h2>
                    <p>L'application arrive bientôt sur l'App Store et Google Play. Une question, un partenariat ? Contactez-nous.</p>
                    <div class="hero-actions">
                        <a href="#" class="store-badge on-dark" aria-label="Disponible sur Google Play">
                            <svg viewBox="0 0 512 512" fill="currentColor"><path d="M325.3 234.3L104.6 13l280.8 161.2-60.1 60.1zM47 0C34 6.8 25.3 19.2 25.3 35.3v441.3c0 16.1 8.7 28.5 21.7 35.3l256.6-256L47 0zm425.2 225.6l-58.9-34.1-65.7 64.5 65.7 64.5 60.1-34.1c18-14.3 18-46.5-1.2-60.8zM104.6 499l280.8-161.2-60.1-60.1L104.6 499z"/></svg>
                            <span class="store-badge-text">
                                <small>Disponible sur</small>
                                <strong>Google Play</strong>
                            </span>
                        </a>
                        <a href="#" class="store-badge on-dark" aria-label="Télécharger sur l'App Store">
                            <svg viewBox="0 0 512 512" fill="currentColor"><path d="M318.7 268.7c-.2-36.7 16.4-64.4 50-84.8-18.8-26.9-47.2-41.7-84.7-44.6-35.5-2.8-74.3 20.7-88.5 20.7-15 0-49.4-19.7-76.4-19.7C63.3 141.2 4 184.8 4 273.5q0 39.3 14.4 81.2c12.8 36.7 59 126.7 107.2 125.2 25.2-.6 43-17.9 75.8-17.9 31.8 0 48.3 17.9 76.4 17.9 48.6-.7 90.4-82.5 102.6-119.3-65.2-30.7-61.7-90-61.7-91.9zm-56.6-164.2c27.3-32.4 24.8-61.9 24-72.5-24.1 1.4-52 16.4-67.9 34.9-17.5 19.8-27.8 44.3-25.6 71.9 26.1 2 49.9-11.4 69.5-34.3z"/></svg>
                            <span class="store-badge-text">
                                <small>Télécharger sur</small>
                                <strong>App Store</strong>
                            </span>
                        </a>
                    </div>
                    <div class="hero-actions">
                        <a href="mailto:support@telubaobab.com" class="btn btn-ghost">Nous contacter</a>
                        <a href="/privacy-policy" class="btn btn-ghost">Politique de confidentialité</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container">
            <div class="footer-brand">
                <img src="{{ asset('images/logo-full.png') }}" alt="TELU BAOBAB">
            </div>
            <div class="footer-links">
                <a href="#modules">Modules</a>
                <a href="#fonctionnalites">Fonctionnalités</a>
                <a href="/privacy-policy">Confidentialité</a>
                <a href="mailto:support@telubaobab.com">Contact</a>
            </div>
            <div class="footer-copy">
                &copy; {{ date('Y') }} TELU BAOBAB — Tous droits réservés. · telu3.com
            </div>
        </div>
    </footer>

</body>
</html>
