<?php

namespace App\Support;

/**
 * Curated Togolese reference data (names, places, products, jobs, businesses)
 * used by factories/seeders so demo data reads as real and local instead of
 * generic Faker English output. Not used at runtime by the API itself.
 */
class TogoCatalog
{
    /** @var array<int, string> */
    public const MALE_FIRST_NAMES = [
        'Kossi', 'Kokou', 'Komla', 'Kodjo', 'Yao', 'Ayaovi', 'Kwami', 'Edem',
        'Sena', 'Sitsofe', 'Fiawoo', 'Kofi', 'Selom', 'Mawuli', 'Mensah',
        'Amétépé', 'Delali', 'Elom', 'Fofo', 'Togbé', 'Ekoué', 'Kondi',
        'Tchaa', 'Essobozou', 'Bakoubè', 'Napo', 'Tchilabalo', 'Yendoukoa',
        'Piham', 'Alaza',
    ];

    /** @var array<int, string> */
    public const FEMALE_FIRST_NAMES = [
        'Akossiwa', 'Adjoa', 'Afi', 'Ama', 'Abra', 'Akouvi', 'Ablavi', 'Afiba',
        'Ayélé', 'Enam', 'Edoh', 'Dédé', 'Mawena', 'Sedami', 'Sika', 'Élikplim',
        'Amivi', 'Yawa', 'Kafui', 'Délali', 'Foli', 'Nyaley', 'Essowavana',
        'Pouwendbenda', 'Tchoulani', 'Bimpou', 'Nafissatou', 'Aïcha', 'Rachida',
        'Fatimata',
    ];

    /** @var array<int, string> */
    public const LAST_NAMES = [
        'Amégan', 'Adjavon', 'Agbodjan', 'Kpodo', 'Klutsé', 'Amouzou', 'Agbéko',
        'Dogbé', 'Dosseh', 'Sossou', 'Tossou', 'Adjékoun', 'Agbéli', 'Amétowoyona',
        'Amégnran', 'Baglo', 'Djidonou', 'Fiadjoe', 'Gnassingbé', 'Kpatcha',
        'Lawson', 'Olympio', 'Sedjro', 'Sogbossi', 'Tsegan', 'Yaovi', 'Zinsou',
        'Bakonde', 'Napo', 'Tchakala', 'Kolani', 'Yendoubouame', 'Bitho', 'Mazou',
        'Ouro-Djeri', 'Sambiani', 'Tchamdja', 'Kombate', 'Bassowa',
    ];

    /** @var array<int, string> */
    public const CITIES = [
        'Lomé', 'Kara', 'Sokodé', 'Kpalimé', 'Atakpamé', 'Dapaong', 'Tsévié',
        'Aného', 'Vogan', 'Bassar', 'Notsé', 'Mango',
    ];

    /** @var array<int, string> */
    public const LOME_QUARTIERS = [
        'Bè', 'Tokoin', 'Nyékonakpoè', 'Adidogomé', 'Agoè-Nyivé', 'Kodjoviakopé',
        'Hédzranawoé', 'Djidjolé', 'Agbalépédogan', 'Baguida', 'Akodesséwa',
        'Amoutivé', 'Doumassessé', 'Sagbado', 'Gbényédji', 'Adakpamé',
    ];

    /** @var array<int, string> */
    public const STREET_NAMES = [
        'Rue de la Paix', 'Rue du Commerce', 'Avenue de la Libération',
        'Rue des Bougainvilliers', 'Rue du Marché', 'Avenue Jean-Paul II',
        'Rue de l\'Indépendance', 'Rue des Manguiers', 'Avenue du Golfe',
        'Rue de l\'Ancien Pont',
    ];

