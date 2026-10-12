<?php

namespace Tests\Feature;

use App\Modules\Experience\Presenters\StageBackdrop;
use Tests\TestCase;

class StageBackdropTest extends TestCase
{
    public function test_artwork_versions_stay_stable_and_change_when_the_image_content_changes(): void
    {
        $originalPublicPath = public_path();
        $artworkDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'salon-stage-artwork-'.bin2hex(random_bytes(8));
        mkdir($artworkDirectory);
        file_put_contents($artworkDirectory.'/avatar.webp', 'first avatar');
        file_put_contents($artworkDirectory.'/character.webp', 'first character');
        $this->app->usePublicPath($artworkDirectory);
        config(['salon.avatar_image' => 'avatar.webp', 'salon.scenes.home.character' => 'character.webp']);

        try {
            $backdrop = StageBackdrop::for('home');

            $this->assertSame(asset('avatar.webp').'?v=9ea5a5aaf139', $backdrop['avatarImage']);
            $this->assertSame(asset('character.webp').'?v=2de663249e83', $backdrop['character']);
            $this->assertSame($backdrop, StageBackdrop::for('home'));

            file_put_contents($artworkDirectory.'/character.webp', 'updated character');
            $updatedBackdrop = StageBackdrop::for('home');

            $this->assertSame(asset('character.webp').'?v=fa6afee1ac79', $updatedBackdrop['character']);
            $this->assertSame($backdrop['avatarImage'], $updatedBackdrop['avatarImage']);
        } finally {
            $this->app->usePublicPath($originalPublicPath);
            unlink($artworkDirectory.'/avatar.webp');
            unlink($artworkDirectory.'/character.webp');
            rmdir($artworkDirectory);
        }
    }

    public function test_missing_artwork_keeps_its_original_url_without_file_read_errors(): void
    {
        config([
            'salon.avatar_image' => 'images/beautician/missing-avatar.webp',
            'salon.scenes.home.character' => 'images/character/missing-character.webp',
        ]);

        $backdrop = StageBackdrop::for('home');

        $this->assertSame(asset('images/beautician/missing-avatar.webp'), $backdrop['avatarImage']);
        $this->assertSame(asset('images/character/missing-character.webp'), $backdrop['character']);
    }
}
