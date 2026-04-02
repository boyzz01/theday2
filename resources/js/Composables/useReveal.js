import { onMounted, onUnmounted, ref } from 'vue';

/**
 * Attach to a template ref to get CSS-based scroll-reveal.
 * Usage:
 *   const el = ref(null);
 *   useReveal(el);
 *   <div ref="el" class="reveal">…</div>
 */
export function useReveal(elRef, options = {}) {
    const { threshold = 0.15, delay = 0 } = options;
    const visible = ref(false);
    let observer;

    onMounted(() => {
        if (!elRef.value) return;
        if (delay) elRef.value.style.transitionDelay = `${delay}ms`;

        observer = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting) {
                    visible.value = true;
                    elRef.value?.classList.add('visible');
                    observer.disconnect();
                }
            },
            { threshold }
        );
        observer.observe(elRef.value);
    });

    onUnmounted(() => observer?.disconnect());

    return { visible };
}

/**
 * Staggered reveal for a list of child elements.
 * Usage:
 *   const list = ref(null);
 *   useStaggerReveal(list, '.item');
 */
export function useStaggerReveal(containerRef, childSelector = '.item', stagger = 80) {
    let observer;
    let triggered = false;

    onMounted(() => {
        if (!containerRef.value) return;

        observer = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting && !triggered) {
                    triggered = true;
                    const children = containerRef.value.querySelectorAll(childSelector);
                    children.forEach((el, i) => {
                        el.style.transitionDelay = `${i * stagger}ms`;
                        el.classList.add('visible');
                    });
                    observer.disconnect();
                }
            },
            { threshold: 0.1 }
        );
        observer.observe(containerRef.value);
    });

    onUnmounted(() => observer?.disconnect());
}
