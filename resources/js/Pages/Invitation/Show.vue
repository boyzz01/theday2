<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import PasswordGate       from './PasswordGate.vue';
import InvitationRenderer from '@/Components/invitation/InvitationRenderer.vue';

const props = defineProps({
    invitation:   { type: Object,  required: true },
    messages:     { type: Array,   default: () => [] },
    needPassword: { type: Boolean, default: false },
});

const unlocked = ref(! props.needPassword);
</script>

<template>
    <Head>
        <title>{{ invitation.title }}</title>
        <meta name="description" :content="`Undangan ${invitation.title}`"/>
        <meta property="og:title"  :content="invitation.title"/>
        <meta property="og:type"   content="website"/>
        <meta v-if="invitation.details?.cover_photo_url"
              property="og:image"  :content="invitation.details.cover_photo_url"/>
    </Head>

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
