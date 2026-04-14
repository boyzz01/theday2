<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    article:    Object,  // null = create mode
    categories: Array,
});

const isEdit = computed(() => !!props.article);

const form = useForm({
    title:            props.article?.title ?? '',
    slug:             props.article?.slug ?? '',
    excerpt:          props.article?.excerpt ?? '',
    content:          props.article?.content ?? '',
    status:           props.article?.status ?? 'draft',
    featured:         props.article?.featured ?? false,
    author_name:      props.article?.author_name ?? '',
    category_id:      props.article?.category_id ?? '',
    meta_title:       props.article?.meta_title ?? '',
    meta_description: props.article?.meta_description ?? '',
    canonical_url:    props.article?.canonical_url ?? '',
    cover_image:      null,
});

const coverPreview = ref(props.article?.cover_image_path ? `/storage/${props.article.cover_image_path}` : null);

function onCoverChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    form.cover_image = file;
    coverPreview.value = URL.createObjectURL(file);
}

function submit() {
    if (isEdit.value) {
        form.post(`/admin/articles/${props.article.id}?_method=PATCH`, {
            forceFormData: true,
        });
    } else {
        form.post('/admin/articles', { forceFormData: true });
    }
}

function saveDraft() {
    form.status = 'draft';
    submit();
}

function saveAndPublish() {
    form.status = 'published';
    submit();
}

// Auto-generate slug from title
function autoSlug() {
    if (form.slug) return;
    form.slug = form.title
        .toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .trim()
        .replace(/\s+/g, '-');
}
</script>

