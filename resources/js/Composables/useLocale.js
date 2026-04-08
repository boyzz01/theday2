import { ref, readonly } from 'vue';

const LANG_KEY = 'theday_lang';
const locale = ref(localStorage.getItem(LANG_KEY) || 'id');

export function useLocale() {
    function setLocale(lang) {
        locale.value = lang;
        localStorage.setItem(LANG_KEY, lang);
    }

    function toggleLocale() {
        setLocale(locale.value === 'id' ? 'en' : 'id');
    }

    function t(id, en) {
        return locale.value === 'en' ? en : id;
    }

    return {
        locale: readonly(locale),
        setLocale,
        toggleLocale,
        t,
    };
}