    /** @var array<string, array<int, string>> */
    /**
     * Product name => a real, on-topic photo (Wikimedia Commons, resolved by
     * search term per product) instead of a random Lorem Picsum image.
     *
     * @var array<string, array<string, string>>
     */
    public const PRODUCTS_BY_CATEGORY = [
        'food' => [
            'Pâte et sauce arachide' => 'https://upload.wikimedia.org/wikipedia/commons/5/53/Fufu_in_groundnut_soup_with_fish.jpg',
            'Fufu igname' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/9c/Fufu_1.jpg/960px-Fufu_1.jpg',
            'Akpan (bouillie de maïs)' => 'https://upload.wikimedia.org/wikipedia/commons/a/ab/MAIZE_PORRIDGE_WITH_PEANUT_SPRINKLES.jpg',
            'Ablo vapeur' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c5/Fante_Kenkey_%282%29.jpg/960px-Fante_Kenkey_%282%29.jpg',
            'Riz sauce tomate' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/fa/Ghana_Jollof_Rice_with_Chicken.jpg/960px-Ghana_Jollof_Rice_with_Chicken.jpg',
            'Koliko (frites de patate douce)' => 'https://upload.wikimedia.org/wikipedia/commons/2/2c/Sweet_Potato_Fries_-_White_Castle%2C_New_Orleans.jpg',
            'Wagashi grillé' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c1/Waagashi.jpg/960px-Waagashi.jpg',
            'Poulet braisé' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3e/Boneless_Grilled_Chicken_and_Chips.jpg/960px-Boneless_Grilled_Chicken_and_Chips.jpg',
            'Poisson braisé et attiéké' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/ab/New_Year_meal_in_Africa.JPG/960px-New_Year_meal_in_Africa.JPG',
            'Haricot sauce gouagouassou' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d1/Ndamb%C3%A9_%28Senegalese_bean_sandwich%29.png/960px-Ndamb%C3%A9_%28Senegalese_bean_sandwich%29.png',
            'Gari foto' => 'https://upload.wikimedia.org/wikipedia/commons/0/0f/Gari_Fotor.jpg',
            'Akoumé sauce gombo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/34/Okra_soup%2C_melon_soup_and_catfish_stew.jpg/960px-Okra_soup%2C_melon_soup_and_catfish_stew.jpg',
            'Djenkoumé' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/Banku_07.jpg/960px-Banku_07.jpg',
            'Brochettes de bœuf' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/26/Anticuchos_-_Grilled_Beef_Heart_skewers.jpg/960px-Anticuchos_-_Grilled_Beef_Heart_skewers.jpg',
            'Beignets haricot (Ata)' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/53/Fried_Bean_cake.jpg/960px-Fried_Bean_cake.jpg',
        ],
        'drinks' => [
            'Bière Awooyo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/ef/Beer_at_Ramena_Beach.JPG/960px-Beer_at_Ramena_Beach.JPG',
            'Bière Flag' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/81/Bottle_of_Hite_poured_into_a_glass.jpg/960px-Bottle_of_Hite_poured_into_a_glass.jpg',
            'Coca-Cola 33cl' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e8/15-09-26-RalfR-WLC-0098_-_Coca-Cola_glass_bottle_%28Germany%29.jpg/960px-15-09-26-RalfR-WLC-0098_-_Coca-Cola_glass_bottle_%28Germany%29.jpg',
            'Fanta Orange' => 'https://upload.wikimedia.org/wikipedia/commons/1/1f/Almost_Empty_Fanta_Bottle.jpg',
            'Youki Soda' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a3/OK_Beverage_Orange_Soda_Bottle_-_DPLA_-_e1c275e65204b52253e2e0e7faa81a50_%28page_1%29.jpg/960px-OK_Beverage_Orange_Soda_Bottle_-_DPLA_-_e1c275e65204b52253e2e0e7faa81a50_%28page_1%29.jpg',
            'Sodabi artisanal' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e9/A_Palm_Win_Drinker.jpg/960px-A_Palm_Win_Drinker.jpg',
            'Tchoukoutou' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a7/Benin_-_Tchouk_photo_9.jpg/960px-Benin_-_Tchouk_photo_9.jpg',
            'Bissap glacé' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/07/Sobolo.jpg/960px-Sobolo.jpg',
            'Jus de gingembre' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/99/Ginger_%2C_zobo_and_tiger_nuts_drink.jpg/960px-Ginger_%2C_zobo_and_tiger_nuts_drink.jpg',
            'Eau minérale Possotomé' => 'https://upload.wikimedia.org/wikipedia/commons/4/47/Eau_potable.png',
            'Jus de bissap-gingembre' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/af/Petit_vendeur_de_jus.jpg/960px-Petit_vendeur_de_jus.jpg',
            'Café Togo Moka' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d0/Coffee_beans_spilling_out_of_a_cup.jpg/960px-Coffee_beans_spilling_out_of_a_cup.jpg',
        ],
        'grocery' => [
            'Riz local de Kovié 5kg' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/ce/Plain_white_rice-min-min.jpg/960px-Plain_white_rice-min-min.jpg',
            'Huile de palme rouge 1L' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Bottles_of_red_oil.jpg/960px-Bottles_of_red_oil.jpg',
            'Farine de maïs 2kg' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b6/Soorcornmeal1.jpg/960px-Soorcornmeal1.jpg',
            'Gari 1kg' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/7b/A_lady_roasting_grits_into_garri.png/960px-A_lady_roasting_grits_into_garri.png',
            'Piment frais' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3b/Liat_Portal_for_Foodie_Disorder_-_Serrano_Peppers_from_San_Francisco_Farmers_Market.jpg/960px-Liat_Portal_for_Foodie_Disorder_-_Serrano_Peppers_from_San_Francisco_Farmers_Market.jpg',
            'Tomate fraîche' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/7a/Fresh_Tomatoes_in_rolls_in_Barnawa_market_Kaduna_03.jpg/960px-Fresh_Tomatoes_in_rolls_in_Barnawa_market_Kaduna_03.jpg',
            'Oignon' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/8c/Market_onions_seller.jpg/960px-Market_onions_seller.jpg',
            'Igname de Sotouboua' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/17/Local_Tubers_Of_Yam_at_the_market.jpg/960px-Local_Tubers_Of_Yam_at_the_market.jpg',
            'Farine de manioc' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/64/A_cassava_flour_seller.jpg/960px-A_cassava_flour_seller.jpg',
            'Haricot niébé' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/0b/Patterned_cowpea_%2820240529%29.jpg/960px-Patterned_cowpea_%2820240529%29.jpg',
            'Arachides grillées' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/46/Groundnuts_farm.jpg/960px-Groundnuts_farm.jpg',
            'Sel de cuisine 1kg' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/07/Comparison_of_Table_Salt_with_Kitchen_Salt.png/960px-Comparison_of_Table_Salt_with_Kitchen_Salt.png',
        ],
        'electronics' => [
            'Smartphone Tecno Spark' => 'https://upload.wikimedia.org/wikipedia/commons/2/27/Back_of_Spark_10_Pro.png',
            'Chargeur solaire portable' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/bc/Eco-Camper_125W_Flexible_Solar_Panel.jpg/960px-Eco-Camper_125W_Flexible_Solar_Panel.jpg',
            'Ventilateur de bureau' => 'https://upload.wikimedia.org/wikipedia/commons/8/8d/Desk_fan_GE_%282%29.jpg',
            'Radio FM à piles' => 'https://upload.wikimedia.org/wikipedia/commons/9/99/Shower_radio.jpg',
            'Lampe torche rechargeable' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d7/3x_AA_to_D_battery_converter%2C_MY_DAY_vintage_flashlight%2C_Sofirn_SP36_flashlight.jpg/960px-3x_AA_to_D_battery_converter%2C_MY_DAY_vintage_flashlight%2C_Sofirn_SP36_flashlight.jpg',
            'Écouteurs Bluetooth' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/9d/Nothing_ear_%28stick%29_and_Nothing_Phone_%281%29.jpg/960px-Nothing_ear_%28stick%29_and_Nothing_Phone_%281%29.jpg',
            'Powerbank 10000mAh' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b5/A_power_bank_and_phone_charger_seller_hawking_in_Ketu%2C_Lagos%2C_Nigeria.jpg/960px-A_power_bank_and_phone_charger_seller_hawking_in_Ketu%2C_Lagos%2C_Nigeria.jpg',
            'Multiprise antisurtension' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/6e/APC_SurgeArrest_Surge_Protector_Power_Strip_%2848968398472%29.jpg/960px-APC_SurgeArrest_Surge_Protector_Power_Strip_%2848968398472%29.jpg',
            'Fer à repasser' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/86/Charcoal_iron_open.jpg/960px-Charcoal_iron_open.jpg',
        ],
        'fashion' => [
            'Pagne wax hollandais' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/20/Ankara_asv2021-10_img73_Republic_Museum.jpg/960px-Ankara_asv2021-10_img73_Republic_Museum.jpg',
            'Boubou brodé homme' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/eb/Boubou.tif/lossy-page1-960px-Boubou.tif.jpg',
            'Robe pagne sur mesure' => 'https://upload.wikimedia.org/wikipedia/commons/e/ec/Cowries_Print_Maxi_Dress.JPG',
            'Sandales en cuir de Kara' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/7e/Handcrafted_sandals_01.jpg/960px-Handcrafted_sandals_01.jpg',
            'Chemise en kente' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c9/Shirt_van_Afrikaanse_kente_stof-_Stichting_Nationaal_Museum_van_Wereldculturen_-_R-3633c.jpg/960px-Shirt_van_Afrikaanse_kente_stof-_Stichting_Nationaal_Museum_van_Wereldculturen_-_R-3633c.jpg',
            'Sac tissé en raphia' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c8/Hand_crafted_Raffia_bag.jpg/960px-Hand_crafted_Raffia_bag.jpg',
            'Ensemble bazin riche' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/5c/Vente_de_basin_pr%C3%AAt_a_porter.jpg/960px-Vente_de_basin_pr%C3%AAt_a_porter.jpg',
            'Casquette brodée' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e0/Cap%2C_sports_%28AM_2011.4.3-1%29.jpg/960px-Cap%2C_sports_%28AM_2011.4.3-1%29.jpg',
            'Foulard wax' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/5c/ASC_Leiden_-_Rietveld_Collection_-_Nigeria_1970_-_1973_-_01_-_017_A_girl_with_a_yellow_headscarf_and_dress_-_Toro.jpg/960px-ASC_Leiden_-_Rietveld_Collection_-_Nigeria_1970_-_1973_-_01_-_017_A_girl_with_a_yellow_headscarf_and_dress_-_Toro.jpg',
        ],
    ];

