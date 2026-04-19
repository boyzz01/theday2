<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import PasswordGate       from './PasswordGate.vue';
import InvitationRenderer from '@/Components/invitation/InvitationRenderer.vue';

const props = defineProps({
    invitation:   { type: Object,  required: true },
    messages:     { type: Array,   default: () => [] },
    needPassword: { type: Boolean, default: false },
    isPreview:    { type: Boolean, default: false },
    guest:        { type: Object,  default: null },
});

const unlocked = ref(! props.needPassword);
</script>

<template>
    <Head>
        <title>{{ isPreview ? `[Preview] ${invitation.title}` : invitation.title }}</title>
        <meta name="description" :content="`Undangan ${invitation.title}`"/>
        <meta property="og:title"  :content="invitation.title"/>
        <meta property="og:type"   content="website"/>
        <meta v-if="invitation.details?.cover_photo_url"
              property="og:image"  :content="invitation.details.cover_photo_url"/>
        <meta v-if="isPreview" name="robots" content="noindex"/>
    </Head>

    <!-- Preview banner -->
    <div v-if="isPreview"
         style="position:fixed;top:0;left:0;right:0;z-index:9999;background:#1A2720;color:#B8C7BF;font-family:Arial,sans-serif;font-size:13px;font-weight:600;text-align:center;padding:10px 16px;display:flex;align-items:center;justify-content:center;gap:10px;box-shadow:0 2px 8px rgba(0,0,0,0.3);">
        <span style="color:#92A89C;">👁</span>
        Mode Preview — Undangan ini masih berstatus <strong style="color:#fff;">Draft</strong> dan belum dipublikasikan.
        <a :href="`/dashboard/invitations/${invitation.id}/edit`"
           style="margin-left:8px;padding:4px 12px;background:#92A89C;color:#fff;border-radius:8px;text-decoration:none;font-size:12px;">
            Edit →
        </a>
    </div>

    <!-- Spacer agar konten tidak tertutup banner -->
    <div v-if="isPreview" style="height:41px;"></div>

    <!-- Password gate -->
    <PasswordGate
        v-if="!unlocked"
        :slug="invitation.slug"
        :primary-color="invitation.config?.primary_color ?? '#92A89C'"
        :font-family="invitation.config?.font_title ?? invitation.config?.font ?? 'Playfair Display'"
        :cover-url="invitation.details?.cover_photo_url"
        @unlocked="unlocked = true"
    />

    <!-- Full invitation renderer -->
    <InvitationRenderer
        v-else
        :invitation="invitation"
        :messages="messages"
        :guest="guest"
        :is-demo="false"
        :auto-open="false"
    />
</template>

<style>
:root { --inv-primary: #92A89C; --inv-font: 'Playfair Display', serif; }
.reveal { opacity: 0; transform: translateY(24px); transition: opacity 0.7s ease, transform 0.7s ease; }
.reveal.visible { opacity: 1; transform: translateY(0); }
.envelope-leave-active { transition: opacity 0.6s ease, transform 0.6s ease; }
.envelope-leave-to     { opacity: 0; transform: scale(1.04); }
@keyframes spin-slow { to { transform: rotate(360deg); } }
.animate-spin-slow { animation: spin-slow 4s linear infinite; }
</style>
