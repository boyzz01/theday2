<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center rounded-xl border border-brand-primary-soft bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-brand-text shadow-sm transition duration-200 ease-in-out hover:bg-brand-primary/10 hover:border-brand-primary focus:outline-none focus:ring-2 focus:ring-brand-primary focus:ring-offset-2 disabled:opacity-25 cursor-pointer"
    >
        <slot />
    </button>
</template>
