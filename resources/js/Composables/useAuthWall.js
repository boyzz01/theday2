import { ref } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const isAuthModalOpen = ref(false);
const authModalTab    = ref('register');

// Pending promise resolver — set when requireAuth() is waiting
let _resolve = null;

export function useAuthWall() {
    const page = usePage();

    /**
     * Gate that ensures the user is authenticated before proceeding.
     *
     * If already logged in → resolves immediately with user.
     * If guest → opens modal, resolves when authenticated (or null if dismissed).
     *
     * @param {'register'|'login'} tab  Which tab to open first
     * @returns {Promise<object|null>}
     */
    function requireAuth(tab = 'register') {
        const user = page.props.auth?.user;
        if (user) return Promise.resolve(user);

        authModalTab.value    = tab;
        isAuthModalOpen.value = true;

        return new Promise((resolve) => {
            _resolve = resolve;
        });
    }

    /**
     * Called by AuthModal when login/register succeeds.
     *
     * Performs a FULL page reload (no `only` restriction) so that page-level
     * props like `isGuest` are also refreshed from the server.
     */
    function onAuthenticated() {
        isAuthModalOpen.value = false;
        router.reload({
            onSuccess: (page) => {
                const user = page.props.auth?.user ?? true;
                if (_resolve) { _resolve(user); _resolve = null; }
            },
        });
    }

    /**
     * Called when the user dismisses the modal without authenticating.
     */
    function onDismiss() {
        isAuthModalOpen.value = false;
        if (_resolve) { _resolve(null); _resolve = null; }
    }

    return {
        isAuthModalOpen,
        authModalTab,
        requireAuth,
        onAuthenticated,
        onDismiss,
    };
}
