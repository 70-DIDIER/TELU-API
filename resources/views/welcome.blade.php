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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-gray-900 bg-white">

    <x-landing.header />

    <main>
        <x-landing.hero :playStoreUrl="$playStoreUrl" :appStoreUrl="$appStoreUrl" />
        <x-landing.modules />
        <x-landing.steps />
        <x-landing.features />
        <x-landing.cta :playStoreUrl="$playStoreUrl" :appStoreUrl="$appStoreUrl" />
    </main>

    <x-landing.footer />

</body>
</html>