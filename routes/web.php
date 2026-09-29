<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Liens des stores pilotés par les settings (modifiables depuis le back-office) ;
    // la page vitrine ne doit pas tomber si la base est indisponible.
    try {
        $playStoreUrl = Setting::get('app_store_url_android');
        $appStoreUrl = Setting::get('app_store_url_ios');
    } catch (Throwable) {
        $playStoreUrl = $appStoreUrl = null;
    }

    return view('welcome', [
        'playStoreUrl' => $playStoreUrl ?: 'https://play.google.com/store/apps/details?id=com.duokhorus.telu',
        'appStoreUrl' => $appStoreUrl ?: null,
    ]);
});

Route::view('/privacy-policy', 'privacy-policy')->name('privacy-policy');
