<footer class="border-t border-gray-200 bg-white py-10" role="contentinfo">
    <div class="max-w-7xl mx-auto px-6">
        <div class="flex flex-col md:flex-row items-center justify-between gap-6 md:gap-8">
            <div class="footer-brand flex items-center gap-3 font-bold text-gray-900" aria-label="TELU BAOBAB">
                <img src="{{ asset('images/logo-full.png') }}" alt="" class="h-7 w-auto">
            </div>

            <nav class="footer-links flex flex-wrap items-center justify-center gap-6 md:gap-8 text-sm font-semibold text-gray-500" aria-label="Liens du pied de page">
                <a href="#modules" class="hover:text-primary-600 transition-colors">Modules</a>
                <a href="#fonctionnalites" class="hover:text-primary-600 transition-colors">Fonctionnalités</a>
                <a href="/privacy-policy" class="hover:text-primary-600 transition-colors">Confidentialité</a>
                <a href="mailto:support@telubaobab.com" class="hover:text-primary-600 transition-colors">Contact</a>
            </nav>
        </div>

        <div class="footer-copy mt-8 pt-8 border-t border-gray-200 text-center">
            <p class="text-sm text-gray-400">
                &copy; {{ date('Y') }} TELU BAOBAB — Tous droits réservés. · telu3.com
            </p>
        </div>
    </div>
</footer>