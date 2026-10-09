<?php

namespace Database\Seeders;

use App\Models\Salon;
use Illuminate\Database\Seeder;

class StylistImageSeeder extends Seeder
{
    public function run(): void
    {
        $salon = Salon::where('name', SalonSeeder::NAME)->firstOrFail();

        foreach ($this->imageDefinitions() as $stylistSlug => $images) {
            $stylist = $salon->stylists()->where('slug', $stylistSlug)->firstOrFail();
            $sort = 0;

            foreach ($images as $image) {
                $stylist->images()->updateOrCreate(
                    ['image_url' => $image['url']],
                    ['tags' => $image['tags'], 'alt_text' => $image['alt'], 'sort_order' => $sort++]
                );
            }
        }
    }

    /**
     * Portfolio pictures per stylist, keyed by the stylist's slug.
     *
     * @return array<string, list<array{url: string, tags: list<string>, alt: string}>>
     */
    private function imageDefinitions(): array
    {
        return [
            'rina-pratama' => [
                ['url' => 'images/stylists/rina-pratama-1.svg', 'tags' => ['balayage'], 'alt' => 'Balayage karya Rina'],
                ['url' => 'images/stylists/rina-pratama-2.svg', 'tags' => ['keratin'], 'alt' => 'Rambut halus setelah keratin'],
            ],
            'kenji-mori' => [
                ['url' => 'images/stylists/kenji-mori-1.svg', 'tags' => ['haircut'], 'alt' => 'Potongan presisi karya Kenji'],
                ['url' => 'images/stylists/kenji-mori-2.svg', 'tags' => ['bridal'], 'alt' => 'Tata rambut pengantin'],
            ],
            'sari-wulandari' => [
                ['url' => 'images/stylists/sari-wulandari-1.svg', 'tags' => ['gel'], 'alt' => 'Manikur gel karya Sari'],
                ['url' => 'images/stylists/sari-wulandari-2.svg', 'tags' => ['nail-art'], 'alt' => 'Nail art dengan kristal'],
            ],
            'maya-lestari' => [
                ['url' => 'images/stylists/maya-lestari-1.svg', 'tags' => ['facial'], 'alt' => 'Kulit sehat setelah facial'],
                ['url' => 'images/stylists/maya-lestari-2.svg', 'tags' => ['room'], 'alt' => 'Ruang facial yang tenang'],
            ],
            'dewi-anggraini' => [
                ['url' => 'images/stylists/dewi-anggraini-1.svg', 'tags' => ['bridal'], 'alt' => 'Makeup pengantin karya Dewi'],
                ['url' => 'images/stylists/dewi-anggraini-2.svg', 'tags' => ['glam'], 'alt' => 'Makeup glam untuk pesta'],
            ],
            'ayu-ratna' => [
                ['url' => 'images/stylists/ayu-ratna-1.svg', 'tags' => ['aroma'], 'alt' => 'Minyak esensial untuk pijat'],
                ['url' => 'images/stylists/ayu-ratna-2.svg', 'tags' => ['room'], 'alt' => 'Ruang pijat yang hangat'],
            ],
        ];
    }
}
