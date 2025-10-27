import i18n from "i18next";
import { initReactI18next } from "react-i18next";

import vi from "@/i18n/locales/vi.json";

export enum _LanguageCode {
    VI = 'vi',
    EN = 'en',
}

i18n
    .use(initReactI18next)
    .init({
        resources: {
            vi: { translation: vi },
        },
        lng: "vi",
        fallbackLng: "en",
        interpolation: {
            escapeValue: false,
        },
    });
