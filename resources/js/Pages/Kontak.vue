<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import axios from 'axios';

// ─── Form state ───────────────────────────────────────────────────────────────
const form = ref({
    name: '',
    email: '',
    subject: '',
    message: '',
    website: '', // honeypot
});

const errors  = ref({});
const loading = ref(false);
const submitted = ref(false);

const subjects = [
    'Pertanyaan umum',
    'Masalah teknis',
    'Pembayaran & langganan',
    'Saran & masukan',
    'Kerjasama & partnership',
    'Lainnya',
];

const msgLength = computed(() => form.value.message.length);

// ─── Inline validation ────────────────────────────────────────────────────────
function validateField(field) {
    const val = form.value[field];
    switch (field) {
        case 'name':
            if (!val.trim()) errors.value.name = 'Nama wajib diisi.';
            else if (val.trim().length < 2) errors.value.name = 'Nama minimal 2 karakter.';
            else delete errors.value.name;
            break;
        case 'email':
            if (!val.trim()) errors.value.email = 'Email wajib diisi.';
            else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) errors.value.email = 'Format email tidak valid.';
            else delete errors.value.email;
            break;
        case 'subject':
            if (!val) errors.value.subject = 'Pilih topik pesan.';
            else delete errors.value.subject;
            break;
        case 'message':
            if (!val.trim()) errors.value.message = 'Pesan wajib diisi.';
            else if (val.trim().length < 10) errors.value.message = 'Pesan minimal 10 karakter.';
            else if (val.length > 2000) errors.value.message = 'Pesan maksimal 2000 karakter.';
            else delete errors.value.message;
            break;
    }
}

function validateAll() {
    ['name', 'email', 'subject', 'message'].forEach(validateField);
    return Object.keys(errors.value).length === 0;
}

// ─── Submit ───────────────────────────────────────────────────────────────────
const submittedEmail = ref('');
const serverError    = ref('');

async function submit() {
    if (!validateAll()) return;

    loading.value     = true;
    serverError.value = '';

    try {
        await axios.post('/kontak', form.value);
        submittedEmail.value = form.value.email;
        submitted.value = true;
    } catch (err) {
        if (err.response?.status === 422) {
            const serverErrors = err.response.data.errors ?? {};
            Object.entries(serverErrors).forEach(([k, v]) => {
                errors.value[k] = Array.isArray(v) ? v[0] : v;
            });
        } else if (err.response?.status === 429) {
            serverError.value = err.response.data.message ?? 'Terlalu banyak percobaan. Coba lagi nanti.';
        } else {
            serverError.value = 'Terjadi kesalahan. Coba lagi atau hubungi kami via WhatsApp.';
        }
    } finally {
        loading.value = false;
    }
}

// ─── FAQ accordion ────────────────────────────────────────────────────────────
const faqs = [
    {
        q: 'Apakah TheDay benar-benar gratis?',
        a: 'Ya! Paket Free memungkinkan kamu membuat 1 undangan pernikahan digital tanpa biaya. Upgrade ke paket berbayar untuk fitur lebih lengkap seperti tanpa watermark dan musik kustom.',
    },
    {
        q: 'Bagaimana cara berbagi undangan ke tamu?',
        a: 'Setelah undangan dipublish, kamu akan mendapatkan link unik yang bisa langsung dibagikan via WhatsApp, SMS, atau media sosial. Tamu tidak perlu install aplikasi apapun.',
    },
    {
        q: 'Bisa ganti template setelah undangan dibuat?',
        a: 'Saat ini setiap undangan menggunakan satu template yang dipilih di awal. Kamu bisa mengedit semua konten undangan (teks, foto, musik, warna) kapan saja selama masa aktif.',
    },
    {
        q: 'Apakah ada garansi uang kembali?',
        a: 'Ada! Kami memberikan garansi uang kembali 7 hari untuk semua paket berbayar. Jika tidak puas dalam 7 hari pertama sejak pembayaran, hubungi kami dan kami akan proses pengembalian dana.',
    },
    {
        q: 'Tamu perlu install aplikasi untuk membuka undangan?',
        a: 'Tidak perlu sama sekali. Undangan TheDay berbasis web — tamu cukup klik link dan undangan langsung terbuka di browser HP mereka. Tidak ada download, tidak ada akun.',
    },
];

