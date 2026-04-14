<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Article;
use App\Models\BlogCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Inspirasi Pernikahan',  'slug' => 'inspirasi-pernikahan',  'description' => 'Ide dan inspirasi untuk hari pernikahanmu.'],
            ['name' => 'Tips Persiapan Nikah',  'slug' => 'tips-persiapan-nikah',  'description' => 'Panduan dan tips merencanakan pernikahan.'],
            ['name' => 'Undangan Digital',       'slug' => 'undangan-digital',       'description' => 'Semua tentang undangan pernikahan digital.'],
            ['name' => 'Dekorasi & Tema',        'slug' => 'dekorasi-tema',          'description' => 'Inspirasi dekorasi dan tema pernikahan.'],
            ['name' => 'Budget & Checklist',     'slug' => 'budget-checklist',       'description' => 'Tips mengatur anggaran dan checklist pernikahan.'],
            ['name' => 'Tradisi & Acara',        'slug' => 'tradisi-acara',          'description' => 'Prosesi, tradisi, dan acara pernikahan Indonesia.'],
        ];

        foreach ($categories as $cat) {
            BlogCategory::firstOrCreate(['slug' => $cat['slug']], $cat);
        }

        $catIds = BlogCategory::pluck('id', 'slug');

        $articles = [
            [
                'title'       => '10 Ide Undangan Pernikahan Digital yang Elegan dan Modern',
                'slug'        => '10-ide-undangan-pernikahan-digital-elegan-modern',
                'excerpt'     => 'Undangan pernikahan digital kini hadir dalam berbagai gaya yang indah. Temukan 10 inspirasi undangan digital yang elegan, modern, dan berkesan untuk hari spesialmu.',
                'content'     => '<h2>Mengapa Memilih Undangan Digital?</h2>
<p>Di era digital ini, undangan pernikahan online bukan sekadar pengganti undangan cetak. Undangan digital memberikan pengalaman yang lebih interaktif, bisa dibagikan lewat WhatsApp dalam hitungan detik, dan memuat fitur RSVP real-time yang sangat membantu perencanaan.</p>

<h2>1. Tema Minimalis dengan Sentuhan Floral</h2>
<p>Desain minimalis dengan ilustrasi bunga-bunga lembut memberikan kesan elegan tanpa berlebihan. Cocok untuk pasangan yang menyukai gaya bersih dan modern.</p>

<h2>2. Aesthetic Dark Romantic</h2>
<p>Latar hitam atau navy dengan aksen emas menciptakan suasana mewah dan dramatis. Pilihan sempurna untuk pernikahan malam hari atau venue indoor yang eksklusif.</p>

<h2>3. Javanese Contemporary</h2>
<p>Motif batik modern dikombinasikan dengan tipografi elegan. Merayakan kekayaan budaya Indonesia sekaligus terasa segar dan kontemporer.</p>

<h2>4. Garden Party Greenery</h2>
<p>Palet hijau sage dan putih dengan ilustrasi dedaunan menciptakan nuansa outdoor yang segar dan hangat.</p>

<h2>5. Blush & Gold Classic</h2>
<p>Perpaduan warna blush pink dan aksen emas adalah kombinasi timeless yang selalu cantik untuk pernikahan.</p>

<h2>Tips Memilih Desain</h2>
<ul>
<li>Sesuaikan tema undangan dengan venue dan konsep pernikahan secara keseluruhan</li>
<li>Pastikan foto pasangan yang digunakan berkualitas tinggi</li>
<li>Cek kompatibilitas tampilan di smartphone</li>
<li>Gunakan font yang mudah dibaca di layar kecil</li>
</ul>

<p>Temukan ratusan template undangan digital premium di <a href="/templates">galeri template TheDay</a> dan buat undanganmu dalam hitungan menit.</p>',
                'status'      => 'published',
                'featured'    => true,
                'author_name' => 'Tim TheDay',
                'category_id' => $catIds['undangan-digital'],
                'published_at'=> now()->subDays(2),
                'meta_title'  => '10 Ide Undangan Pernikahan Digital Elegan — TheDay',
                'meta_description' => 'Temukan 10 inspirasi undangan pernikahan digital yang elegan dan modern. Dari minimalis floral hingga dark romantic, ada untuk setiap gaya.',
            ],
            [
                'title'       => 'Cara Mengatur Budget Pernikahan agar Tidak Membengkak',
                'slug'        => 'cara-mengatur-budget-pernikahan',
                'excerpt'     => 'Pernikahan impian bisa terwujud tanpa harus menguras kantong. Berikut panduan lengkap mengatur anggaran pernikahan secara cerdas.',
                'content'     => '<h2>Mulai dengan Angka yang Realistis</h2>
<p>Sebelum memulai perencanaan, sepakati bersama pasangan berapa total anggaran yang tersedia. Angka ini menjadi fondasi semua keputusan berikutnya.</p>

<h2>Prioritas Alokasi Budget</h2>
<p>Umumnya distribusi anggaran pernikahan di Indonesia mengikuti pola berikut:</p>
<ul>
<li><strong>Venue & Catering</strong>: 40–50% dari total budget</li>
<li><strong>Dokumentasi (foto & video)</strong>: 10–15%</li>
<li><strong>Gaun & Busana</strong>: 8–12%</li>
<li><strong>Dekorasi & Bunga</strong>: 8–10%</li>
<li><strong>Entertainment & MC</strong>: 5–8%</li>
<li><strong>Undangan & Stationery</strong>: 2–5%</li>
<li><strong>Lain-lain & Cadangan</strong>: 10%</li>
</ul>

<h2>Tips Hemat Tanpa Mengurangi Kualitas</h2>
<ol>
<li>Pilih hari Jumat atau hari kerja untuk venue — biasanya lebih murah 20–30%</li>
<li>Batasi jumlah tamu undangan sesuai kemampuan</li>
<li>Gunakan undangan digital — hemat biaya cetak dan ongkir</li>
<li>Pertimbangkan vendor lokal yang kualitasnya tak kalah dari vendor ternama</li>
<li>Manfaatkan periode off-peak untuk booking vendor</li>
</ol>

<h2>Gunakan Checklist Budget Digital</h2>
<p>Catat setiap pengeluaran dan bandingkan dengan anggaran yang sudah ditetapkan. Fitur Budget Planner di TheDay membantu kamu memantau seluruh pengeluaran pernikahan dalam satu tempat.</p>',
                'status'      => 'published',
                'featured'    => false,
                'author_name' => 'Tim TheDay',
                'category_id' => $catIds['budget-checklist'],
                'published_at'=> now()->subDays(5),
                'meta_title'  => 'Cara Mengatur Budget Pernikahan agar Tidak Membengkak — TheDay',
                'meta_description' => 'Panduan lengkap mengatur anggaran pernikahan agar tidak bengkak. Tips cerdas alokasi budget dari venue hingga undangan digital.',
            ],
            [
                'title'       => 'Panduan Lengkap RSVP Online untuk Pernikahan Modern',
                'slug'        => 'panduan-rsvp-online-pernikahan-modern',
                'excerpt'     => 'RSVP online membuat pengelolaan tamu jauh lebih mudah. Ketahui cara mengoptimalkan fitur RSVP agar data tamu selalu akurat dan tepat waktu.',
                'content'     => '<h2>Apa itu RSVP Online?</h2>
<p>RSVP (Répondez s\'il vous plaît) online adalah sistem konfirmasi kehadiran tamu melalui tautan digital. Tamu cukup membuka undangan dan mengisi form konfirmasi tanpa perlu menghubungi pengantin satu per satu.</p>

<h2>Keunggulan RSVP Online</h2>
<ul>
<li>Data kehadiran terkumpul otomatis dan real-time</li>
<li>Mengurangi komunikasi manual yang memakan waktu</li>
<li>Tamu bisa konfirmasi kapan saja dari smartphone</li>
<li>Mudah diakses untuk semua usia</li>
<li>Data tersimpan rapi dan bisa diekspor</li>
</ul>

<h2>Tips Meningkatkan Tingkat Respons RSVP</h2>
<ol>
<li><strong>Tetapkan deadline yang jelas</strong> — minimal 2 minggu sebelum hari H</li>
<li><strong>Kirim pengingat</strong> — follow up via WhatsApp untuk tamu yang belum konfirmasi</li>
<li><strong>Buat prosesnya mudah</strong> — form RSVP sebaiknya hanya 2–3 pertanyaan</li>
<li><strong>Personalisasi undangan</strong> — tamu lebih semangat respons saat dipanggil dengan nama</li>
</ol>

<h2>Integrasi dengan Guest List</h2>
<p>Di TheDay, data RSVP langsung tersinkronisasi dengan fitur Guest List. Kamu bisa memantau siapa yang sudah konfirmasi, belum, atau tidak hadir — semua dari satu dashboard yang mudah dipahami.</p>

<p><a href="/templates">Coba buat undangan dengan RSVP online sekarang →</a></p>',
                'status'      => 'published',
                'featured'    => false,
                'author_name' => 'Tim TheDay',
                'category_id' => $catIds['tips-persiapan-nikah'],
                'published_at'=> now()->subDays(8),
                'meta_title'  => 'Panduan RSVP Online untuk Pernikahan Modern — TheDay',
                'meta_description' => 'Cara mengoptimalkan RSVP online di undangan pernikahan digital. Tips agar semua tamu konfirmasi tepat waktu dan data kehadiran akurat.',
            ],
        ];

        foreach ($articles as $data) {
            Article::firstOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
