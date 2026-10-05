<section id="telecharger" class="py-20 md:py-28 lg:py-32 bg-gray-50" aria-labelledby="cta-title">
    <div class="max-w-7xl mx-auto px-6">
        <div class="max-w-4xl mx-auto text-center">
            <div class="rounded-3xl bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-10 md:p-16 relative overflow-hidden">
                <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-from)_0%,transparent_70%)] from-primary-500/20 to-transparent" aria-hidden="true"></div>

                <h2 id="cta-title" class="text-3xl md:text-4xl lg:text-5xl font-extrabold text-white mb-6 relative z-10">
                    Rejoignez TELU BAOBAB dès aujourd'hui
                </h2>

                <p class="text-lg md:text-xl text-gray-300 max-w-2xl mx-auto mb-10 relative z-10 leading-relaxed">
                    {{ $appStoreUrl ? "L'application est disponible sur Google Play et l'App Store." : "L'application est disponible sur Google Play, et arrive bientôt sur l'App Store." }}
                    Une question, un partenariat ? Contactez-nous.
                </p>

                <div class="flex flex-wrap items-center justify-center gap-4 mb-10 relative z-10" role="group" aria-label="Liens de téléchargement">
                    <a href="{{ $playStoreUrl }}" target="_blank" rel="noopener noreferrer"
                        class="store-badge inline-flex items-center gap-3 px-5 py-3 rounded-xl bg-white/10 text-white border border-white/20
                        hover:bg-white/20 hover:border-white/30 hover:-translate-y-0.5 hover:shadow-xl transition-all duration-150"
                        aria-label="Disponible sur Google Play">
                        <svg class="w-6 h-6 flex-shrink-0" viewBox="0 0 512 512" fill="currentColor" aria-hidden="true">
                            <path d="M325.3 234.3L104.6 13l280.8 161.2-60.1 60.1zM47 0C34 6.8 25.3 19.2 25.3 35.3v441.3c0 16.1 8.7 28.5 21.7 35.3l256.6-256L47 0zm425.2 225.6l-58.9-34.1-65.7 64.5 65.7 64.5 60.1-34.1c18-14.3 18-46.5-1.2-60.8zM104.6 499l280.8-161.2-60.1-60.1L104.6 499z"/>
                        </svg>
                        <span class="flex flex-col leading-tight">
                            <small class="text-xs font-medium text-gray-400">Disponible sur</small>
                            <strong class="text-base font-bold">Google Play</strong>
                        </span>
                    </a>

                    @if ($appStoreUrl)
                    <a href="{{ $appStoreUrl }}" target="_blank" rel="noopener noreferrer"
                        class="store-badge inline-flex items-center gap-3 px-5 py-3 rounded-xl bg-white/10 text-white border border-white/20
                        hover:bg-white/20 hover:border-white/30 hover:-translate-y-0.5 hover:shadow-xl transition-all duration-150"
                        aria-label="Télécharger sur l'App Store">
                        <svg class="w-6 h-6 flex-shrink-0" viewBox="0 0 512 512" fill="currentColor" aria-hidden="true">
                            <path d="M318.7 268.7c-.2-36.7 16.4-64.4 50-84.8-18.8-26.9-47.2-41.7-84.7-44.6-35.5-2.8-74.3 20.7-88.5 20.7-15 0-49.4-19.7-76.4-19.7C63.3 141.2 4 184.8 4 273.5q0 39.3 14.4 81.2c12.8 36.7 59 126.7 107.2 125.2 25.2-.6 43-17.9 75.8-17.9 31.8 0 48.3 17.9 76.4 17.9 48.6-.7 90.4-82.5 102.6-119.3-65.2-30.7-61.7-90-61.7-91.9zm-56.6-164.2c27.3-32.4 24.8-61.9 24-72.5-24.1 1.4-52 16.4-67.9 34.9-17.5 19.8-27.8 44.3-25.6 71.9 26.1 2 49.9-11.4 69.5-34.3z"/>
                        </svg>
                        <span class="flex flex-col leading-tight">
                            <small class="text-xs font-medium text-gray-400">Télécharger sur</small>
                            <strong class="text-base font-bold">App Store</strong>
                        </span>
                    </a>
                    @else
                    <span class="store-badge inline-flex items-center gap-3 px-5 py-3 rounded-xl bg-white/5 text-white/50 border border-white/10 cursor-default"
                        aria-label="Bientôt sur l'App Store">
                        <svg class="w-6 h-6 flex-shrink-0" viewBox="0 0 512 512" fill="currentColor" aria-hidden="true">
                            <path d="M318.7 268.7c-.2-36.7 16.4-64.4 50-84.8-18.8-26.9-47.2-41.7-84.7-44.6-35.5-2.8-74.3 20.7-88.5 20.7-15 0-49.4-19.7-76.4-19.7C63.3 141.2 4 184.8 4 273.5q0 39.3 14.4 81.2c12.8 36.7 59 126.7 107.2 125.2 25.2-.6 43-17.9 75.8-17.9 31.8 0 48.3 17.9 76.4 17.9 48.6-.7 90.4-82.5 102.6-119.3-65.2-30.7-61.7-90-61.7-91.9zm-56.6-164.2c27.3-32.4 24.8-61.9 24-72.5-24.1 1.4-52 16.4-67.9 34.9-17.5 19.8-27.8 44.3-25.6 71.9 26.1 2 49.9-11.4 69.5-34.3z"/>
                        </svg>
                        <span class="flex flex-col leading-tight">
                            <small class="text-xs font-medium text-gray-500">Bientôt sur</small>
                            <strong class="text-base font-bold">App Store</strong>
                        </span>
                    </span>
                    @endif
                </div>

                <div class="flex flex-wrap items-center justify-center gap-4 relative z-10">
                    <a href="mailto:support@telubaobab.com"
                        class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-full bg-white/10 text-white border border-white/20 font-semibold text-sm
                        hover:bg-white/20 hover:border-white/30 transition-all duration-150">
                        Nous contacter
                    </a>
                    <a href="/privacy-policy"
                        class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-full bg-white/10 text-white border border-white/20 font-semibold text-sm
                        hover:bg-white/20 hover:border-white/30 transition-all duration-150">
                        Politique de confidentialité
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>