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
                'slug' => 'haircut-blow-dry', 'category' => 'hair', 'name' => 'Haircut & Blow Dry', 'price' => 250000, 'duration_minutes' => 60, 'deposit' => 0,
                'tags' => ['bestseller'], 'cautions' => [], 'is_featured' => true,
                'description' => 'Konsultasi bentuk wajah, potongan presisi, keramas, dan blow dry rapi.',
                'translations' => [
                    'id' => ['name' => 'Potong Rambut & Blow Dry', 'description' => 'Konsultasi bentuk wajah, potongan presisi, keramas, dan blow dry rapi.'],
                    'en' => ['name' => 'Haircut & Blow Dry', 'description' => 'A face-shape consultation, a precision cut, a wash and a polished blow dry.'],
                    'ja' => ['name' => 'カット＆ブロー', 'description' => '顔型のカウンセリング、精密なカット、シャンプー、仕上げのブローまで。'],
                ],
            ],
            [
                'slug' => 'balayage-color', 'category' => 'hair', 'name' => 'Balayage Color', 'price' => 1650000, 'duration_minutes' => 240, 'deposit' => 400000,
                'tags' => ['signature'], 'cautions' => ['peroxide', 'fragrance'], 'is_featured' => true,
                'description' => 'Pewarnaan tangan dengan gradasi lembut, termasuk toner, treatment, dan blow dry.',
                'translations' => [
                    'id' => ['name' => 'Balayage Color', 'description' => 'Pewarnaan tangan dengan gradasi lembut, termasuk toner, treatment, dan blow dry.'],
                    'en' => ['name' => 'Balayage Color', 'description' => 'Hand-painted colour with a soft gradient, including toner, a treatment and a blow dry.'],
                    'ja' => ['name' => 'バレイヤージュカラー', 'description' => '手作業で描く柔らかなグラデーションカラー。トナー、トリートメント、ブロー込み。'],
                ],
            ],
            [
                'slug' => 'keratin-smoothing', 'category' => 'hair', 'name' => 'Keratin Smoothing', 'price' => 1250000, 'duration_minutes' => 180, 'deposit' => 300000,
                'tags' => [], 'cautions' => ['fragrance'], 'is_featured' => false,
                'description' => 'Konsultasi kondisi rambut, aplikasi keratin, dan penataan dengan catokan. Hasil dan perawatan lanjut disesuaikan dengan kondisi rambut.',
                'translations' => [
                    'id' => ['name' => 'Keratin Smoothing', 'description' => 'Konsultasi kondisi rambut, aplikasi keratin, dan penataan dengan catokan. Hasil dan perawatan lanjut disesuaikan dengan kondisi rambut.'],
                    'en' => ['name' => 'Keratin Smoothing', 'description' => 'A hair assessment, keratin application and flat-iron styling. Results and aftercare depend on your hair condition.'],
                    'ja' => ['name' => 'ケラチンスムージング', 'description' => '髪の状態の確認、ケラチンの塗布、ヘアアイロンでの仕上げ。仕上がりとホームケアは髪の状態に合わせてご案内します。'],
                ],
            ],
            [
                'slug' => 'scalp-hair-spa', 'category' => 'hair', 'name' => 'Scalp & Hair Spa', 'price' => 350000, 'duration_minutes' => 60, 'deposit' => 0,
                'tags' => [], 'cautions' => ['essential_oils'], 'is_featured' => false,
                'description' => 'Keramas, pijat kulit kepala, masker rambut, dan pengeringan. Cocok untuk sesi perawatan rutin di sela aktivitas.',
                'translations' => [
                    'id' => ['name' => 'Spa Rambut & Kulit Kepala', 'description' => 'Keramas, pijat kulit kepala, masker rambut, dan pengeringan. Cocok untuk sesi perawatan rutin di sela aktivitas.'],
                    'en' => ['name' => 'Scalp & Hair Spa', 'description' => 'A wash, scalp massage, hair mask and drying. A practical treatment for your regular hair-care routine.'],
                    'ja' => ['name' => 'スカルプ＆ヘアスパ', 'description' => 'シャンプー、頭皮マッサージ、ヘアマスク、ドライまで。日常のヘアケアに取り入れやすいメニューです。'],
                ],
            ],
            [
                'slug' => 'bridal-updo', 'category' => 'hair', 'name' => 'Bridal Updo', 'price' => 850000, 'duration_minutes' => 90, 'deposit' => 250000,
                'tags' => ['bridal'], 'cautions' => [], 'is_featured' => false,
                'description' => 'Konsultasi gaya, sanggul atau tata rambut pengantin, serta pemasangan aksesori milik tamu. Dilakukan di salon pada satu sesi.',
                'translations' => [
                    'id' => ['name' => 'Tata Rambut Pengantin', 'description' => 'Konsultasi gaya, sanggul atau tata rambut pengantin, serta pemasangan aksesori milik tamu. Dilakukan di salon pada satu sesi.'],
                    'en' => ['name' => 'Bridal Updo', 'description' => 'A style consultation, bridal updo or hairstyle, and fitting of the guest\'s accessories. One appointment at the salon.'],
                    'ja' => ['name' => 'ブライダルアップ', 'description' => 'スタイル相談、ブライダルヘアセット、お持ち込みのアクセサリーの取り付け。サロンでの1回の施術です。'],
                ],
            ],
            [
                'slug' => 'mens-cut-style', 'category' => 'hair', 'name' => "Men's Cut & Style", 'price' => 180000, 'duration_minutes' => 30, 'deposit' => 0,
                'tags' => ['for_men', 'quick'], 'cautions' => [], 'is_featured' => false,
                'description' => 'Potongan pria cepat dengan styling pomade atau clay.',
                'translations' => [
                    'id' => ['name' => 'Potong Rambut Pria & Styling', 'description' => 'Potongan pria cepat dengan styling pomade atau clay.'],
                    'en' => ['name' => "Men's Cut & Style", 'description' => 'A quick men\'s cut finished with pomade or clay.'],
                    'ja' => ['name' => 'メンズカット＆スタイリング', 'description' => 'ポマードやクレイで仕上げる、手早いメンズカット。'],
                ],
            ],
            [
                'slug' => 'gel-manicure', 'category' => 'nails', 'name' => 'Gel Manicure', 'price' => 250000, 'duration_minutes' => 60, 'deposit' => 0,
                'tags' => ['bestseller'], 'cautions' => ['acrylic'], 'is_featured' => true,
                'description' => 'Pembentukan kuku, perawatan kutikula, dan warna gel polos untuk sepuluh kuku. Nail art dan pelepasan gel lama dipesan terpisah.',
                'translations' => [
                    'id' => ['name' => 'Manikur Gel', 'description' => 'Pembentukan kuku, perawatan kutikula, dan warna gel polos untuk sepuluh kuku. Nail art dan pelepasan gel lama dipesan terpisah.'],
                    'en' => ['name' => 'Gel Manicure', 'description' => 'Nail shaping, cuticle care and one plain gel colour on all ten nails. Nail art and removal of old gel are separate requests.'],
                    'ja' => ['name' => 'ジェルマニキュア', 'description' => '爪の形を整え、キューティクルをケアし、10本にワンカラーのジェルを塗布します。アートや以前のジェルの除去は別途ご相談ください。'],
                ],
            ],
            [
                'slug' => 'spa-pedicure', 'category' => 'nails', 'name' => 'Spa Pedicure', 'price' => 300000, 'duration_minutes' => 60, 'deposit' => 0,
                'tags' => [], 'cautions' => ['essential_oils'], 'is_featured' => false,
                'description' => 'Rendam kaki hangat, scrub, pijat betis, dan pewarna kuku pilihan.',
                'translations' => [
                    'id' => ['name' => 'Pedikur Spa', 'description' => 'Rendam kaki hangat, scrub, pijat betis, dan pewarna kuku pilihan.'],
                    'en' => ['name' => 'Spa Pedicure', 'description' => 'A warm foot soak, a scrub, a calf massage and your choice of polish.'],
                    'ja' => ['name' => 'スパペディキュア', 'description' => '温かいフットバス、スクラブ、ふくらはぎマッサージ、お好みのカラー。'],
                ],
            ],
            [
                'slug' => 'nail-art-custom', 'category' => 'nails', 'name' => 'Custom Nail Art', 'price' => 450000, 'duration_minutes' => 120, 'deposit' => 100000,
                'tags' => ['signature'], 'cautions' => ['acrylic'], 'is_featured' => false,
                'description' => 'Manikur gel dengan desain sederhana pada sepuluh kuku. Bawa foto referensi; detail desain dikonfirmasi sebelum sesi dimulai.',
                'translations' => [
                    'id' => ['name' => 'Nail Art Kustom', 'description' => 'Manikur gel dengan desain sederhana pada sepuluh kuku. Bawa foto referensi; detail desain dikonfirmasi sebelum sesi dimulai.'],
                    'en' => ['name' => 'Custom Nail Art', 'description' => 'A gel manicure with a simple design on all ten nails. Bring a reference photo; design details are agreed before the appointment.'],
                    'ja' => ['name' => 'カスタムネイルアート', 'description' => '10本の爪にシンプルなデザインを施すジェルマニキュア。参考写真をご持参ください。施術前にデザインの詳細を確認します。'],
                ],
            ],
            [
                'slug' => 'hydrating-facial', 'category' => 'skin', 'name' => 'Hydrating Facial', 'price' => 450000, 'duration_minutes' => 60, 'deposit' => 0,
                'tags' => [], 'cautions' => ['fragrance'], 'is_featured' => true,
                'description' => 'Pembersihan, eksfoliasi lembut, masker hyaluronic, dan pijat wajah untuk kulit kusam atau kering.',
                'translations' => [
                    'id' => ['name' => 'Facial Hidrasi', 'description' => 'Pembersihan, eksfoliasi lembut, masker hyaluronic, dan pijat wajah untuk kulit kusam atau kering.'],
                    'en' => ['name' => 'Hydrating Facial', 'description' => 'Cleansing, gentle exfoliation, a hyaluronic mask and a face massage for dull or dry skin.'],
                    'ja' => ['name' => 'ハイドレーティングフェイシャル', 'description' => 'クレンジング、やさしい角質ケア、ヒアルロン酸マスク、フェイスマッサージ。くすみや乾燥が気になる肌に。'],
                ],
            ],
            [
                'slug' => 'acne-clearing-facial', 'category' => 'skin', 'name' => 'Acne Clearing Facial', 'price' => 550000, 'duration_minutes' => 90, 'deposit' => 0,
                'tags' => [], 'cautions' => ['retinol', 'fragrance'], 'is_featured' => false,
                'description' => 'Konsultasi kondisi kulit, pembersihan wajah, dan masker. Tahapan disesuaikan dengan kondisi kulit; hasil tiap tamu dapat berbeda.',
                'translations' => [
                    'id' => ['name' => 'Facial Jerawat', 'description' => 'Konsultasi kondisi kulit, pembersihan wajah, dan masker. Tahapan disesuaikan dengan kondisi kulit; hasil tiap tamu dapat berbeda.'],
                    'en' => ['name' => 'Acne Clearing Facial', 'description' => 'A skin consultation, facial cleansing and a mask. Steps are tailored to your skin condition; results vary between guests.'],
                    'ja' => ['name' => 'ニキビケアフェイシャル', 'description' => '肌の状態の相談、クレンジング、マスク。肌の状態に合わせて内容を調整し、仕上がりには個人差があります。'],
                ],
            ],
            [
                'slug' => 'brightening-peel', 'category' => 'skin', 'name' => 'Brightening Peel', 'price' => 600000, 'duration_minutes' => 60, 'deposit' => 0,
                'tags' => [], 'cautions' => ['retinol', 'peroxide'], 'is_featured' => false,
                'description' => 'Konsultasi kulit, eksfoliasi permukaan, dan masker. Kesesuaian perawatan dikonfirmasi oleh terapis sebelum sesi.',
                'translations' => [
                    'id' => ['name' => 'Peeling Pencerah', 'description' => 'Konsultasi kulit, eksfoliasi permukaan, dan masker. Kesesuaian perawatan dikonfirmasi oleh terapis sebelum sesi.'],
                    'en' => ['name' => 'Brightening Peel', 'description' => 'A skin consultation, surface exfoliation and a mask. The therapist confirms suitability before the appointment.'],
                    'ja' => ['name' => 'ブライトニングピール', 'description' => '肌の相談、表面の角質ケア、マスク。施術前にセラピストがメニューの適合性を確認します。'],
                ],
            ],
            [
                'slug' => 'glam-makeup', 'category' => 'makeup', 'name' => 'Glam Makeup', 'price' => 650000, 'duration_minutes' => 90, 'deposit' => 100000,
                'tags' => ['bestseller'], 'cautions' => ['latex'], 'is_featured' => true,
                'description' => 'Makeup lengkap untuk pesta, wisuda, atau pemotretan, termasuk bulu mata.',
                'translations' => [
                    'id' => ['name' => 'Makeup Glam', 'description' => 'Makeup lengkap untuk pesta, wisuda, atau pemotretan, termasuk bulu mata.'],
                    'en' => ['name' => 'Glam Makeup', 'description' => 'Full makeup for a party, graduation or photoshoot, lashes included.'],
                    'ja' => ['name' => 'グラムメイク', 'description' => 'パーティー、卒業式、撮影のためのフルメイク。つけまつげ込み。'],
                ],
            ],
            [
                'slug' => 'bridal-makeup', 'category' => 'makeup', 'name' => 'Bridal Makeup', 'price' => 2500000, 'duration_minutes' => 120, 'deposit' => 750000,
                'tags' => ['bridal', 'signature'], 'cautions' => ['latex'], 'is_featured' => true,
                'description' => 'Makeup pengantin di salon, persiapan kulit, bulu mata, dan touch-up sebelum tamu berangkat. Trial, tata rambut, dan pendampingan lokasi acara tidak termasuk.',
                'translations' => [
                    'id' => ['name' => 'Makeup Pengantin', 'description' => 'Makeup pengantin di salon, persiapan kulit, bulu mata, dan touch-up sebelum tamu berangkat. Trial, tata rambut, dan pendampingan lokasi acara tidak termasuk.'],
                    'en' => ['name' => 'Bridal Makeup', 'description' => 'Bridal makeup at the salon with skin preparation, lashes and a touch-up before departure. Trials, hairstyling and attendance at the venue are not included.'],
                    'ja' => ['name' => 'ブライダルメイク', 'description' => 'サロンでのブライダルメイク、肌の準備、つけまつげ、出発前のお直し。リハーサル、ヘアセット、会場への同行は含まれません。'],
                ],
            ],
            [
                'slug' => 'aromatherapy-massage', 'category' => 'spa', 'name' => 'Aromatherapy Massage', 'price' => 400000, 'duration_minutes' => 60, 'deposit' => 0,
                'tags' => [], 'cautions' => ['essential_oils', 'nut_oil'], 'is_featured' => false,
                'description' => 'Pijat relaksasi seluruh tubuh dengan minyak esensial pilihan Anda.',
                'translations' => [
                    'id' => ['name' => 'Pijat Aromaterapi', 'description' => 'Pijat relaksasi seluruh tubuh dengan minyak esensial pilihan Anda.'],
                    'en' => ['name' => 'Aromatherapy Massage', 'description' => 'A full-body relaxing massage with the essential oil you choose.'],
                    'ja' => ['name' => 'アロマテラピーマッサージ', 'description' => 'お好みのエッセンシャルオイルで行う、全身のリラクゼーションマッサージ。'],
                ],
            ],
            [
                'slug' => 'deep-tissue-massage', 'category' => 'spa', 'name' => 'Deep Tissue Massage', 'price' => 550000, 'duration_minutes' => 90, 'deposit' => 0,
                'tags' => [], 'cautions' => ['nut_oil'], 'is_featured' => false,
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
