<?php

namespace Database\Seeders;

use App\Models\Salon;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $salon = Salon::where('name', SalonSeeder::NAME)->firstOrFail();
        $sort = 0;

        foreach ($this->serviceDefinitions() as $definition) {
            $salon->services()->updateOrCreate(
                ['slug' => $definition['slug']],
                [...$definition, 'image_url' => 'images/services/'.$definition['slug'].'.webp', 'sort_order' => $sort++, 'is_active' => true]
            );
        }
    }

    /**
     * Durations are whole 30-minute blocks, the unit chair time is booked in.
     *
     * @return list<array<string, mixed>>
     */
    private function serviceDefinitions(): array
    {
        return [
            [
                'slug' => 'haircut-blow-dry', 'category' => 'hair', 'name' => 'Haircut & Blow Dry', 'price' => 180000, 'duration_minutes' => 60, 'deposit' => 0,
                'tags' => ['bestseller', 'halal'], 'cautions' => [], 'is_featured' => true,
                'description' => 'Konsultasi bentuk wajah, potongan presisi, keramas, dan blow dry rapi.',
                'translations' => [
                    'id' => ['name' => 'Potong Rambut & Blow Dry', 'description' => 'Konsultasi bentuk wajah, potongan presisi, keramas, dan blow dry rapi.'],
                    'en' => ['name' => 'Haircut & Blow Dry', 'description' => 'A face-shape consultation, a precision cut, a wash and a polished blow dry.'],
                    'ja' => ['name' => 'カット＆ブロー', 'description' => '顔型のカウンセリング、精密なカット、シャンプー、仕上げのブローまで。'],
                ],
            ],
            [
                'slug' => 'balayage-color', 'category' => 'hair', 'name' => 'Balayage Color', 'price' => 1450000, 'duration_minutes' => 180, 'deposit' => 300000,
                'tags' => ['signature'], 'cautions' => ['peroxide', 'fragrance'], 'is_featured' => true,
                'description' => 'Pewarnaan tangan dengan gradasi lembut, termasuk toner, treatment, dan blow dry.',
                'translations' => [
                    'id' => ['name' => 'Balayage Color', 'description' => 'Pewarnaan tangan dengan gradasi lembut, termasuk toner, treatment, dan blow dry.'],
                    'en' => ['name' => 'Balayage Color', 'description' => 'Hand-painted colour with a soft gradient, including toner, a treatment and a blow dry.'],
                    'ja' => ['name' => 'バレイヤージュカラー', 'description' => '手作業で描く柔らかなグラデーションカラー。トナー、トリートメント、ブロー込み。'],
                ],
            ],
            [
                'slug' => 'keratin-smoothing', 'category' => 'hair', 'name' => 'Keratin Smoothing', 'price' => 1100000, 'duration_minutes' => 150, 'deposit' => 200000,
                'tags' => ['ammonia_free'], 'cautions' => ['fragrance'], 'is_featured' => false,
                'description' => 'Melembutkan dan mengurangi frizz hingga 3 bulan dengan formula bebas amonia.',
                'translations' => [
                    'id' => ['name' => 'Keratin Smoothing', 'description' => 'Melembutkan dan mengurangi frizz hingga 3 bulan dengan formula bebas amonia.'],
                    'en' => ['name' => 'Keratin Smoothing', 'description' => 'Softens hair and tames frizz for up to 3 months with an ammonia-free formula.'],
                    'ja' => ['name' => 'ケラチンスムージング', 'description' => 'アンモニアフリーの処方で、最長3か月間うねりを抑えてやわらかな髪に。'],
                ],
            ],
            [
                'slug' => 'scalp-hair-spa', 'category' => 'hair', 'name' => 'Scalp & Hair Spa', 'price' => 320000, 'duration_minutes' => 60, 'deposit' => 0,
                'tags' => ['halal', 'vegan', 'sensitive_skin'], 'cautions' => ['essential_oils'], 'is_featured' => false,
                'description' => 'Pijat kulit kepala, masker nutrisi, dan uap hangat untuk rambut yang kering atau mudah rontok.',
                'translations' => [
                    'id' => ['name' => 'Spa Rambut & Kulit Kepala', 'description' => 'Pijat kulit kepala, masker nutrisi, dan uap hangat untuk rambut yang kering atau mudah rontok.'],
                    'en' => ['name' => 'Scalp & Hair Spa', 'description' => 'A scalp massage, a nourishing mask and warm steam for dry or thinning hair.'],
                    'ja' => ['name' => 'スカルプ＆ヘアスパ', 'description' => '頭皮マッサージ、栄養マスク、温スチームで、乾燥や抜け毛が気になる髪をケア。'],
                ],
            ],
            [
                'slug' => 'bridal-updo', 'category' => 'hair', 'name' => 'Bridal Updo', 'price' => 750000, 'duration_minutes' => 90, 'deposit' => 250000,
                'tags' => ['bridal'], 'cautions' => [], 'is_featured' => false,
                'description' => 'Sanggul atau tata rambut pengantin, termasuk aksesori dan fiksasi tahan seharian.',
                'translations' => [
                    'id' => ['name' => 'Tata Rambut Pengantin', 'description' => 'Sanggul atau tata rambut pengantin, termasuk aksesori dan fiksasi tahan seharian.'],
                    'en' => ['name' => 'Bridal Updo', 'description' => 'A bridal updo or hairstyle, with accessories and a hold that lasts all day.'],
                    'ja' => ['name' => 'ブライダルアップ', 'description' => 'アクセサリー込みのブライダルヘアセット。一日中崩れにくい仕上がりです。'],
                ],
            ],
            [
                'slug' => 'mens-cut-style', 'category' => 'hair', 'name' => "Men's Cut & Style", 'price' => 120000, 'duration_minutes' => 30, 'deposit' => 0,
                'tags' => ['for_men', 'quick', 'halal'], 'cautions' => [], 'is_featured' => false,
                'description' => 'Potongan pria cepat dengan styling pomade atau clay.',
                'translations' => [
                    'id' => ['name' => 'Potong Rambut Pria & Styling', 'description' => 'Potongan pria cepat dengan styling pomade atau clay.'],
                    'en' => ['name' => "Men's Cut & Style", 'description' => 'A quick men\'s cut finished with pomade or clay.'],
                    'ja' => ['name' => 'メンズカット＆スタイリング', 'description' => 'ポマードやクレイで仕上げる、手早いメンズカット。'],
                ],
            ],
            [
                'slug' => 'gel-manicure', 'category' => 'nails', 'name' => 'Gel Manicure', 'price' => 220000, 'duration_minutes' => 60, 'deposit' => 0,
                'tags' => ['bestseller'], 'cautions' => ['acrylic'], 'is_featured' => true,
                'description' => 'Perawatan kuku tangan, kutikula, dan warna gel tahan hingga 3 minggu.',
                'translations' => [
                    'id' => ['name' => 'Manikur Gel', 'description' => 'Perawatan kuku tangan, kutikula, dan warna gel tahan hingga 3 minggu.'],
                    'en' => ['name' => 'Gel Manicure', 'description' => 'Hand and cuticle care with a gel colour that lasts up to 3 weeks.'],
                    'ja' => ['name' => 'ジェルマニキュア', 'description' => 'ハンドケアとキューティクルケア、最長3週間持つジェルカラー。'],
                ],
            ],
            [
                'slug' => 'spa-pedicure', 'category' => 'nails', 'name' => 'Spa Pedicure', 'price' => 260000, 'duration_minutes' => 90, 'deposit' => 0,
                'tags' => ['halal'], 'cautions' => ['essential_oils'], 'is_featured' => false,
                'description' => 'Rendam kaki hangat, scrub, pijat betis, dan pewarna kuku pilihan.',
                'translations' => [
                    'id' => ['name' => 'Pedikur Spa', 'description' => 'Rendam kaki hangat, scrub, pijat betis, dan pewarna kuku pilihan.'],
                    'en' => ['name' => 'Spa Pedicure', 'description' => 'A warm foot soak, a scrub, a calf massage and your choice of polish.'],
                    'ja' => ['name' => 'スパペディキュア', 'description' => '温かいフットバス、スクラブ、ふくらはぎマッサージ、お好みのカラー。'],
                ],
            ],
            [
                'slug' => 'nail-art-custom', 'category' => 'nails', 'name' => 'Custom Nail Art', 'price' => 380000, 'duration_minutes' => 120, 'deposit' => 100000,
                'tags' => ['signature'], 'cautions' => ['acrylic'], 'is_featured' => false,
                'description' => 'Desain kuku sesuai foto referensi Anda, dengan gel, foil, atau hiasan kristal.',
                'translations' => [
                    'id' => ['name' => 'Nail Art Kustom', 'description' => 'Desain kuku sesuai foto referensi Anda, dengan gel, foil, atau hiasan kristal.'],
                    'en' => ['name' => 'Custom Nail Art', 'description' => 'A nail design made from your reference photo, with gel, foil or crystal details.'],
                    'ja' => ['name' => 'カスタムネイルアート', 'description' => 'ご持参の参考写真をもとに、ジェル、ホイル、クリスタルで仕上げるデザイン。'],
                ],
            ],
            [
                'slug' => 'hydrating-facial', 'category' => 'skin', 'name' => 'Hydrating Facial', 'price' => 450000, 'duration_minutes' => 60, 'deposit' => 0,
                'tags' => ['sensitive_skin', 'vegan', 'halal'], 'cautions' => ['fragrance'], 'is_featured' => true,
                'description' => 'Pembersihan, eksfoliasi lembut, masker hyaluronic, dan pijat wajah untuk kulit kusam atau kering.',
                'translations' => [
                    'id' => ['name' => 'Facial Hidrasi', 'description' => 'Pembersihan, eksfoliasi lembut, masker hyaluronic, dan pijat wajah untuk kulit kusam atau kering.'],
                    'en' => ['name' => 'Hydrating Facial', 'description' => 'Cleansing, gentle exfoliation, a hyaluronic mask and a face massage for dull or dry skin.'],
                    'ja' => ['name' => 'ハイドレーティングフェイシャル', 'description' => 'クレンジング、やさしい角質ケア、ヒアルロン酸マスク、フェイスマッサージ。くすみや乾燥が気になる肌に。'],
                ],
            ],
            [
                'slug' => 'acne-clearing-facial', 'category' => 'skin', 'name' => 'Acne Clearing Facial', 'price' => 580000, 'duration_minutes' => 90, 'deposit' => 0,
                'tags' => ['halal'], 'cautions' => ['retinol', 'fragrance'], 'is_featured' => false,
                'description' => 'Pembersihan pori, ekstraksi aman, dan masker penenang untuk kulit berjerawat.',
                'translations' => [
                    'id' => ['name' => 'Facial Jerawat', 'description' => 'Pembersihan pori, ekstraksi aman, dan masker penenang untuk kulit berjerawat.'],
                    'en' => ['name' => 'Acne Clearing Facial', 'description' => 'Pore cleansing, safe extractions and a calming mask for acne-prone skin.'],
                    'ja' => ['name' => 'ニキビケアフェイシャル', 'description' => '毛穴クレンジング、安全な角栓ケア、鎮静マスクで、ニキビができやすい肌を整えます。'],
                ],
            ],
            [
                'slug' => 'brightening-peel', 'category' => 'skin', 'name' => 'Brightening Peel', 'price' => 650000, 'duration_minutes' => 60, 'deposit' => 0,
                'tags' => [], 'cautions' => ['retinol', 'peroxide'], 'is_featured' => false,
                'description' => 'Peeling ringan untuk mencerahkan kulit dan menyamarkan noda ringan.',
                'translations' => [
                    'id' => ['name' => 'Peeling Pencerah', 'description' => 'Peeling ringan untuk mencerahkan kulit dan menyamarkan noda ringan.'],
                    'en' => ['name' => 'Brightening Peel', 'description' => 'A light peel that brightens the skin and softens minor marks.'],
                    'ja' => ['name' => 'ブライトニングピール', 'description' => '肌を明るくし、軽いシミやくすみをやわらげる軽めのピーリング。'],
                ],
            ],
            [
                'slug' => 'glam-makeup', 'category' => 'makeup', 'name' => 'Glam Makeup', 'price' => 500000, 'duration_minutes' => 60, 'deposit' => 0,
                'tags' => ['bestseller', 'halal'], 'cautions' => ['latex'], 'is_featured' => true,
                'description' => 'Makeup lengkap untuk pesta, wisuda, atau pemotretan, termasuk bulu mata.',
                'translations' => [
                    'id' => ['name' => 'Makeup Glam', 'description' => 'Makeup lengkap untuk pesta, wisuda, atau pemotretan, termasuk bulu mata.'],
                    'en' => ['name' => 'Glam Makeup', 'description' => 'Full makeup for a party, graduation or photoshoot, lashes included.'],
                    'ja' => ['name' => 'グラムメイク', 'description' => 'パーティー、卒業式、撮影のためのフルメイク。つけまつげ込み。'],
                ],
            ],
            [
                'slug' => 'bridal-makeup', 'category' => 'makeup', 'name' => 'Bridal Makeup', 'price' => 1800000, 'duration_minutes' => 150, 'deposit' => 500000,
                'tags' => ['bridal', 'signature'], 'cautions' => ['latex'], 'is_featured' => true,
                'description' => 'Makeup pengantin dengan uji coba, tahan seharian, dan touch-up sebelum akad atau resepsi.',
                'translations' => [
                    'id' => ['name' => 'Makeup Pengantin', 'description' => 'Makeup pengantin dengan uji coba, tahan seharian, dan touch-up sebelum akad atau resepsi.'],
                    'en' => ['name' => 'Bridal Makeup', 'description' => 'Bridal makeup with a trial, all-day wear and a touch-up before the ceremony or reception.'],
                    'ja' => ['name' => 'ブライダルメイク', 'description' => 'リハーサル付きで一日中崩れにくく、挙式や披露宴前のお直しも含むブライダルメイク。'],
                ],
            ],
            [
                'slug' => 'aromatherapy-massage', 'category' => 'spa', 'name' => 'Aromatherapy Massage', 'price' => 380000, 'duration_minutes' => 60, 'deposit' => 0,
                'tags' => ['halal'], 'cautions' => ['essential_oils', 'nut_oil'], 'is_featured' => false,
                'description' => 'Pijat relaksasi seluruh tubuh dengan minyak esensial pilihan Anda.',
                'translations' => [
                    'id' => ['name' => 'Pijat Aromaterapi', 'description' => 'Pijat relaksasi seluruh tubuh dengan minyak esensial pilihan Anda.'],
                    'en' => ['name' => 'Aromatherapy Massage', 'description' => 'A full-body relaxing massage with the essential oil you choose.'],
                    'ja' => ['name' => 'アロマテラピーマッサージ', 'description' => 'お好みのエッセンシャルオイルで行う、全身のリラクゼーションマッサージ。'],
                ],
            ],
            [
                'slug' => 'deep-tissue-massage', 'category' => 'spa', 'name' => 'Deep Tissue Massage', 'price' => 520000, 'duration_minutes' => 90, 'deposit' => 0,
                'tags' => ['halal'], 'cautions' => ['nut_oil'], 'is_featured' => false,
                'description' => 'Pijat dengan tekanan kuat untuk punggung, bahu, dan leher yang tegang.',
                'translations' => [
                    'id' => ['name' => 'Pijat Deep Tissue', 'description' => 'Pijat dengan tekanan kuat untuk punggung, bahu, dan leher yang tegang.'],
                    'en' => ['name' => 'Deep Tissue Massage', 'description' => 'A firm-pressure massage for a tense back, shoulders and neck.'],
                    'ja' => ['name' => 'ディープティシューマッサージ', 'description' => '背中、肩、首のこりに働きかける、しっかりした圧のマッサージ。'],
                ],
            ],
        ];
    }
}
