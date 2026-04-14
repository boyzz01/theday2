<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    articles:   Object,
    categories: Array,
    filters:    Object,
});

const statusLabels = {
    draft:     { label: 'Draft',       bg: '#F3F4F6', color: '#6B7280' },
    published: { label: 'Dipublikasi', bg: '#D1FAE5', color: '#059669' },
};

function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(dateStr));
}

function filterStatus(status) {
    router.get('/admin/articles', { status: status || undefined }, { preserveState: true, replace: true });
}

function toggleFeatured(id) {
    router.patch(`/admin/articles/${id}/featured`, {}, { preserveScroll: true });
}

function publish(id) {
    router.patch(`/admin/articles/${id}/publish`, {}, { preserveScroll: true });
}

function unpublish(id) {
    router.patch(`/admin/articles/${id}/unpublish`, {}, { preserveScroll: true });
}

function destroy(id, title) {
    if (!confirm(`Hapus artikel "${title}"? Tindakan ini tidak bisa dibatalkan.`)) return;
    router.delete(`/admin/articles/${id}`, { preserveScroll: true });
}
</script>

<template>
    <AdminLayout>
        <Head title="Kelola Artikel — Admin" />

        <div class="p-6 max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Artikel Blog</h1>
                    <p class="text-sm text-gray-400 mt-0.5">Kelola konten blog TheDay</p>
                </div>
                <Link
                    href="/admin/articles/create"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-white transition cursor-pointer hover:opacity-90"
                    style="background:var(--color-primary, #D4A373)"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tulis Artikel
                </Link>
            </div>

            <!-- Status filter -->
            <div class="flex gap-2 mb-6">
                <button
                    v-for="opt in [{ value: '', label: 'Semua' }, { value: 'published', label: 'Dipublikasi' }, { value: 'draft', label: 'Draft' }]"
                    :key="opt.value"
                    @click="filterStatus(opt.value)"
                    class="px-4 py-1.5 rounded-full text-sm font-medium transition cursor-pointer"
                    :style="(filters?.status ?? '') === opt.value
                        ? 'background:#1E293B;color:white'
                        : 'background:#F3F4F6;color:#6B7280'"
                >
                    {{ opt.label }}
                </button>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl overflow-hidden" style="box-shadow:0 2px 12px rgba(0,0,0,0.06)">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-stone-100 text-left text-xs text-gray-400 uppercase tracking-wider">
                            <th class="px-6 py-4 font-medium">Artikel</th>
                            <th class="px-4 py-4 font-medium hidden md:table-cell">Kategori</th>
                            <th class="px-4 py-4 font-medium hidden lg:table-cell">Tanggal</th>
                            <th class="px-4 py-4 font-medium">Status</th>
                            <th class="px-4 py-4 font-medium text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="article in articles.data"
                            :key="article.id"
                            class="border-b border-stone-50 hover:bg-stone-50 transition"
                        >
                            <!-- Title + featured -->
                            <td class="px-6 py-4">
                                <div class="flex items-start gap-3">
                                    <div v-if="article.cover_image_path" class="w-12 h-10 rounded-lg overflow-hidden flex-shrink-0 bg-stone-100">
                                        <img :src="`/storage/${article.cover_image_path}`" class="w-full h-full object-cover" />
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-800 line-clamp-1">{{ article.title }}</p>
                                        <p class="text-xs text-gray-400 mt-0.5">/blog/{{ article.slug }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 hidden md:table-cell">
                                <span v-if="article.category" class="text-xs text-gray-500">{{ article.category.name }}</span>
                                <span v-else class="text-xs text-gray-300">—</span>
                            </td>
                            <td class="px-4 py-4 hidden lg:table-cell text-gray-400 text-xs">
                                {{ formatDate(article.published_at) }}
                            </td>
                            <td class="px-4 py-4">
                                <span
                                    class="inline-block px-2.5 py-1 rounded-full text-xs font-medium"
                                    :style="`background:${statusLabels[article.status]?.bg};color:${statusLabels[article.status]?.color}`"
                                >
                                    {{ statusLabels[article.status]?.label ?? article.status }}
                                </span>
                                <span v-if="article.featured" class="ml-1 inline-block px-2 py-0.5 rounded-full text-xs font-medium"
                                      style="background:rgba(212,163,115,0.15);color:#B8865A">
                                    Unggulan
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <!-- Preview -->
                                    <a :href="`/blog/${article.slug}`" target="_blank"
                                       class="p-1.5 rounded-lg hover:bg-stone-100 text-gray-400 hover:text-gray-600 transition cursor-pointer" title="Lihat">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    <!-- Featured toggle -->
                                    <button @click="toggleFeatured(article.id)"
                                            class="p-1.5 rounded-lg hover:bg-stone-100 transition cursor-pointer"
                                            :class="article.featured ? 'text-amber-500' : 'text-gray-300 hover:text-gray-500'"
                                            title="Toggle Unggulan">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    </button>
                                    <!-- Publish / Unpublish -->
                                    <button v-if="article.status === 'draft'" @click="publish(article.id)"
                                            class="p-1.5 rounded-lg hover:bg-emerald-50 text-emerald-500 hover:text-emerald-600 transition cursor-pointer" title="Publikasi">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                    <button v-else @click="unpublish(article.id)"
                                            class="p-1.5 rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-500 transition cursor-pointer" title="Kembalikan ke Draft">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                        </svg>
                                    </button>
                                    <!-- Edit -->
                                    <Link :href="`/admin/articles/${article.id}/edit`"
                                          class="p-1.5 rounded-lg hover:bg-stone-100 text-gray-400 hover:text-gray-600 transition cursor-pointer" title="Edit">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </Link>
                                    <!-- Delete -->
                                    <button @click="destroy(article.id, article.title)"
                                            class="p-1.5 rounded-lg hover:bg-red-50 text-gray-300 hover:text-red-500 transition cursor-pointer" title="Hapus">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="!articles.data.length">
                            <td colspan="5" class="px-6 py-16 text-center text-gray-400 text-sm">
                                Belum ada artikel.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="articles.last_page > 1" class="mt-6 flex justify-center gap-2">
                <template v-for="link in articles.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url"
                          class="px-4 py-2 rounded-lg text-sm font-medium transition cursor-pointer"
                          :style="link.active ? 'background:#1E293B;color:white' : 'background:#F3F4F6;color:#6B7280'"
                          v-html="link.label" />
                    <span v-else class="px-4 py-2 rounded-lg text-sm text-gray-300" v-html="link.label" />
                </template>
            </div>
        </div>
    </AdminLayout>
</template>