<template>
    <AdminLayout>
        <Head :title="isEdit ? 'Edit Artikel — Admin' : 'Tulis Artikel — Admin'" />

        <div class="p-6 max-w-5xl mx-auto">
            <!-- Header -->
            <div class="flex items-center gap-4 mb-8">
                <Link href="/admin/articles"
                      class="p-2 rounded-xl hover:bg-stone-100 text-gray-400 hover:text-gray-600 transition cursor-pointer">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </Link>
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">
                        {{ isEdit ? 'Edit Artikel' : 'Tulis Artikel Baru' }}
                    </h1>
                    <p class="text-sm text-gray-400 mt-0.5">{{ isEdit ? article.title : 'Buat konten baru untuk blog TheDay' }}</p>
                </div>
            </div>

            <div class="grid lg:grid-cols-3 gap-6">

                <!-- Main Editor -->
                <div class="lg:col-span-2 space-y-5">

                    <!-- Title -->
                    <div class="bg-white rounded-2xl p-6" style="box-shadow:0 2px 12px rgba(0,0,0,0.06)">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Judul Artikel *</label>
                        <input
                            v-model="form.title"
                            @blur="autoSlug"
                            type="text"
                            placeholder="Tulis judul artikel yang menarik..."
                            class="w-full px-4 py-3 rounded-xl border border-stone-200 text-gray-800 text-lg font-medium focus:outline-none transition"
                            onfocus="this.style.borderColor='#D4A373'"
                            onblur="this.style.borderColor=''"
                        />
                        <p v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</p>
                    </div>

                    <!-- Slug -->
                    <div class="bg-white rounded-2xl p-6" style="box-shadow:0 2px 12px rgba(0,0,0,0.06)">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Slug URL</label>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-400 whitespace-nowrap">/blog/</span>
                            <input
                                v-model="form.slug"
                                type="text"
                                placeholder="url-artikel-anda"
                                class="flex-1 px-4 py-2.5 rounded-xl border border-stone-200 text-sm focus:outline-none transition"
                                onfocus="this.style.borderColor='#D4A373'"
                                onblur="this.style.borderColor=''"
                            />
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Kosongkan untuk generate otomatis dari judul.</p>
                        <p v-if="form.errors.slug" class="text-red-500 text-xs mt-1">{{ form.errors.slug }}</p>
                    </div>

                    <!-- Excerpt -->
                    <div class="bg-white rounded-2xl p-6" style="box-shadow:0 2px 12px rgba(0,0,0,0.06)">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ringkasan (Excerpt)</label>
                        <textarea
                            v-model="form.excerpt"
                            rows="3"
                            placeholder="Deskripsi singkat artikel (tampil di index dan SEO)..."
                            class="w-full px-4 py-3 rounded-xl border border-stone-200 text-sm text-gray-700 focus:outline-none transition resize-none"
                            onfocus="this.style.borderColor='#D4A373'"
                            onblur="this.style.borderColor=''"
                        />
                        <div class="flex justify-end mt-1">
                            <span class="text-xs text-gray-400">{{ form.excerpt.length }}/500</span>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="bg-white rounded-2xl p-6" style="box-shadow:0 2px 12px rgba(0,0,0,0.06)">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Konten Artikel *</label>
                        <p class="text-xs text-gray-400 mb-3">Gunakan HTML dasar: &lt;h2&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;ol&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;a&gt;, &lt;blockquote&gt;</p>
                        <textarea
                            v-model="form.content"
                            rows="20"
                            placeholder="<h2>Subjudul</h2>&#10;<p>Paragraf pertama...</p>"
                            class="w-full px-4 py-3 rounded-xl border border-stone-200 text-sm text-gray-700 focus:outline-none transition resize-y font-mono"
                            onfocus="this.style.borderColor='#D4A373'"
                            onblur="this.style.borderColor=''"
                        />
                        <p v-if="form.errors.content" class="text-red-500 text-xs mt-1">{{ form.errors.content }}</p>
                    </div>

                    <!-- SEO -->
                    <div class="bg-white rounded-2xl p-6" style="box-shadow:0 2px 12px rgba(0,0,0,0.06)">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4">SEO & Meta</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Meta Title</label>
                                <input v-model="form.meta_title" type="text" placeholder="Default: judul artikel"
                                       class="w-full px-4 py-2.5 rounded-xl border border-stone-200 text-sm focus:outline-none transition"
                                       onfocus="this.style.borderColor='#D4A373'" onblur="this.style.borderColor=''"/>
                                <div class="flex justify-end mt-1">
                                    <span :class="form.meta_title.length > 60 ? 'text-red-400' : 'text-gray-400'" class="text-xs">
                                        {{ form.meta_title.length }}/60
                                    </span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Meta Description</label>
                                <textarea v-model="form.meta_description" rows="2" placeholder="Default: excerpt"
                                          class="w-full px-4 py-2.5 rounded-xl border border-stone-200 text-sm focus:outline-none transition resize-none"
                                          onfocus="this.style.borderColor='#D4A373'" onblur="this.style.borderColor=''"/>
                                <div class="flex justify-end mt-1">
                                    <span :class="form.meta_description.length > 160 ? 'text-red-400' : 'text-gray-400'" class="text-xs">
                                        {{ form.meta_description.length }}/160
                                    </span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Canonical URL</label>
                                <input v-model="form.canonical_url" type="url" placeholder="https://..."
                                       class="w-full px-4 py-2.5 rounded-xl border border-stone-200 text-sm focus:outline-none transition"
                                       onfocus="this.style.borderColor='#D4A373'" onblur="this.style.borderColor=''"/>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-5">

                    <!-- Publish actions -->
                    <div class="bg-white rounded-2xl p-5" style="box-shadow:0 2px 12px rgba(0,0,0,0.06)">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4">Publikasi</h3>
                        <div class="space-y-3">
                            <button
                                @click="saveAndPublish"
                                :disabled="form.processing"
                                class="w-full py-2.5 rounded-xl text-sm font-semibold text-white transition cursor-pointer disabled:opacity-50"
                                style="background:var(--color-primary, #D4A373)"
                            >
                                {{ isEdit && article.status === 'published' ? 'Simpan Perubahan' : 'Publikasi Artikel' }}
                            </button>
                            <button
                                @click="saveDraft"
                                :disabled="form.processing"
                                class="w-full py-2.5 rounded-xl text-sm font-semibold text-gray-600 bg-stone-100 hover:bg-stone-200 transition cursor-pointer disabled:opacity-50"
                            >
                                Simpan sebagai Draft
                            </button>
                        </div>
                        <p v-if="form.errors.status" class="text-red-500 text-xs mt-2">{{ form.errors.status }}</p>
                    </div>

                    <!-- Cover Image -->
                    <div class="bg-white rounded-2xl p-5" style="box-shadow:0 2px 12px rgba(0,0,0,0.06)">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Cover Image</h3>
                        <div v-if="coverPreview" class="mb-3 rounded-xl overflow-hidden aspect-video bg-stone-100">
                            <img :src="coverPreview" class="w-full h-full object-cover" />
                        </div>
                        <label class="flex items-center justify-center gap-2 w-full py-3 rounded-xl border-2 border-dashed border-stone-200 text-sm text-gray-400 hover:border-stone-400 hover:text-gray-600 transition cursor-pointer">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            {{ coverPreview ? 'Ganti Gambar' : 'Upload Cover' }}
                            <input type="file" accept="image/*" class="hidden" @change="onCoverChange" />
                        </label>
                        <p class="text-xs text-gray-400 mt-2 text-center">JPG/PNG, maks 2MB</p>
                        <p v-if="form.errors.cover_image" class="text-red-500 text-xs mt-1">{{ form.errors.cover_image }}</p>
                    </div>

                    <!-- Category + Author -->
                    <div class="bg-white rounded-2xl p-5" style="box-shadow:0 2px 12px rgba(0,0,0,0.06)">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4">Detail</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Kategori</label>
                                <select v-model="form.category_id"
                                        class="w-full px-3 py-2.5 rounded-xl border border-stone-200 text-sm focus:outline-none bg-white cursor-pointer"
                                        onfocus="this.style.borderColor='#D4A373'" onblur="this.style.borderColor=''">
                                    <option value="">— Pilih Kategori —</option>
                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                        {{ cat.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Nama Penulis</label>
                                <input v-model="form.author_name" type="text" placeholder="Tim TheDay"
                                       class="w-full px-3 py-2.5 rounded-xl border border-stone-200 text-sm focus:outline-none transition"
                                       onfocus="this.style.borderColor='#D4A373'" onblur="this.style.borderColor=''"/>
                            </div>
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium text-gray-600">Artikel Unggulan</p>
                                    <p class="text-xs text-gray-400">Tampil di bagian atas blog</p>
                                </div>
                                <button
                                    type="button"
                                    @click="form.featured = !form.featured"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition cursor-pointer"
                                    :style="form.featured ? 'background:var(--color-primary,#D4A373)' : 'background:#E5E7EB'"
                                >
                                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition"
                                          :class="form.featured ? 'translate-x-6' : 'translate-x-1'" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
