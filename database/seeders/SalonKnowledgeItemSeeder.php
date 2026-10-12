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
                'body' => 'Beauty Salon AI adalah salon kecantikan butik di Senopati yang menggabungkan perawatan rambut, kuku, kulit, makeup, dan spa. Tim kami terdiri dari dua hair stylist, nail artist, terapis kulit, makeup artist, dan terapis spa. Salon buka setiap hari pukul 09.00 sampai 20.00; jadwal tiap anggota tim berbeda.',
                'translations' => [
                    'en' => ['title' => 'About Beauty Salon AI', 'body' => 'Beauty Salon AI is a boutique beauty salon in Senopati that brings hair, nails, skin, makeup and spa care together. Our team includes two hair stylists, a nail artist, a skin therapist, a makeup artist and a spa therapist. The salon opens daily from 09:00 to 20:00; each team member works a different schedule.'],
                    'ja' => ['title' => 'Beauty Salon AIについて', 'body' => 'Beauty Salon AIはスノパティにあるブティックサロンで、ヘア、ネイル、スキンケア、メイク、スパを一か所でご提供します。ヘアスタイリスト2名、ネイリスト、スキンセラピスト、メイクアップアーティスト、スパセラピストが在籍しています。毎日9:00〜20:00に営業し、スタッフの勤務時間はそれぞれ異なります。'],
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
                'body' => 'Beri tahu tim tentang riwayat alergi, kulit sensitif, dan kondisi yang dapat memengaruhi perawatan saat berkonsultasi. Tim akan mengonfirmasi produk yang digunakan serta kebutuhan uji tempel sebelum sesi.',
                'translations' => [
                    'en' => ['title' => 'Patch tests and sensitive skin', 'body' => 'Tell the team about allergies, sensitive skin and any condition that may affect your treatment during the consultation. The team will confirm the products used and any patch-test requirements before your appointment.'],
                    'ja' => ['title' => 'パッチテストと敏感肌', 'body' => 'カウンセリング時にアレルギー歴、敏感肌、施術に影響する可能性のある体調についてスタッフへお伝えください。施術前に使用製品とパッチテストの必要性を確認します。'],
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
                'category' => SalonKnowledgeItem::CATEGORY_FACILITIES, 'title' => 'Wi-Fi dan ruang tunggu', 'tags' => ['wifi', 'wi-fi', 'internet', 'tunggu', 'lounge', 'waiting'], 'image_url' => 'images/scenes/salon-lobby.webp',
                'body' => 'Wi-Fi gratis dan ruang tunggu nyaman dengan sofa, majalah, serta teh dan air mineral tanpa biaya. Colokan tersedia di area tunggu; minta bantuan tim bila Anda perlu mengisi daya.',
                'translations' => [
                    'en' => ['title' => 'Wi-Fi and waiting lounge', 'body' => 'Free Wi-Fi and a comfortable waiting lounge with sofas, magazines, and complimentary tea and water. Power outlets are available in the waiting area; ask the team if you need to charge a device.'],
                    'ja' => ['title' => 'Wi-Fiとウェイティングラウンジ', 'body' => '無料Wi-Fiと、ソファ、雑誌、無料のお茶とお水を用意した快適なラウンジをご利用いただけます。待合スペースにコンセントをご用意しています。充電が必要な場合はスタッフへお声がけください。'],
                ],
            ],
            [
                'category' => SalonKnowledgeItem::CATEGORY_FACILITIES, 'title' => 'Ruang bridal privat', 'tags' => ['bridal', 'pengantin', 'private room', 'ruang privat', 'vip'], 'image_url' => null,
                'body' => 'Ruang privat dengan cermin besar, pencahayaan makeup, dan area ganti untuk pengantin serta satu pendamping. Ketersediaan ruang dikonfirmasi bersama jadwal makeup pengantin.',
                'translations' => [
                    'en' => ['title' => 'Private bridal room', 'body' => 'A private room with a large mirror, makeup lighting and a changing area for the bride and one companion. Room availability is confirmed with the bridal makeup appointment.'],
                    'ja' => ['title' => 'ブライダル専用個室', 'body' => '大きな鏡、メイク用の照明、更衣スペースを備えた個室で、花嫁と付き添いの方1名がご利用いただけます。ブライダルメイクのご予約時に空き状況を確認します。'],
                ],
            ],
            [
                'category' => SalonKnowledgeItem::CATEGORY_FACILITIES, 'title' => 'Parkir', 'tags' => ['parkir', 'parking', 'mobil', 'motor'], 'image_url' => null,
                'body' => 'Tempat parkir mobil dan motor tersedia dengan kapasitas terbatas. Ketersediaan mengikuti kondisi saat kedatangan; hubungi tim sebelum datang bersama rombongan.',
                'translations' => [
                    'en' => ['title' => 'Parking', 'body' => 'Car and motorbike parking is available with limited capacity. Spaces depend on availability on arrival; contact the team before bringing a group.'],
                    'ja' => ['title' => '駐車場', 'body' => '車とバイクの駐車スペースがありますが台数に限りがあります。ご到着時の空き状況によりますので、グループでご来店の場合は事前にスタッフへご相談ください。'],
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
                'body' => 'Makeup pengantin dan tata rambut tersedia sebagai layanan terpisah dengan harga pada katalog. Trial, layanan di lokasi acara, dan permintaan rombongan perlu dikonfirmasi oleh tim; belum termasuk dalam harga atau durasi layanan yang ditampilkan.',
                'translations' => [
                    'en' => ['title' => 'Bridal and group packages', 'body' => 'Bridal makeup and hairstyling are separate services with prices in the catalogue. Trials, attendance at the event venue and group requests need confirmation from the team; they are not included in the displayed prices or durations.'],
                    'ja' => ['title' => 'ブライダル・グループパッケージ', 'body' => 'ブライダルメイクとヘアセットは、それぞれカタログ記載の料金でご利用いただけます。リハーサル、会場への同行、グループのご依頼はスタッフへの確認が必要で、表示料金や所要時間には含まれません。'],
                ],
            ],
            [
                'category' => SalonKnowledgeItem::CATEGORY_LOCATION, 'title' => 'Lokasi dan transportasi', 'tags' => ['lokasi', 'location', 'alamat', 'address', 'mrt', 'stasiun', 'station', 'grab'], 'image_url' => null,
                'body' => 'Kami berada di Jl. Senopati No. 21, Kebayoran Baru, Jakarta Selatan. Ojek dan taksi online mudah ditemukan di depan salon, dan stasiun MRT terdekat bisa dicapai dengan sekali naik ojek.',
                'translations' => [
                    'en' => ['title' => 'Location and transport', 'body' => 'We are at Jl. Senopati No. 21, Kebayoran Baru, Jakarta Selatan. Ride-hailing is easy to find out front, and the nearest MRT station is one short ride away.'],
                    'ja' => ['title' => 'アクセス', 'body' => '住所はジャカルタ南部、クバヨラン・バルのJl. Senopati No. 21です。サロン前で配車アプリを利用しやすく、最寄りのMRT駅へも短い乗車で行けます。'],
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
                'body' => 'Produk berbeda untuk tiap perawatan. Bila Anda memerlukan produk halal, vegan, atau ingin menghindari bahan tertentu, sampaikan kepada tim agar label dan ketersediaannya dapat dikonfirmasi sebelum booking.',
                'translations' => [
                    'en' => ['title' => 'Are your products halal?', 'body' => 'Products vary by treatment. If you require halal or vegan products, or need to avoid an ingredient, tell the team so labels and availability can be confirmed before booking.'],
                    'ja' => ['title' => '使用する製品はハラール対応ですか？', 'body' => '使用製品はメニューによって異なります。ハラールやヴィーガンの製品をご希望の場合、または避けたい成分がある場合は、ご予約前にスタッフへお伝えください。表示と在庫を確認します。'],
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
            [
                'category' => SalonKnowledgeItem::CATEGORY_FAQ, 'title' => 'Apakah semua stylist tersedia setiap hari?', 'tags' => ['jadwal', 'shift', 'istirahat', 'stylist', 'schedule'], 'image_url' => null,
                'body' => 'Salon buka setiap hari, tetapi tiap stylist memiliki shift, waktu istirahat satu jam, dan satu hari libur mingguan. Pilih layanan, stylist, dan tanggal untuk melihat jam mulai yang masih tersedia; perawatan harus selesai sebelum shift berakhir.',
                'translations' => [
                    'en' => ['title' => 'Is every stylist available every day?', 'body' => 'The salon opens daily, but each stylist has a shift, a one-hour break and one weekly day off. Choose a service, stylist and date to see available start times; the treatment must finish before the shift ends.'],
                    'ja' => ['title' => 'すべてのスタッフが毎日勤務していますか？', 'body' => 'サロンは毎日営業していますが、スタッフにはそれぞれ勤務時間、1時間の休憩、週1日の休日があります。メニュー、スタッフ、日付を選ぶと空いている開始時間をご確認いただけます。施術は勤務終了前に終わる必要があります。'],
                ],
            ],
            [
                'category' => SalonKnowledgeItem::CATEGORY_FAQ, 'title' => 'Apa yang perlu disiapkan sebelum datang?', 'tags' => ['persiapan', 'datang', 'consultation', 'prepare'], 'image_url' => null,
                'body' => 'Datang sekitar 10 menit sebelum jadwal untuk konsultasi singkat. Bawa foto referensi bila ada, sampaikan riwayat perawatan serta produk yang perlu dihindari, dan konfirmasi cakupan layanan sebelum sesi dimulai.',
                'translations' => [
                    'en' => ['title' => 'How should I prepare for my visit?', 'body' => 'Arrive around 10 minutes before your appointment for a short consultation. Bring any reference photos, share your treatment history and products to avoid, and confirm what the service includes before it begins.'],
                    'ja' => ['title' => '来店前に何を準備すればよいですか？', 'body' => '短いカウンセリングのため、ご予約の約10分前にご来店ください。参考写真があればご持参いただき、過去の施術や避けたい製品をお伝えください。施術前にメニューに含まれる内容を確認します。'],
                ],
            ],
        ];
    }
}
