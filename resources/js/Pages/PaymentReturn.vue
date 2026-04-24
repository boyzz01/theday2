<script setup>
defineProps({
    transactionId: String,
    status: String,
});
</script>

<template>
    <div>
        <p>Payment status: {{ status }}</p>
    </div>
</template>