    /** @var array<int, string> */
    public const JOB_TITLES = [
        'Menuisier', 'Maçon', 'Électricien bâtiment', 'Plombier', 'Couturière',
        'Mécanicien auto', 'Coiffeuse', 'Chauffeur privé', 'Vendeuse en boutique',
        'Agent de sécurité', 'Cuisinier', 'Femme de ménage', 'Jardinier',
        'Peintre en bâtiment', 'Soudeur', 'Carreleur', 'Tailleur',
        'Blanchisseur', 'Réparateur de téléphones', 'Photographe événementiel',
        'Comptable', 'Agent commercial', 'Développeur informatique',
        'Enseignant à domicile', 'Aide-soignant', 'Livreur moto', 'Vigile',
    ];

    /** @var array<int, string> */
    public const COMPANY_NAMES = [
        'Ets Kodjo & Fils', 'SARL Togo Distribution', 'Groupe Lomé BTP',
        'Sotexho Togo', 'Nouvelle Vision Commerce', 'STMB Transport',
        'Agro Togo Plus', 'TogoTech Solutions', 'Baobab Services',
        'Adjavon & Associés', 'Golfe Import-Export', 'Kara Construction',
        'Maritima Logistique', 'Savana Agro-Industrie', 'Eburnie Togo SARL',
    ];

