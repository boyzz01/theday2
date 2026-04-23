<script setup>
// Editor Mode layout — 3-panel: sidebar | form | preview
// Separate from DashboardLayout by design (spec requirement).
defineProps({
    title: { type: String, default: '' },
});
</script>

<template>
    <div class="h-screen flex flex-col overflow-hidden" style="background-color: #FFFDF7">

        <!-- ── Top bar slot ──────────────────────────────────────── -->
        <slot name="topbar" />

        <!-- ── 3-column body ─────────────────────────────────────── -->
        <div class="flex flex-1 min-h-0 overflow-hidden">

            <!-- Left: section sidebar -->
            <slot name="sidebar" />

            <!-- Center: form panel -->
            <slot name="form" />

            <!-- Right: live preview -->
            <slot name="preview" />

        </div>
    </div>
</template>
