<?php

namespace Database\Seeders;

use App\Models\Salon;
use App\Models\SalonKnowledgeItem;
use Illuminate\Database\Seeder;

class SalonKnowledgeItemSeeder extends Seeder
{
    public function run(): void
    {
        $salon = Salon::where('name', SalonSeeder::NAME)->firstOrFail();
        $sort = 0;

        foreach ($this->knowledgeDefinitions() as $definition) {
            $salon->knowledgeItems()->updateOrCreate(
                ['title' => $definition['title']],
                [...$definition, 'sort_order' => $sort++, 'is_active' => true]
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function knowledgeDefinitions(): array
    {
        return [
            [
                'category' => SalonKnowledgeItem::CATEGORY_GENERAL, 'title' => 'Tentang Beauty Salon AI', 'tags' => ['about', 'cerita', 'story'], 'image_url' => null,
                'body' => 'Beauty Salon AI adalah salon kecantikan butik di Senopati yang menggabungkan perawatan rambut, kuku, kulit, makeup, dan spa. Tim kami terdiri dari enam stylist berpengalaman. Kami buka setiap hari pukul 09.00 sampai 20.00.',
                'translations' => [
                    'en' => ['title' => 'About Beauty Salon AI', 'body' => 'Beauty Salon AI is a boutique beauty salon in Senopati that brings hair, nails, skin, makeup and spa care together. Our team is six experienced stylists. We are open every day from 09:00 to 20:00.'],
                    'ja' => ['title' => 'Beauty Salon AIについて', 'body' => 'Beauty Salon AIはスノパティにあるブティックサロンで、ヘア、ネイル、スキンケア、メイク、スパを一か所でご提供します。経験豊富な6名のスタイリストが在籍しています。毎日9:00〜20:00に営業しています。'],
                ],
            ],
            [
                'category' => SalonKnowledgeItem::CATEGORY_POLICIES, 'title' => 'Kebijakan janji temu dan pembatalan', 'tags' => ['janji', 'appointment', 'batal', 'cancel', 'reschedule'], 'image_url' => null,
                'body' => 'Janji temu ditahan selama 15 menit dari jam mulai. Pembatalan atau perubahan jadwal gratis sampai 24 jam sebelumnya. Pembatalan kurang dari 24 jam atau tidak datang membuat deposit (bila ada) tidak dikembalikan.',
                'translations' => [
                    'en' => ['title' => 'Appointment and cancellation policy', 'body' => 'Appointments are held for 15 minutes from the start time. Cancelling or rescheduling is free up to 24 hours ahead. Cancelling later than that, or not showing up, means any deposit is not refunded.'],
                    'ja' => ['title' => 'ご予約とキャンセルについて', 'body' => 'ご予約は開始時間から15分間お取り置きします。24時間前までは無料で変更・キャンセルできます。それ以降のキャンセルや無断欠席の場合、デポジットがある場合は返金できません。'],
                ],
            ],
            [
                'category' => SalonKnowledgeItem::CATEGORY_POLICIES, 'title' => 'Deposit dan pembayaran', 'tags' => ['deposit', 'bayar', 'payment', 'dp', 'transfer'], 'image_url' => null,
                'body' => 'Sebagian layanan, seperti pewarnaan rambut panjang dan paket pengantin, memerlukan deposit untuk mengamankan jadwal; jumlahnya tertera pada halaman layanan. Pembayaran dilakukan di salon dengan tunai, kartu, atau QRIS; deposit dikurangkan dari total.',
                'translations' => [
                    'en' => ['title' => 'Deposits and payment', 'body' => 'Some services, such as long colour sessions and bridal packages, need a deposit to hold the booking; the amount is shown on the service page. Payment is made at the salon by cash, card or QRIS, and the deposit is deducted from the total.'],
                    'ja' => ['title' => 'デポジットとお支払い', 'body' => '長時間のカラーやブライダルパッケージなど一部のメニューでは、ご予約確保のためデポジットをお願いしています。金額はメニューページに表示されています。お支払いはサロンで現金、カード、QRISをご利用いただけ、デポジットは合計から差し引かれます。'],
                ],
            ],
            [
                'category' => SalonKnowledgeItem::CATEGORY_POLICIES, 'title' => 'Uji tempel (patch test) dan kulit sensitif', 'tags' => ['patch test', 'uji tempel', 'alergi', 'allergy', 'sensitif', 'sensitive'], 'image_url' => null,
                'body' => 'Untuk pewarnaan, keratin, peeling, dan bulu mata, kami menyarankan uji tempel 48 jam sebelumnya bila Anda punya kulit sensitif atau riwayat alergi. Beri tahu stylist tentang alergi, kehamilan, atau kondisi medis sebelum perawatan dimulai.',
                'translations' => [
                    'en' => ['title' => 'Patch tests and sensitive skin', 'body' => 'For colour, keratin, peels and lashes we recommend a patch test 48 hours ahead if you have sensitive skin or a history of allergies. Please tell your stylist about any allergy, pregnancy or medical condition before the treatment starts.'],
                    'ja' => ['title' => 'パッチテストと敏感肌', 'body' => 'カラー、ケラチン、ピーリング、まつげは、敏感肌やアレルギー歴がある方は48時間前のパッチテストをおすすめします。アレルギー、妊娠、持病がある場合は、施術前にスタイリストへお伝えください。'],
                ],
            ],
            [
                'category' => SalonKnowledgeItem::CATEGORY_POLICIES, 'title' => 'Keterlambatan', 'tags' => ['telat', 'terlambat', 'late', 'delay'], 'image_url' => null,
                'body' => 'Bila Anda terlambat lebih dari 15 menit, stylist akan menyesuaikan perawatan agar selesai tepat waktu, atau menawarkan jadwal baru bila waktunya tidak cukup.',
                'translations' => [
                    'en' => ['title' => 'Late arrivals', 'body' => 'If you are more than 15 minutes late, your stylist will adjust the treatment to finish on time, or offer a new time if there is not enough left.'],
                    'ja' => ['title' => 'ご遅刻について', 'body' => '15分以上遅れる場合は、終了時間に合わせて施術内容を調整するか、時間が足りない場合は別の日時をご案内します。'],
                ],
            ],
            [
                'category' => SalonKnowledgeItem::CATEGORY_FACILITIES, 'title' => 'Wi-Fi dan ruang tunggu', 'tags' => ['wifi', 'wi-fi', 'internet', 'tunggu', 'lounge', 'waiting'], 'image_url' => null,
                'body' => 'Wi-Fi gratis dan ruang tunggu nyaman dengan sofa, majalah, serta teh dan air mineral tanpa biaya. Colokan tersedia di setiap kursi.',
                'translations' => [
                    'en' => ['title' => 'Wi-Fi and waiting lounge', 'body' => 'Free Wi-Fi and a comfortable waiting lounge with sofas, magazines, and complimentary tea and water. Power outlets are at every chair.'],
                    'ja' => ['title' => 'Wi-Fiとウェイティングラウンジ', 'body' => '無料Wi-Fiと、ソファ、雑誌、無料のお茶とお水を用意した快適なラウンジをご利用いただけます。各席にコンセントもあります。'],
                ],
            ],
            [
                'category' => SalonKnowledgeItem::CATEGORY_FACILITIES, 'title' => 'Ruang bridal privat', 'tags' => ['bridal', 'pengantin', 'private room', 'ruang privat', 'vip'], 'image_url' => null,
                'body' => 'Ruang privat dengan cermin besar, pencahayaan makeup, dan area ganti untuk pengantin serta pendamping, dapat dipesan bersama paket pengantin.',
                'translations' => [
                    'en' => ['title' => 'Private bridal room', 'body' => 'A private room with a large mirror, makeup lighting and a changing area for the bride and her party, bookable together with a bridal package.'],
                    'ja' => ['title' => 'ブライダル専用個室', 'body' => '大きな鏡、メイク用の照明、更衣スペースを備えた個室で、花嫁と付き添いの方がご利用いただけます。ブライダルパッケージと一緒にご予約ください。'],
                ],
            ],
            [
                'category' => SalonKnowledgeItem::CATEGORY_FACILITIES, 'title' => 'Parkir', 'tags' => ['parkir', 'parking', 'mobil', 'motor', 'valet'], 'image_url' => null,
                'body' => 'Parkir gratis untuk 3 jam bagi tamu salon di basement gedung, dengan valet tersedia di akhir pekan. Parkir motor ada di depan.',
                'translations' => [
                    'en' => ['title' => 'Parking', 'body' => 'Free parking for 3 hours for salon guests in the building basement, with valet on weekends. Motorbike parking is at the front.'],
                    'ja' => ['title' => '駐車場', 'body' => 'ご来店のお客様は、ビルの地下駐車場を3時間無料でご利用いただけます。週末はバレーサービスもあります。バイク用の駐輪場は正面にあります。'],
                ],
            ],
            [
                'category' => SalonKnowledgeItem::CATEGORY_FACILITIES, 'title' => 'Musala', 'tags' => ['musala', 'mushola', 'ibadah', 'prayer', 'sholat'], 'image_url' => null,
                'body' => 'Ada musala kecil lengkap dengan perlengkapan salat, terbuka selama jam buka salon. Kami juga menyesuaikan jadwal perawatan bila Anda perlu salat.',
                'translations' => [
                    'en' => ['title' => 'Prayer room', 'body' => 'There is a small prayer room with prayer mats and garments, open during salon hours. We also adjust the treatment schedule if you need to pray.'],
                    'ja' => ['title' => '礼拝室', 'body' => '礼拝用の敷物と衣類を備えた小さな礼拝室を、営業時間中ご利用いただけます。礼拝が必要な場合は施術の時間も調整します。'],
                ],
            ],
            [
                'category' => SalonKnowledgeItem::CATEGORY_EVENTS, 'title' => 'Paket pengantin dan rombongan', 'tags' => ['pengantin', 'bridal', 'wedding', 'rombongan', 'group', 'bridesmaid'], 'image_url' => null,
                'body' => 'Kami menyediakan paket pengantin (makeup, tata rambut, uji coba) dan paket rombongan untuk pendamping pengantin atau acara kantor. Karena jadwalnya perlu disusun bersama tim, permintaan rombongan diteruskan ke tim kami lewat percakapan atau WhatsApp.',
                'translations' => [
                    'en' => ['title' => 'Bridal and group packages', 'body' => 'We offer bridal packages (makeup, hair and a trial) and group packages for a bridal party or an office event. Because the schedule is planned with the team, group requests are passed to our team through the chat or WhatsApp.'],
                    'ja' => ['title' => 'ブライダル・グループパッケージ', 'body' => 'ブライダルパッケージ（メイク、ヘア、リハーサル）と、付き添いの方や社内イベント向けのグループパッケージをご用意しています。スケジュールはスタッフと一緒に調整するため、グループのご依頼はチャットまたはWhatsAppでスタッフにお伝えします。'],
                ],
            ],
            [
                'category' => SalonKnowledgeItem::CATEGORY_LOCATION, 'title' => 'Lokasi dan transportasi', 'tags' => ['lokasi', 'location', 'alamat', 'address', 'mrt', 'stasiun', 'station', 'grab'], 'image_url' => null,
                'body' => 'Kami berada di Jl. Senopati Raya No. 21, Senopati, Jakarta. Ojek dan taksi online mudah ditemukan di depan salon, dan stasiun MRT terdekat bisa dicapai dengan sekali naik ojek.',
                'translations' => [
                    'en' => ['title' => 'Location and transport', 'body' => 'We are at Jl. Senopati Raya No. 21, Senopati, Jakarta. Ride-hailing is easy to find out front, and the nearest MRT station is one short ride away.'],
                    'ja' => ['title' => 'アクセス', 'body' => '住所はジャカルタ・スノパティのJl. Senopati Raya No. 21です。サロン前で配車アプリを利用しやすく、最寄りのMRT駅へも短い乗車で行けます。'],
                ],
            ],
            [
                'category' => SalonKnowledgeItem::CATEGORY_FAQ, 'title' => 'Apakah boleh datang tanpa janji temu?', 'tags' => ['walk-in', 'tanpa janji', 'langsung datang'], 'image_url' => null,
                'body' => 'Boleh bila stylist yang sesuai sedang kosong, terutama untuk potong rambut dan manikur. Untuk pewarnaan, facial, makeup, dan akhir pekan, kami sangat menyarankan membuat janji temu.',
                'translations' => [
                    'en' => ['title' => 'Do you take walk-ins?', 'body' => 'Yes, if the right stylist is free, especially for haircuts and manicures. For colour, facials, makeup and weekends we strongly recommend booking an appointment.'],
                    'ja' => ['title' => '予約なしでも利用できますか？', 'body' => '該当のスタイリストの手が空いていれば可能です。特にカットやマニキュアは対応しやすいです。カラー、フェイシャル、メイク、週末はご予約を強くおすすめします。'],
                ],
            ],
            [
                'category' => SalonKnowledgeItem::CATEGORY_FAQ, 'title' => 'Apakah produk yang dipakai halal?', 'tags' => ['halal', 'produk', 'products', 'vegan', 'bpom'], 'image_url' => null,
                'body' => 'Banyak layanan kami memakai produk yang berlabel halal atau vegan; layanan tersebut ditandai di halaman layanan. Bila ada produk tertentu yang perlu Anda hindari, beri tahu stylist sebelum perawatan.',
                'translations' => [
                    'en' => ['title' => 'Are your products halal?', 'body' => 'Many of our services use products labelled halal or vegan; those services are marked on the service page. If there is a particular product you need to avoid, tell your stylist before the treatment.'],
                    'ja' => ['title' => '使用する製品はハラール対応ですか？', 'body' => '多くのメニューでハラールまたはヴィーガン表示のある製品を使用しており、メニューページに表示しています。避けたい製品がある場合は、施術前にスタイリストへお伝えください。'],
                ],
            ],
            [
                'category' => SalonKnowledgeItem::CATEGORY_FAQ, 'title' => 'Bolehkah membawa foto referensi?', 'tags' => ['referensi', 'reference', 'foto', 'photo', 'desain'], 'image_url' => null,
                'body' => 'Sangat boleh. Foto referensi membantu stylist memahami gaya yang Anda inginkan, dan mereka akan jujur menyampaikan apa yang realistis untuk jenis rambut, kuku, atau kulit Anda.',
                'translations' => [
                    'en' => ['title' => 'Can I bring a reference photo?', 'body' => 'Please do. A reference photo helps your stylist understand the look you want, and they will be honest about what is realistic for your hair, nails or skin.'],
                    'ja' => ['title' => '参考写真を持って行っても良いですか？', 'body' => 'ぜひお持ちください。参考写真はご希望のイメージを共有するのに役立ち、スタイリストがお客様の髪、爪、肌で実現できる範囲を正直にお伝えします。'],
                ],
            ],
        ];
    }
}
