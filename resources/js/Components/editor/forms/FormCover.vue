<script setup>
import { computed } from 'vue';
import ImageUploader from '@/Components/editor/ImageUploader.vue';

const props = defineProps({
    data:         { type: Object, default: () => ({}) },
    invitationId: { type: String, required: true },
});
const emit = defineEmits(['update:data']);

function update(field, value) {
    emit('update:data', { ...props.data, [field]: value });
}

const guestNameMode   = computed(() => props.data.guest_name_mode   ?? 'query_param');
const showGuestName   = computed(() => props.data.show_guest_name   ?? true);
const showDate        = computed(() => props.data.show_date         ?? true);
const showPretitle    = computed(() => props.data.show_pretitle     ?? true);
const overlayOpacity  = computed(() => props.data.overlay_opacity   ?? 0.35);
const textAlign       = computed(() => props.data.text_align        ?? 'center');
const contentPosition = computed(() => props.data.content_position  ?? 'center');
const openAction      = computed(() => props.data.open_action       ?? 'enter_content');

const inputClass = 'w-full px-3 py-2 rounded-xl border border-stone-200 text-sm text-stone-800 placeholder-stone-300 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-200 transition-colors';
const labelClass = 'block text-xs font-medium text-stone-600 mb-1.5';
const groupClass = 'space-y-4 pt-4 pb-2';
const groupLabelClass = 'text-[11px] font-semibold text-stone-400 uppercase tracking-wider mb-3';
const toggleClass = 'relative inline-flex h-5 w-9 cursor-pointer rounded-full transition-colors focus:outline-none';
</script>

