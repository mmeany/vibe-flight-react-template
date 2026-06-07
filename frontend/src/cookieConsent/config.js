import * as CookieConsent from 'vanilla-cookieconsent';
import { cookieConsentContent } from '../content/cookieConsentContent';
import { initAnalyticsIfConsented, revokeAnalytics } from './analytics';

export const CONSENT_REVISION = 1;
export const CONSENT_COOKIE_NAME = 'app_consent';

export function getCookieConsentConfig() {
  return {
    mode: 'opt-in',
    revision: CONSENT_REVISION,
    cookie: {
      name: CONSENT_COOKIE_NAME,
    },
    guiOptions: {
      consentModal: {
        layout: 'box',
        position: 'bottom right',
      },
      preferencesModal: {
        layout: 'box',
      },
    },
    categories: {
      necessary: {
        enabled: true,
        readOnly: true,
      },
      analytics: {
        autoClear: {
          cookies: [
            { name: /^_ga/ },
            { name: '_gid' },
            { name: '_gat' },
          ],
        },
      },
    },
    language: {
      default: 'en',
      translations: {
        en: {
          consentModal: cookieConsentContent.consentModal,
          preferencesModal: cookieConsentContent.preferencesModal,
        },
      },
    },
    onConsent: ({ cookie }) => {
      if (cookie.categories.includes('analytics')) {
        initAnalyticsIfConsented();
      }
    },
    onChange: ({ cookie, changedCategories }) => {
      if (changedCategories.includes('analytics')) {
        if (cookie.categories.includes('analytics')) {
          initAnalyticsIfConsented();
        } else {
          revokeAnalytics();
        }
      }
    },
  };
}

export function initCookieConsent() {
  CookieConsent.run(getCookieConsentConfig());
}

export function showPreferences() {
  CookieConsent.showPreferences();
}

export function hideCookieConsent() {
  CookieConsent.hide();
}

export function showCookieConsent() {
  CookieConsent.show();
}
