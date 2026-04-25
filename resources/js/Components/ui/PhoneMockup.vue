<script setup>
defineProps({
    // 254px screen (260px frame – 6px border) / 375px content = 0.677
    scale:    { type: Number, default: 0.677 },
    screenBg: { type: String, default: 'white' },
});
</script>

<template>
    <!--
        Outer frame: fixed visual dimensions of a phone bezel.
        Inner screen hosts a 375-px-wide slot that is CSS-scaled down.
    -->
    <div class="phone-frame">
        <!-- Status bar / notch row -->
        <div class="phone-status-bar">
            <div class="phone-notch" />
        </div>

        <!-- Screen — overflow-hidden so scaled content stays inside -->
        <div class="phone-screen" :style="{ background: screenBg }">
            <!--
                Scale container: always 375 px wide, scaled to fit the 260 px
                screen width (260/375 ≈ 0.693 but let caller override).
                Height expands naturally; screen clips it.
            -->
            <div
                class="phone-content-scaler"
                :style="{
                    transform: `scale(${scale})`,
                    transformOrigin: 'top left',
                    width: '375px',
                    // Make the logical height the inverse-scaled so the phone
                    // screen shows about a full page height worth of content.
                    minHeight: `${Math.round(560 / scale)}px`,
                }"
            >
                <slot />
            </div>
        </div>

        <!-- Home bar -->
        <div class="phone-home-bar" />
    </div>
</template>

<style scoped>
.phone-frame {
    width: 260px;
    height: 540px;
    border-radius: 44px;
    border: 3px solid #1C1C1E;
    background: #1C1C1E;
    padding: 0;
    display: flex;
    flex-direction: column;
    box-shadow:
        0 0 0 1px #3A3A3C,
        0 20px 60px rgba(0, 0, 0, 0.5),
        inset 0 0 0 2px #2C2C2E;
    flex-shrink: 0;
    position: relative;
    overflow: hidden;
}

.phone-status-bar {
    height: 28px;
    background: #1C1C1E;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border-radius: 44px 44px 0 0;
}

.phone-notch {
    width: 88px;
    height: 18px;
    background: #1C1C1E;
    border-radius: 0 0 16px 16px;
    position: relative;
    z-index: 2;
    box-shadow: 0 2px 4px rgba(0,0,0,0.4);
}

.phone-screen {
    flex: 1;
    background: white;
    overflow-y: auto;
    overflow-x: hidden;
    /* Hide scrollbar for clean look */
    scrollbar-width: none;
    -ms-overflow-style: none;
    border-radius: 2px;
}

.phone-screen::-webkit-scrollbar {
    display: none;
}

.phone-content-scaler {
    display: block;
}

.phone-home-bar {
    height: 22px;
    background: #1C1C1E;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border-radius: 0 0 44px 44px;
}

.phone-home-bar::after {
    content: '';
    width: 100px;
    height: 4px;
    background: #48484A;
    border-radius: 2px;
}
</style>
