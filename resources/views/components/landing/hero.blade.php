<section class="relative overflow-hidden py-20 md:py-28 lg:py-32 bg-gradient-to-br from-primary-50 via-white to-transparent" aria-labelledby="hero-title">
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_85%_-10%,var(--tw-gradient-from)_0%,transparent_55%)] from-primary-50 to-transparent" aria-hidden="true"></div>

    <div class="max-w-7xl mx-auto px-6 relative">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
            <div>
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-primary-50 text-primary-700 font-bold text-xs md:text-sm tracking-wider uppercase mb-6">
                    Faite pour le Togo
                </span>

                <h1 id="hero-title" class="text-4xl md:text-5xl lg:text-6xl font-extrabold leading-tight text-gray-900 mb-6">
                    Une seule app pour
                    <span class="text-primary-600"> acheter</span>,
                    <span class="text-primary-600"> habiter</span>
                    et <span class="text-primary-600"> travailler</span>
                </h1>

                <p class="text-lg md:text-xl text-gray-500 max-w-xl mb-8 leading-relaxed">
                    TELU BAOBAB réunit le commerce & la livraison, l'immobilier et l'emploi journalier
                    dans une seule super-app, avec géolocalisation en temps réel et paiement Flooz & TMoney.
                </p>

                <div class="flex flex-wrap gap-4 mb-10" role="group" aria-label="Liens de téléchargement">
                    <a href="{{ $playStoreUrl }}" target="_blank" rel="noopener noreferrer"
                        class="store-badge inline-flex items-center gap-3 px-5 py-3 rounded-xl bg-gray-900 text-white
                        hover:bg-gray-800 hover:-translate-y-0.5 hover:shadow-lg transition-all duration-150"
                        aria-label="Disponible sur Google Play">
                        <svg class="w-6 h-6 flex-shrink-0" viewBox="0 0 512 512" fill="currentColor" aria-hidden="true">
                            <path d="M325.3 234.3L104.6 13l280.8 161.2-60.1 60.1zM47 0C34 6.8 25.3 19.2 25.3 35.3v441.3c0 16.1 8.7 28.5 21.7 35.3l256.6-256L47 0zm425.2 225.6l-58.9-34.1-65.7 64.5 65.7 64.5 60.1-34.1c18-14.3 18-46.5-1.2-60.8zM104.6 499l280.8-161.2-60.1-60.1L104.6 499z"/>
                        </svg>
                        <span class="flex flex-col leading-tight">
                            <small class="text-xs font-medium text-gray-300">Disponible sur</small>
                            <strong class="text-base font-bold">Google Play</strong>
                        </span>
                    </a>

                    @if ($appStoreUrl)
                    <a href="{{ $appStoreUrl }}" target="_blank" rel="noopener noreferrer"
                        class="store-badge inline-flex items-center gap-3 px-5 py-3 rounded-xl bg-gray-900 text-white
                        hover:bg-gray-800 hover:-translate-y-0.5 hover:shadow-lg transition-all duration-150"
                        aria-label="Télécharger sur l'App Store">
                        <svg class="w-6 h-6 flex-shrink-0" viewBox="0 0 512 512" fill="currentColor" aria-hidden="true">
                            <path d="M318.7 268.7c-.2-36.7 16.4-64.4 50-84.8-18.8-26.9-47.2-41.7-84.7-44.6-35.5-2.8-74.3 20.7-88.5 20.7-15 0-49.4-19.7-76.4-19.7C63.3 141.2 4 184.8 4 273.5q0 39.3 14.4 81.2c12.8 36.7 59 126.7 107.2 125.2 25.2-.6 43-17.9 75.8-17.9 31.8 0 48.3 17.9 76.4 17.9 48.6-.7 90.4-82.5 102.6-119.3-65.2-30.7-61.7-90-61.7-91.9zm-56.6-164.2c27.3-32.4 24.8-61.9 24-72.5-24.1 1.4-52 16.4-67.9 34.9-17.5 19.8-27.8 44.3-25.6 71.9 26.1 2 49.9-11.4 69.5-34.3z"/>
                        </svg>
                        <span class="flex flex-col leading-tight">
                            <small class="text-xs font-medium text-gray-300">Télécharger sur</small>
                            <strong class="text-base font-bold">App Store</strong>
                        </span>
                    </a>
                    @else
                    <span class="store-badge inline-flex items-center gap-3 px-5 py-3 rounded-xl bg-gray-900/50 text-white/60 cursor-default"
                        aria-label="Bientôt sur l'App Store">
                        <svg class="w-6 h-6 flex-shrink-0" viewBox="0 0 512 512" fill="currentColor" aria-hidden="true">
                            <path d="M318.7 268.7c-.2-36.7 16.4-64.4 50-84.8-18.8-26.9-47.2-41.7-84.7-44.6-35.5-2.8-74.3 20.7-88.5 20.7-15 0-49.4-19.7-76.4-19.7C63.3 141.2 4 184.8 4 273.5q0 39.3 14.4 81.2c12.8 36.7 59 126.7 107.2 125.2 25.2-.6 43-17.9 75.8-17.9 31.8 0 48.3 17.9 76.4 17.9 48.6-.7 90.4-82.5 102.6-119.3-65.2-30.7-61.7-90-61.7-91.9zm-56.6-164.2c27.3-32.4 24.8-61.9 24-72.5-24.1 1.4-52 16.4-67.9 34.9-17.5 19.8-27.8 44.3-25.6 71.9 26.1 2 49.9-11.4 69.5-34.3z"/>
                        </svg>
                        <span class="flex flex-col leading-tight">
                            <small class="text-xs font-medium text-gray-400">Bientôt sur</small>
                            <strong class="text-base font-bold">App Store</strong>
                        </span>
                    </span>
                    @endif
                </div>

                <div class="flex flex-wrap gap-4 mb-12">
                    <a href="#modules" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-full bg-primary-50 text-primary-700 font-bold text-sm
                        hover:bg-primary-100 transition-colors">
                        Découvrir les modules
                    </a>
                </div>

                <div class="flex flex-wrap gap-10 md:gap-16" role="list" aria-label="Statistiques clés">
                    <div class="flex flex-col" role="listitem">
                        <strong class="text-3xl md:text-4xl font-extrabold text-gray-900">3</strong>
                        <span class="text-sm text-gray-500 mt-1">Marketplaces réunies</span>
                    </div>
                    <div class="flex flex-col" role="listitem">
                        <strong class="text-3xl md:text-4xl font-extrabold text-gray-900">100%</strong>
                        <span class="text-sm text-gray-500 mt-1">Mobile money local</span>
                    </div>
                    <div class="flex flex-col" role="listitem">
                        <strong class="text-3xl md:text-4xl font-extrabold text-gray-900">24/7</strong>
                        <span class="text-sm text-gray-500 mt-1">Suivi en temps réel</span>
                    </div>
                </div>
            </div>

            <div class="relative hidden lg:block" aria-hidden="true">
                <div class="relative max-w-md mx-auto">
                    <div class="rounded-2xl bg-gradient-to-br from-gray-900 to-gray-800 p-8 md:p-12 shadow-2xl flex items-center justify-center min-h-[320px]">
                        <img src="{{ asset('images/logo-full.png') }}" alt="" class="w-full h-auto brightness-0 invert">
                    </div>

                    <div class="absolute -top-4 -left-6 md:-left-8 bg-white rounded-xl shadow-xl p-4 flex items-center gap-3 animate-float-slow">
                        <span class="w-2.5 h-2.5 rounded-full bg-green-500 flex-shrink-0" aria-hidden="true"></span>
                        <span class="font-bold text-sm text-gray-900 whitespace-nowrap">Livraison en cours</span>
                    </div>

                    <div class="absolute -bottom-4 -right-6 md:-right-8 bg-white rounded-xl shadow-xl p-4 flex items-center gap-3 animate-float-slow animation-delay-1000">
                        <span class="w-2.5 h-2.5 rounded-full bg-green-500 flex-shrink-0" aria-hidden="true"></span>
                        <span class="font-bold text-sm text-gray-900 whitespace-nowrap">Paiement confirmé</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>