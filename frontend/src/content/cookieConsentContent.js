export const cookieConsentContent = {
  consentModal: {
    title: 'We use cookies',
    description:
      'We use necessary cookies to run this site. Analytics cookies help us understand how visitors use the site — only if you opt in.',
    acceptAllBtn: 'Accept all',
    acceptNecessaryBtn: 'Reject non-essential',
    showPreferencesBtn: 'Manage preferences',
  },
  preferencesModal: {
    title: 'Cookie preferences',
    acceptAllBtn: 'Accept all',
    acceptNecessaryBtn: 'Reject non-essential',
    savePreferencesBtn: 'Save preferences',
    closeIconLabel: 'Close',
    sections: [
      {
        title: 'Cookie usage',
        description: 'You can choose which optional cookies we may set.',
      },
      {
        title: 'Strictly necessary',
        description: 'Required for the site to function, including consent storage.',
        linkedCategory: 'necessary',
      },
      {
        title: 'Analytics',
        description: 'Google Analytics 4 — page views and anonymous usage events if you opt in.',
        linkedCategory: 'analytics',
      },
      {
        title: 'More information',
        description: 'See our Privacy Policy for full details.',
      },
    ],
  },
  cookieTable: {
    necessary: [
      {
        name: 'app_consent',
        purpose: 'Stores your cookie consent choices',
        duration: '1 year',
      },
    ],
    analytics: [
      { name: '_ga', purpose: 'Google Analytics — distinguishes users', duration: '2 years' },
      { name: '_gid', purpose: 'Google Analytics — distinguishes users', duration: '24 hours' },
    ],
  },
};
