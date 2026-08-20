export const APP_NAME = "ZETRA";
export const APP_FULL_NAME =
  "ZETRA — Kelola Zakat dengan Amanah";
export const APP_TAGLINE =
  "Kelola zakat dengan amanah, rapi, dan transparan";
export const APP_SHORT_TAGLINE = "Pengelolaan zakat yang transparan";
export const APP_TECHNICAL_TAGLINE = "Platform pengelolaan zakat terbuka";
export const APP_DESCRIPTION =
  "Platform pengelolaan zakat yang amanah, rapi, dan transparan.";
export const APP_LONG_DESCRIPTION =
  "ZETRA membantu lembaga zakat mencatat penerimaan, mengelola dana, menilai kebutuhan, menyalurkan bantuan, dan membuat laporan yang mudah dipertanggungjawabkan.";

export const formatPageTitle = (pageTitle?: string) =>
  pageTitle ? `${pageTitle} | ${APP_NAME}` : APP_FULL_NAME;
