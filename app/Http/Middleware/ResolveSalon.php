<?php

namespace App\Http\Middleware;

use App\Models\Salon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the published salon named by the {salonSlug} route segment and the
 * guest's language, so controllers never look either up themselves.
 *
 * The language is ?lang= on pages and "locale" in JSON bodies; an unsupported
 * value falls back to the salon's default language.
 */
class ResolveSalon
{
    private const SALON = 'salon';

    private const LOCALE = 'locale';

    public function handle(Request $request, Closure $next): Response
    {
        $salon = Salon::query()
            ->published()
            ->where('slug', $request->route('salonSlug'))
            ->firstOrFail();

        $requested = $request->query('lang', $request->input('locale'));

        $locale = in_array($requested, config('salon.locales'), true) ? $requested : $salon->default_locale;

        $request->attributes->set(self::SALON, $salon);
        $request->attributes->set(self::LOCALE, $locale);

        app()->setLocale($locale);

        return $next($request);
    }

    public static function salon(Request $request): Salon
    {
        return $request->attributes->get(self::SALON);
    }

    public static function locale(Request $request): string
    {
        return $request->attributes->get(self::LOCALE);
    }
}
