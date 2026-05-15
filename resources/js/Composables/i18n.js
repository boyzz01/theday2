import { createI18n } from 'vue-i18n';

const STORED = (typeof localStorage !== 'undefined' && localStorage.getItem('theday_lang')) || null;
const initialLocale = STORED && ['id', 'en'].includes(STORED) ? STORED : 'id';

export const i18n = createI18n({
    legacy: false,
    locale: initialLocale,
    fallbackLocale: 'id',
    messages: { id: {}, en: {} },
    missingWarn: false,
    fallbackWarn: false,
});

export function setI18nMessages(locale, messages) {
    if (!messages || typeof messages !== 'object') return;
    i18n.global.setLocaleMessage(locale, messages);
}
