<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Salon experience
    |--------------------------------------------------------------------------
    |
    | Guest-facing settings shared by every salon. Per-salon values (name,
    | currency, opening hours, default locale) live in the salons table.
    | Provider connection values stay in config/services.php under
    | "local_llm"; AI budgets are in config/assistant.php.
    |
    */

    'locales' => ['id', 'en', 'ja'],

    'default_locale' => 'id',

    /*
    |--------------------------------------------------------------------------
    | Stage artwork
    |--------------------------------------------------------------------------
    |
    | The small round picture of the stylist used beside chat and narration,
    | and the photography per scene: a salon backdrop plus a stylist portrait
    | layered in front, so she can stand clear of the panels. The original
    | SVG artwork remains in public/images alongside the photos.
    |
    */

    'avatar_image' => 'images/beautician/avatar.webp',

    'scenes' => [
        'home' => ['image' => 'images/scenes/salon-lobby.webp', 'character' => 'images/character/stylist-greeting.webp', 'focus' => 'center 40%', 'focusMobile' => '50% 30%', 'anchor' => 'left', 'text' => 'top'],
        'services' => ['image' => 'images/scenes/services.webp', 'character' => 'images/character/stylist-greeting.webp', 'focus' => 'center 40%', 'focusMobile' => '50% 30%', 'anchor' => 'left', 'text' => 'top'],
        'stylists' => ['image' => 'images/scenes/stylists.webp', 'character' => 'images/character/stylist-greeting.webp', 'focus' => 'center 45%', 'focusMobile' => '60% 30%', 'anchor' => 'left', 'text' => 'top'],
        'facilities' => ['image' => 'images/scenes/facilities.webp', 'character' => 'images/character/stylist-greeting.webp', 'focus' => 'center 45%', 'focusMobile' => '30% 30%', 'anchor' => 'left', 'text' => 'bottom'],
        'info' => ['image' => 'images/scenes/info.webp', 'character' => 'images/character/stylist-greeting.webp', 'focus' => 'center 45%', 'focusMobile' => '70% 30%', 'anchor' => 'left', 'text' => 'bottom'],
        'staff' => ['image' => 'images/scenes/salon-lobby.webp', 'character' => 'images/character/stylist-greeting.webp', 'focus' => 'center 40%', 'focusMobile' => '60% 30%', 'anchor' => 'left', 'text' => 'top'],
        'appointment' => ['image' => 'images/scenes/appointment.webp', 'character' => 'images/character/stylist-greeting.webp', 'focus' => 'center 45%', 'focusMobile' => '30% 30%', 'anchor' => 'left', 'text' => 'bottom'],
    ],

    'scene_artwork' => [
        'service' => 'services',
        'stylist' => 'stylists',
        'facility' => 'facilities',
    ],

];
