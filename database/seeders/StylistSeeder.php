<?php

namespace Database\Seeders;

use App\Models\Salon;
use Illuminate\Database\Seeder;

class StylistSeeder extends Seeder
{
    public function run(): void
    {
        $salon = Salon::where('name', SalonSeeder::NAME)->firstOrFail();
        $sort = 0;

        foreach ($this->stylistDefinitions() as $definition) {
            $salon->stylists()->updateOrCreate(
                ['slug' => $definition['slug']],
                [...$definition, 'sort_order' => $sort++, 'is_active' => true]
            );
        }
    }

    /**
     * A stylist's specialty is a service category: it decides which services
     * they can be booked for.
     *
     * @return list<array<string, mixed>>
     */
    private function stylistDefinitions(): array
    {
        return [
            [
                'slug' => 'rina-pratama', 'name' => 'Rina Pratama', 'title' => 'Senior colorist', 'specialty' => 'hair', 'years_experience' => 9,
                'skills' => ['balayage', 'keratin', 'haircut'],
                'description' => 'Rina meracik warna rambut yang terlihat alami dan mudah dirawat. Ia suka memulai dengan ngobrol soal gaya hidup Anda sebelum memilih warna.',
                'translations' => [
                    'id' => ['title' => 'Senior colorist', 'description' => 'Rina meracik warna rambut yang terlihat alami dan mudah dirawat. Ia suka memulai dengan ngobrol soal gaya hidup Anda sebelum memilih warna.'],
                    'en' => ['title' => 'Senior colorist', 'description' => 'Rina blends hair colour that looks natural and is easy to maintain. She likes to start by chatting about your lifestyle before choosing a shade.'],
                    'ja' => ['title' => 'シニアカラリスト', 'description' => 'リナは自然で手入れしやすいヘアカラーが得意です。色を決める前に、まず普段の生活についてお話を伺います。'],
                ],
            ],
            [
                'slug' => 'kenji-mori', 'name' => 'Kenji Mori', 'title' => 'Hair stylist', 'specialty' => 'hair', 'years_experience' => 6,
                'skills' => ['haircut', 'bridal_updo'],
                'description' => 'Kenji pernah bekerja di Tokyo dan terkenal dengan potongan presisi serta tata rambut pengantin yang rapi. Ia juga berbicara bahasa Jepang.',
                'translations' => [
                    'id' => ['title' => 'Hair stylist', 'description' => 'Kenji pernah bekerja di Tokyo dan terkenal dengan potongan presisi serta tata rambut pengantin yang rapi. Ia juga berbicara bahasa Jepang.'],
                    'en' => ['title' => 'Hair stylist', 'description' => 'Kenji trained in Tokyo and is known for precision cuts and tidy bridal hair. He also speaks Japanese.'],
                    'ja' => ['title' => 'ヘアスタイリスト', 'description' => '東京で経験を積んだケンジは、精密なカットと丁寧なブライダルヘアが得意です。日本語でもご相談いただけます。'],
                ],
            ],
            [
                'slug' => 'sari-wulandari', 'name' => 'Sari Wulandari', 'title' => 'Nail artist', 'specialty' => 'nails', 'years_experience' => 5,
                'skills' => ['gel_nails', 'nail_art'],
                'description' => 'Sari mengubah foto referensi menjadi desain kuku yang rapi dan tahan lama, dari minimalis sampai penuh kristal.',
                'translations' => [
                    'id' => ['title' => 'Nail artist', 'description' => 'Sari mengubah foto referensi menjadi desain kuku yang rapi dan tahan lama, dari minimalis sampai penuh kristal.'],
                    'en' => ['title' => 'Nail artist', 'description' => 'Sari turns a reference photo into a neat, long-lasting nail design, from minimal to crystal-covered.'],
                    'ja' => ['title' => 'ネイリスト', 'description' => 'サリは参考写真を、きれいで長持ちするデザインに仕上げます。シンプルなものからクリスタルたっぷりのものまで。'],
                ],
            ],
            [
                'slug' => 'maya-lestari', 'name' => 'Maya Lestari', 'title' => 'Skin therapist', 'specialty' => 'skin', 'years_experience' => 8,
                'skills' => ['facial', 'acne_care'],
                'description' => 'Maya menganalisis kondisi kulit lebih dulu, lalu menyusun perawatan yang lembut namun efektif, terutama untuk kulit sensitif dan berjerawat.',
                'translations' => [
                    'id' => ['title' => 'Terapis kulit', 'description' => 'Maya menganalisis kondisi kulit lebih dulu, lalu menyusun perawatan yang lembut namun efektif, terutama untuk kulit sensitif dan berjerawat.'],
                    'en' => ['title' => 'Skin therapist', 'description' => 'Maya analyses your skin first, then plans a gentle but effective treatment, especially for sensitive and acne-prone skin.'],
                    'ja' => ['title' => 'スキンセラピスト', 'description' => 'マヤはまず肌の状態を分析し、やさしく効果的なケアを組み立てます。敏感肌やニキビ肌の方にも安心です。'],
                ],
            ],
            [
                'slug' => 'dewi-anggraini', 'name' => 'Dewi Anggraini', 'title' => 'Makeup artist', 'specialty' => 'makeup', 'years_experience' => 7,
                'skills' => ['bridal_makeup', 'glam_makeup'],
                'description' => 'Dewi merias ratusan pengantin dan selalu menyesuaikan riasan dengan busana, pencahayaan, dan karakter Anda.',
                'translations' => [
                    'id' => ['title' => 'Makeup artist', 'description' => 'Dewi merias ratusan pengantin dan selalu menyesuaikan riasan dengan busana, pencahayaan, dan karakter Anda.'],
                    'en' => ['title' => 'Makeup artist', 'description' => 'Dewi has made up hundreds of brides and always tunes the look to your outfit, the lighting and your character.'],
                    'ja' => ['title' => 'メイクアップアーティスト', 'description' => 'デウィは何百人もの花嫁を担当。衣装や照明、あなたらしさに合わせてメイクを調整します。'],
                ],
            ],
            [
                'slug' => 'ayu-ratna', 'name' => 'Ayu Ratna', 'title' => 'Spa therapist', 'specialty' => 'spa', 'years_experience' => 10,
                'skills' => ['aromatherapy', 'deep_tissue'],
                'description' => 'Ayu memadukan teknik pijat tradisional dan modern untuk melepas tegang di punggung, bahu, dan leher.',
                'translations' => [
                    'id' => ['title' => 'Terapis spa', 'description' => 'Ayu memadukan teknik pijat tradisional dan modern untuk melepas tegang di punggung, bahu, dan leher.'],
                    'en' => ['title' => 'Spa therapist', 'description' => 'Ayu blends traditional and modern massage techniques to release tension in the back, shoulders and neck.'],
                    'ja' => ['title' => 'スパセラピスト', 'description' => 'アユは伝統的な技法と現代的な技法を組み合わせ、背中、肩、首の緊張をほぐします。'],
                ],
            ],
        ];
    }
}
