export const APP_NAME = "ZETRA";
export const APP_FULL_NAME =
  "ZETRA — Zakat Ecosystem for Transparency, Reporting & Accountability";
export const APP_TAGLINE =
  "Zakat Ecosystem for Transparency, Reporting & Accountability";
export const APP_SHORT_TAGLINE = "Transparent Zakat Management";
export const APP_TECHNICAL_TAGLINE = "Open Source Zakat Management Platform";
export const APP_DESCRIPTION =
  "Transparent and accountable zakat management platform.";
export const APP_LONG_DESCRIPTION =
  "ZETRA is an open-source zakat management platform designed to support transparent, accountable, secure, and traceable zakat collection, fund management, assessment, distribution, accounting, reporting, and public transparency.";

export const formatPageTitle = (pageTitle?: string) =>
  pageTitle ? `${pageTitle} | ${APP_NAME}` : APP_FULL_NAME;