    /** @var array<int, string> */
    public const SHOP_NAMES = [
        'Chez Maman Afi', 'Boutique Ablavi', 'Superette du Coin - Bè',
        'Alimentation Générale Kodjoviakopé', 'Chez Tantie Akouvi',
        'Marché Frais Adidogomé', 'Épicerie Togo Plus', 'Fast Food Kpalimé',
        'Boulangerie Le Bon Pain', 'Quincaillerie Agbéko', 'Chez Papa Yao',
        'Bar-Restaurant Le Baobab', 'Supermarché Nyékonakpoè', 'Snack Hédzranawoé',
    ];

    public static function fullName(): string
    {
        $firstName = fake()->boolean()
            ? fake()->randomElement(self::MALE_FIRST_NAMES)
            : fake()->randomElement(self::FEMALE_FIRST_NAMES);

        return $firstName.' '.fake()->randomElement(self::LAST_NAMES);
    }

    public static function city(): string
    {
        return fake()->randomElement(self::CITIES);
    }

    public static function lomeQuartier(): string
    {
        return fake()->randomElement(self::LOME_QUARTIERS);
    }

    /**
     * A believable street address inside a Lomé neighbourhood.
     */
    public static function address(?string $quartier = null): string
    {
        return fake()->randomElement(self::STREET_NAMES)
            .', Quartier '.($quartier ?? self::lomeQuartier())
            .', Lomé';
    }

    public static function companyName(): string
    {
        return fake()->randomElement(self::COMPANY_NAMES);
    }

    public static function shopName(): string
    {
        return fake()->randomElement(self::SHOP_NAMES);
    }

    public static function jobTitle(): string
    {
        return fake()->randomElement(self::JOB_TITLES);
    }

    public static function productCategory(): string
    {
        return fake()->randomElement(array_keys(self::PRODUCTS_BY_CATEGORY));
    }

    /**
     * @return array{category: string, name: string, image_url: string}
     */
    public static function product(): array
    {
        $category = self::productCategory();
        $name = fake()->randomElement(array_keys(self::PRODUCTS_BY_CATEGORY[$category]));

        return [
            'category' => $category,
            'name' => $name,
            'image_url' => self::PRODUCTS_BY_CATEGORY[$category][$name],
        ];
    }
}