const openFaq = ref(null);
function toggleFaq(i) {
    openFaq.value = openFaq.value === i ? null : i;
}
</script>

<template>
    <Head>
        <title>Kontak — TheDay</title>
        <meta name="description" content="Ada pertanyaan tentang undangan digital TheDay? Hubungi kami via WhatsApp atau email. Kami siap membantu." />
    </Head>

    <PublicLayout>
        <div style="background-color: #FFFCF7; min-height: 100vh;">

            <!-- ── Page header ──────────────────────────────────────────── -->
            <div class="max-w-5xl mx-auto px-6 pt-14 pb-10 text-center">
                <p class="text-xs font-semibold uppercase tracking-widest mb-3" style="color: #C8A26B;">Hubungi Kami</p>
                <h1 class="text-3xl md:text-4xl font-bold text-stone-900 mb-3"
                    style="font-family: 'Cormorant Garamond', serif;">
                    Ada yang bisa kami bantu?
                </h1>
                <p class="text-stone-500 text-base max-w-md mx-auto leading-relaxed">
                    Ada pertanyaan atau butuh bantuan? Kami siap membantu kamu — pilih cara yang paling nyaman.
                </p>
            </div>

            <!-- ── Main two-column layout ────────────────────────────────── -->
            <div class="max-w-5xl mx-auto px-6 pb-16">
                <div class="grid grid-cols-1 lg:grid-cols-[360px_1fr] gap-10">

                    <!-- ── LEFT: Contact info ─────────────────────────────── -->
                    <div class="space-y-6">

                        <!-- WhatsApp -->
                        <div class="rounded-2xl border border-stone-100 bg-white p-6 shadow-sm">
                            <div class="flex items-start gap-4">
                                <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
                                     style="background-color: rgba(37,211,102,0.12)">
                                    <svg class="w-5 h-5" fill="#25D366" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-stone-800 mb-0.5">Chat via WhatsApp</p>
                                    <p class="text-xs text-stone-400 mb-3 leading-relaxed">
                                        Cara tercepat menghubungi kami. Biasanya kami balas dalam beberapa jam.
                                    </p>
                                    <a href="https://wa.me/6281234567890"
                                       target="_blank" rel="noopener"
                                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-white text-sm font-semibold transition-all hover:opacity-90 active:scale-[0.98]"
                                       style="background-color: #25D366">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                        </svg>
                                        Chat Sekarang
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="rounded-2xl border border-stone-100 bg-white p-6 shadow-sm">
                            <div class="flex items-start gap-4">
                                <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
                                     style="background-color: rgba(200,162,107,0.12)">
                                    <svg class="w-5 h-5" style="color: #C8A26B" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-stone-800 mb-0.5">Kirim Email</p>
                                    <p class="text-xs text-stone-400 mb-2 leading-relaxed">
                                        Untuk pertanyaan lebih detail. Kami balas dalam 1×24 jam pada hari kerja.
                                    </p>
                                    <a href="mailto:hello@theday.id"
                                       class="text-sm font-semibold hover:underline"
                                       style="color: #C8A26B">
                                        hello@theday.id
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Jam layanan -->
                        <div class="rounded-2xl border border-stone-100 bg-stone-50 px-5 py-4">
                            <div class="flex items-center gap-2 mb-1">
                                <svg class="w-4 h-4 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-xs font-semibold text-stone-500">Jam Layanan</p>
                            </div>
                            <p class="text-sm text-stone-700 font-medium">Senin – Sabtu, 09.00 – 21.00 WIB</p>
                            <p class="text-xs text-stone-400 mt-0.5">Di luar jam layanan, kami akan membalas secepatnya.</p>
                        </div>

                        <!-- Social media -->
                        <div class="rounded-2xl border border-stone-100 bg-white p-6 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-widest text-stone-400 mb-4">Media Sosial</p>
                            <div class="space-y-3">
                                <a href="https://instagram.com/theday.id" target="_blank" rel="noopener"
                                   class="flex items-center gap-3 text-sm text-stone-600 hover:text-stone-900 transition-colors group">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center"
                                         style="background: linear-gradient(135deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888)">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                                        </svg>
                                    </div>
                                    <span>@theday.id</span>
                                </a>
                                <a href="https://tiktok.com/@theday.id" target="_blank" rel="noopener"
                                   class="flex items-center gap-3 text-sm text-stone-600 hover:text-stone-900 transition-colors">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-black">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                                        </svg>
                                    </div>
                                    <span>@theday.id</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- ── RIGHT: Form / Success ───────────────────────────── -->
                    <div class="rounded-2xl border border-stone-100 bg-white shadow-sm p-8">

                        <!-- Success state -->
                        <div v-if="submitted" class="flex flex-col items-center justify-center text-center py-12">
                            <div class="w-16 h-16 rounded-full flex items-center justify-center mb-5"
                                 style="background-color: rgba(200,162,107,0.12)">
                                <svg class="w-8 h-8" style="color: #C8A26B" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold text-stone-900 mb-2"
                                style="font-family: 'Cormorant Garamond', serif;">
                                Pesan kamu sudah terkirim! 🤍
                            </h2>
                            <p class="text-stone-500 text-sm leading-relaxed max-w-sm mb-8">
                                Terima kasih sudah menghubungi kami. Kami akan membalas ke
                                <strong class="text-stone-700">{{ submittedEmail }}</strong> secepatnya.
                            </p>
                            <a href="/"
                               class="px-6 py-3 rounded-xl text-white text-sm font-semibold transition-all hover:opacity-90"
                               style="background-color: #C8A26B">
                                Kembali ke Beranda
                            </a>
                        </div>

                        <!-- Form -->
                        <form v-else @submit.prevent="submit" novalidate>
                            <h2 class="text-xl font-semibold text-stone-800 mb-1"
                                style="font-family: 'Cormorant Garamond', serif; font-size: 1.5rem;">
                                Kirim Pesan
                            </h2>
                            <p class="text-sm text-stone-400 mb-7">Isi form di bawah dan kami akan segera merespons.</p>

                            <!-- Server error -->
                            <div v-if="serverError"
                                 class="mb-5 flex items-center gap-2.5 px-4 py-3 rounded-xl bg-red-50 border border-red-100 text-sm text-red-600">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ serverError }}
                            </div>

                            <!-- Honeypot -->
                            <input type="text" v-model="form.website" name="website"
                                   autocomplete="off" tabindex="-1"
                                   style="position:absolute;left:-9999px;opacity:0;height:0;width:0;" />

                            <div class="space-y-5">
                                <!-- Name -->
                                <div>
                                    <label class="block text-sm font-medium text-stone-700 mb-1.5">
                                        Nama kamu <span class="text-red-400">*</span>
                                    </label>
                                    <input
                                        v-model="form.name"
                                        @blur="validateField('name')"
                                        type="text"
                                        placeholder="Nama lengkap"
                                        autocomplete="name"
                                        class="w-full px-4 py-3 rounded-xl border text-sm transition-colors outline-none focus:ring-2"
                                        :class="errors.name
                                            ? 'border-red-300 focus:ring-red-100 bg-red-50/40'
                                            : 'border-stone-200 focus:ring-amber-100 focus:border-amber-300 bg-white'"
                                    />
                                    <p v-if="errors.name" class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ errors.name }}
                                    </p>
                                </div>

                                <!-- Email -->
                                <div>
                                    <label class="block text-sm font-medium text-stone-700 mb-1.5">
                                        Alamat email <span class="text-red-400">*</span>
                                    </label>
                                    <input
                                        v-model="form.email"
                                        @blur="validateField('email')"
                                        type="email"
                                        placeholder="email@example.com"
                                        autocomplete="email"
                                        class="w-full px-4 py-3 rounded-xl border text-sm transition-colors outline-none focus:ring-2"
                                        :class="errors.email
                                            ? 'border-red-300 focus:ring-red-100 bg-red-50/40'
                                            : 'border-stone-200 focus:ring-amber-100 focus:border-amber-300 bg-white'"
                                    />
                                    <p v-if="errors.email" class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ errors.email }}
                                    </p>
                                </div>

                                <!-- Subject -->
                                <div>
                                    <label class="block text-sm font-medium text-stone-700 mb-1.5">
                                        Topik <span class="text-red-400">*</span>
                                    </label>
                                    <select
                                        v-model="form.subject"
                                        @blur="validateField('subject')"
                                        class="select-chevron w-full px-4 py-3 rounded-xl border text-sm transition-colors outline-none focus:ring-2 appearance-none bg-white"
                                        :class="errors.subject
                                            ? 'border-red-300 focus:ring-red-100'
                                            : 'border-stone-200 focus:ring-amber-100 focus:border-amber-300'"
                                    >
                                        <option value="" disabled>Pilih topik...</option>
                                        <option v-for="s in subjects" :key="s" :value="s">{{ s }}</option>
                                    </select>
                                    <p v-if="errors.subject" class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ errors.subject }}
                                    </p>
                                </div>

                                <!-- Message -->
                                <div>
                                    <label class="block text-sm font-medium text-stone-700 mb-1.5">
                                        Pesan <span class="text-red-400">*</span>
                                    </label>
                                    <textarea
                                        v-model="form.message"
                                        @blur="validateField('message')"
                                        rows="5"
                                        placeholder="Tuliskan pertanyaan atau pesanmu di sini..."
                                        class="w-full px-4 py-3 rounded-xl border text-sm transition-colors outline-none focus:ring-2 resize-none"
                                        :class="errors.message
                                            ? 'border-red-300 focus:ring-red-100 bg-red-50/40'
                                            : 'border-stone-200 focus:ring-amber-100 focus:border-amber-300 bg-white'"
                                        maxlength="2000"
                                    ></textarea>
                                    <div class="flex items-center justify-between mt-1">
                                        <p v-if="errors.message" class="text-xs text-red-500 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            {{ errors.message }}
                                        </p>
                                        <span v-else class="text-xs text-stone-300"></span>
                                        <span class="text-xs ml-auto"
                                              :class="msgLength > 1800 ? 'text-amber-500' : 'text-stone-300'">
                                            {{ msgLength }}/2000
                                        </span>
                                    </div>
                                </div>

                                <!-- Submit -->
                                <button
                                    type="submit"
                                    :disabled="loading"
                                    class="w-full py-3 rounded-xl text-sm font-semibold text-white transition-all disabled:opacity-60 disabled:cursor-not-allowed hover:opacity-90 active:scale-[0.99]"
                                    style="background-color: #C8A26B"
                                >
                                    <span v-if="loading" class="flex items-center justify-center gap-2">
                                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                        </svg>
                                        Mengirim...
                                    </span>
                                    <span v-else>Kirim Pesan →</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ── FAQ Section ────────────────────────────────────────────── -->
            <div class="max-w-3xl mx-auto px-6 pb-20">
                <div class="text-center mb-8">
                    <p class="text-xs font-semibold uppercase tracking-widest mb-2" style="color: #C8A26B;">FAQ</p>
                    <h2 class="text-2xl font-bold text-stone-900"
                        style="font-family: 'Cormorant Garamond', serif;">
                        Pertanyaan yang sering ditanyakan
                    </h2>
                </div>

                <div class="space-y-3">
                    <div
                        v-for="(faq, i) in faqs"
                        :key="i"
                        class="rounded-xl border bg-white overflow-hidden transition-all"
                        :class="openFaq === i ? 'border-amber-200 shadow-sm' : 'border-stone-100'"
                    >
                        <button
                            type="button"
                            @click="toggleFaq(i)"
                            class="w-full flex items-center justify-between px-5 py-4 text-left transition-colors"
                            :class="openFaq === i ? 'bg-amber-50/60' : 'hover:bg-stone-50'"
                        >
                            <span class="text-sm font-semibold text-stone-800 pr-4">{{ faq.q }}</span>
                            <svg
                                class="w-4 h-4 flex-shrink-0 transition-transform duration-200"
                                :class="openFaq === i ? 'rotate-180' : ''"
                                style="color: #C8A26B"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div
                            v-show="openFaq === i"
                            class="px-5 pb-4 text-sm text-stone-500 leading-relaxed border-t border-stone-100 pt-3"
                        >
                            {{ faq.a }}
                        </div>
                    </div>
                </div>

                <p class="text-center text-sm text-stone-400 mt-8">
                    Tidak menemukan jawaban?
                    <a href="https://wa.me/6281234567890" target="_blank" class="font-semibold hover:underline" style="color: #C8A26B;">
                        Hubungi kami langsung →
                    </a>
                </p>
            </div>

        </div>
    </PublicLayout>
</template>

<style scoped>
.select-chevron {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23aaa' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
}
</style>
