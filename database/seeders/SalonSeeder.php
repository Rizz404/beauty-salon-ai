<?php

namespace Database\Seeders;

use App\Models\Salon;
use Illuminate\Database\Seeder;

class SalonSeeder extends Seeder
{
    /**
     * The demo salon's name, used by the other seeders to look it up.
     */
    public const NAME = 'Beauty Salon AI';

    public function run(): void
    {
        Salon::firstOrCreate(
            ['name' => self::NAME],
            [
                'slug' => Salon::generateUniqueSlug(self::NAME),
                'description' => 'Salon kecantikan butik di Senopati, Jakarta: perawatan rambut, kuku, kulit, makeup, dan spa dalam satu tempat yang tenang.',
                'translations' => [
                    'id' => ['description' => 'Salon kecantikan butik di Senopati, Jakarta: perawatan rambut, kuku, kulit, makeup, dan spa dalam satu tempat yang tenang.'],
                    'en' => ['description' => 'A boutique beauty salon in Senopati, Jakarta: hair, nails, skin, makeup and spa treatments together in one calm place.'],
                    'ja' => ['description' => 'ジャカルタ・スノパティにあるブティックサロン。ヘア、ネイル、スキンケア、メイク、スパを、ひとつの落ち着いた空間でお楽しみいただけます。'],
                ],
                'address' => 'Jl. Senopati Raya No. 21, Senopati',
                'city' => 'Jakarta',
                'country' => 'Indonesia',
                'latitude' => -6.2297,
                'longitude' => 106.8076,
                'phone' => '+62 21 7123 4567',
                'whatsapp' => '6281234567890',
                'email' => 'hello@beautysalonai.test',
                'instagram' => '@beautysalonai.demo',
                'timezone' => 'Asia/Jakarta',
                'currency' => 'IDR',
                'default_locale' => 'id',
                'opening_time' => '09:00',
                'closing_time' => '20:00',
                'public_status' => 'published',
            ]
        );
    }
}