<template>
    <div class="space-y-1">

        <!-- ── Grup 1: Konten Utama ─────────────────────────────── -->
        <div :class="groupClass">
            <p :class="groupLabelClass">Konten Utama</p>

            <!-- Pretitle -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label :class="labelClass" class="mb-0">Teks Atas (Pretitle)</label>
                    <button
                        type="button"
                        :class="[toggleClass, (showPretitle ? 'bg-amber-400' : 'bg-stone-200')]"
                        @click="update('show_pretitle', !showPretitle)"
                        :aria-pressed="showPretitle"
                    >
                        <span :class="['absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform', showPretitle ? 'translate-x-4' : 'translate-x-0']"/>
                    </button>
                </div>
                <input
                    v-if="showPretitle"
                    type="text"
                    :value="data.pretitle ?? 'The Wedding Of'"
                    @input="update('pretitle', $event.target.value)"
                    placeholder="The Wedding Of"
                    maxlength="50"
                    :class="inputClass"
                />
            </div>

            <!-- Couple names -->
            <div>
                <label :class="labelClass">
                    Nama Kedua Mempelai <span class="text-red-400">*</span>
                </label>
                <input
                    type="text"
                    :value="data.couple_names ?? ''"
                    @input="update('couple_names', $event.target.value)"
                    placeholder="Ahmad & Siti"
                    maxlength="80"
                    :class="inputClass"
                />
            </div>

            <!-- Event date text -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label :class="labelClass" class="mb-0">Teks Tanggal</label>
                    <button
                        type="button"
                        :class="[toggleClass, (showDate ? 'bg-amber-400' : 'bg-stone-200')]"
                        @click="update('show_date', !showDate)"
                        :aria-pressed="showDate"
                    >
                        <span :class="['absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform', showDate ? 'translate-x-4' : 'translate-x-0']"/>
                    </button>
                </div>
                <input
                    v-if="showDate"
                    type="text"
                    :value="data.event_date_text ?? ''"
                    @input="update('event_date_text', $event.target.value)"
                    placeholder="12 September 2026"
                    maxlength="60"
                    :class="inputClass"
                />
            </div>

            <!-- Intro text -->
            <div>
                <label :class="labelClass">Teks Intro</label>
                <input
                    type="text"
                    :value="data.intro_text ?? ''"
                    @input="update('intro_text', $event.target.value)"
                    placeholder="Kepada Bapak/Ibu/Saudara/i"
                    maxlength="100"
                    :class="inputClass"
                />
            </div>

            <!-- Button text -->
            <div>
                <label :class="labelClass">
                    Teks Tombol <span class="text-red-400">*</span>
                </label>
                <input
                    type="text"
                    :value="data.button_text ?? 'Buka Undangan'"
                    @input="update('button_text', $event.target.value)"
                    placeholder="Buka Undangan"
                    maxlength="30"
                    :class="inputClass"
                />
            </div>
        </div>

        <div class="h-px bg-stone-100"/>

        <!-- ── Grup 2: Personalisasi Tamu ──────────────────────── -->
        <div :class="groupClass">
            <p :class="groupLabelClass">Personalisasi Tamu</p>

            <!-- show_guest_name toggle -->
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-stone-700">Tampilkan nama tamu</p>
                    <p class="text-xs text-stone-400 mt-0.5">Sapa tamu secara personal di cover</p>
                </div>
                <button
                    type="button"
                    :class="[toggleClass, (showGuestName ? 'bg-amber-400' : 'bg-stone-200')]"
                    @click="update('show_guest_name', !showGuestName)"
                    :aria-pressed="showGuestName"
                >
                    <span :class="['absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform', showGuestName ? 'translate-x-4' : 'translate-x-0']"/>
                </button>
            </div>

            <template v-if="showGuestName">
                <!-- guest_name_mode -->
                <div>
                    <label :class="labelClass">Mode Nama Tamu</label>
                    <select
                        :value="guestNameMode"
                        @change="update('guest_name_mode', $event.target.value)"
                        :class="inputClass"
                    >
                        <option value="query_param">Otomatis dari URL (?to=...)</option>
                        <option value="manual">Isi manual</option>
                        <option value="none">Nonaktif</option>
                    </select>
                </div>

                <!-- query_key (only when query_param) -->
                <div v-if="guestNameMode === 'query_param'">
                    <label :class="labelClass">Parameter URL</label>
                    <input
                        type="text"
                        :value="data.guest_query_key ?? 'to'"
                        @input="update('guest_query_key', $event.target.value)"
                        placeholder="to"
                        :class="inputClass"
                    />
                    <p class="mt-1 text-xs text-stone-400">
                        Nama tamu diambil dari URL, contoh: <code class="bg-stone-100 px-1 rounded">?to=Bapak+Andi</code>
                    </p>
                </div>

                <!-- manual guest name -->
                <div v-if="guestNameMode === 'manual'">
                    <label :class="labelClass">Nama Tamu</label>
                    <input
                        type="text"
                        :value="data.guest_name ?? ''"
                        @input="update('guest_name', $event.target.value)"
                        placeholder="Bapak Andi & Keluarga"
                        maxlength="120"
                        :class="inputClass"
                    />
                </div>

                <!-- fallback text -->
                <div>
                    <label :class="labelClass">Teks Tamu Default</label>
                    <input
                        type="text"
                        :value="data.fallback_guest_text ?? 'Tamu Undangan'"
                        @input="update('fallback_guest_text', $event.target.value)"
                        placeholder="Tamu Undangan"
                        maxlength="80"
                        :class="inputClass"
                    />
                    <p class="mt-1 text-xs text-stone-400">Ditampilkan jika nama tamu tidak tersedia</p>
                </div>
            </template>
        </div>

        <div class="h-px bg-stone-100"/>

        <!-- ── Grup 3: Background ───────────────────────────────── -->
        <div :class="groupClass">
            <p :class="groupLabelClass">Background</p>

            <!-- background_image -->
            <ImageUploader
                :model-value="data.background_image?.url ?? ''"
                @update:model-value="update('background_image', $event ? { asset_id: null, url: $event } : null)"
                :invitation-id="invitationId"
                upload-type="cover"
                label="Foto Background"
                aspect-class="aspect-video"
                hint="JPG / PNG / WebP · maks. 5 MB"
            />

            <!-- background_mobile_image -->
            <ImageUploader
                :model-value="data.background_mobile_image?.url ?? ''"
                @update:model-value="update('background_mobile_image', $event ? { asset_id: null, url: $event } : null)"
                :invitation-id="invitationId"
                upload-type="cover"
                label="Foto Background Mobile (opsional)"
                aspect-class="aspect-[9/16]"
                hint="Khusus tampilan portrait · maks. 5 MB"
            />

            <!-- background_position -->
            <div>
                <label :class="labelClass">Posisi Gambar</label>
                <select
                    :value="data.background_position ?? 'center'"
                    @change="update('background_position', $event.target.value)"
                    :class="inputClass"
                >
                    <option value="center">Tengah</option>
                    <option value="top">Atas</option>
                    <option value="bottom">Bawah</option>
                </select>
            </div>

            <!-- background_size -->
            <div>
                <label :class="labelClass">Ukuran Gambar</label>
                <select
                    :value="data.background_size ?? 'cover'"
                    @change="update('background_size', $event.target.value)"
                    :class="inputClass"
                >
                    <option value="cover">Cover (penuh)</option>
                    <option value="contain">Contain (utuh)</option>
                </select>
            </div>

            <!-- overlay_opacity -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label :class="labelClass" class="mb-0">Kegelapan Overlay</label>
                    <span class="text-xs text-stone-500 tabular-nums">{{ Math.round(overlayOpacity * 100) }}%</span>
                </div>
                <input
                    type="range"
                    min="0" max="1" step="0.05"
                    :value="overlayOpacity"
                    @input="update('overlay_opacity', parseFloat($event.target.value))"
                    class="w-full accent-amber-400"
                />
                <div class="flex justify-between text-[10px] text-stone-400 mt-0.5">
                    <span>Transparan</span>
                    <span>Gelap</span>
                </div>
            </div>
        </div>

        <div class="h-px bg-stone-100"/>

        <!-- ── Grup 4: Tampilan ─────────────────────────────────── -->
        <div :class="groupClass">
            <p :class="groupLabelClass">Tampilan</p>

            <!-- text_align -->
            <div>
                <label :class="labelClass">Rata Teks</label>
                <div class="flex gap-2">
                    <button
                        v-for="opt in [{ v: 'left', label: 'Kiri' }, { v: 'center', label: 'Tengah' }, { v: 'right', label: 'Kanan' }]"
                        :key="opt.v"
                        type="button"
                        @click="update('text_align', opt.v)"
                        :class="['flex-1 py-1.5 text-xs rounded-lg border transition-colors',
                                 textAlign === opt.v
                                    ? 'border-amber-400 bg-amber-50 text-amber-700 font-medium'
                                    : 'border-stone-200 text-stone-500 hover:border-stone-300']"
                    >
                        {{ opt.label }}
                    </button>
                </div>
            </div>

            <!-- content_position -->
            <div>
                <label :class="labelClass">Posisi Konten</label>
                <div class="flex gap-2">
                    <button
                        v-for="opt in [{ v: 'top', label: 'Atas' }, { v: 'center', label: 'Tengah' }, { v: 'bottom', label: 'Bawah' }]"
                        :key="opt.v"
                        type="button"
                        @click="update('content_position', opt.v)"
                        :class="['flex-1 py-1.5 text-xs rounded-lg border transition-colors',
                                 contentPosition === opt.v
                                    ? 'border-amber-400 bg-amber-50 text-amber-700 font-medium'
                                    : 'border-stone-200 text-stone-500 hover:border-stone-300']"
                    >
                        {{ opt.label }}
                    </button>
                </div>
            </div>

            <!-- show_ornament toggle -->
            <div class="flex items-center justify-between">
                <p class="text-sm text-stone-700">Tampilkan ornamen</p>
                <button
                    type="button"
                    :class="[toggleClass, ((data.show_ornament ?? true) ? 'bg-amber-400' : 'bg-stone-200')]"
                    @click="update('show_ornament', !(data.show_ornament ?? true))"
                    :aria-pressed="data.show_ornament ?? true"
                >
                    <span :class="['absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform', (data.show_ornament ?? true) ? 'translate-x-4' : 'translate-x-0']"/>
                </button>
            </div>
        </div>

        <div class="h-px bg-stone-100"/>

        <!-- ── Grup 5: Interaksi & Musik ────────────────────────── -->
        <div :class="groupClass">
            <p :class="groupLabelClass">Interaksi & Musik</p>

            <!-- open_action -->
            <div>
                <label :class="labelClass">Aksi Tombol Buka</label>
                <select
                    :value="openAction"
                    @change="update('open_action', $event.target.value)"
                    :class="inputClass"
                >
                    <option value="enter_content">Buka konten undangan</option>
                    <option value="scroll_to_next">Scroll ke section berikutnya</option>
                </select>
            </div>

            <!-- music_on_open -->
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-stone-700">Putar musik saat dibuka</p>
                    <p class="text-xs text-stone-400 mt-0.5">Musik diputar setelah tamu menekan tombol</p>
                </div>
                <button
                    type="button"
                    :class="[toggleClass, ((data.music_on_open ?? true) ? 'bg-amber-400' : 'bg-stone-200')]"
                    @click="update('music_on_open', !(data.music_on_open ?? true))"
                    :aria-pressed="data.music_on_open ?? true"
                >
                    <span :class="['absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform', (data.music_on_open ?? true) ? 'translate-x-4' : 'translate-x-0']"/>
                </button>
            </div>

            <!-- show_music_button -->
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-stone-700">Tampilkan tombol musik di cover</p>
                    <p class="text-xs text-stone-400 mt-0.5">Hanya jika template mendukung</p>
                </div>
                <button
                    type="button"
                    :class="[toggleClass, ((data.show_music_button ?? false) ? 'bg-amber-400' : 'bg-stone-200')]"
                    @click="update('show_music_button', !(data.show_music_button ?? false))"
                    :aria-pressed="data.show_music_button ?? false"
                >
                    <span :class="['absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform', (data.show_music_button ?? false) ? 'translate-x-4' : 'translate-x-0']"/>
                </button>
            </div>
        </div>

    </div>
</template>
