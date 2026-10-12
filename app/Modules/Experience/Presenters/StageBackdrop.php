<?php

namespace App\Modules\Experience\Presenters;

/**
 * Photography for a scene: a salon background, the beautician portrait and
 * where she stands, so the scene's text and panels stay clear of her.
 */
final class StageBackdrop
{
    /**
     * @return array{image: string, focus: string, focusMobile: string, character: string, anchor: string, text: string, avatarImage: string}
     */
    public static function for(string $scene): array
    {
        $scenes = config('salon.scenes');
        $artwork = $scenes[config('salon.scene_artwork')[$scene] ?? $scene] ?? $scenes['home'];

        return [
            'image' => asset($artwork['image']),
            'focus' => $artwork['focus'],
            'focusMobile' => $artwork['focusMobile'],
            'character' => self::versionedAssetUrl($artwork['character']),
            'anchor' => $artwork['anchor'],
            'text' => $artwork['text'],
            'avatarImage' => self::avatar(),
        ];
    }

    public static function avatar(): string
    {
        return self::versionedAssetUrl(config('salon.avatar_image'));
    }

    private static function versionedAssetUrl(string $path): string
    {
        $url = asset($path);
        $filePath = public_path($path);

        if (! is_file($filePath)) {
            return $url;
        }

        return $url.'?v='.substr(hash_file('sha256', $filePath), 0, 12);
    }
}
