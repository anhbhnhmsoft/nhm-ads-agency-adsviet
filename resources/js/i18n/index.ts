import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';

import en from '@/i18n/locales/en.json';
import vi from '@/i18n/locales/vi.json';
import zh from '@/i18n/locales/zh.json';

export enum _LanguageCode {
    VI = 'vi',
    EN = 'en',
    ZH = 'zh',
}

i18n.use(initReactI18next).init({
    resources: {
        vi: { translation: vi },
        en: { translation: en },
        zh: { translation: zh },
    },
    lng: 'vi',
    fallbackLng: 'en',
    interpolation: {
        escapeValue: false,
    },
});

export default i18n;
