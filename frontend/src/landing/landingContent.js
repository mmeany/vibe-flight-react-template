/** Customize marketing copy for your app — keep LANDING_APP_NAME in sync with Header / About if you change it. */
export const LANDING_APP_NAME = 'Flight React App';

export const LANDING_HERO = {
  headline: 'Build faster with a modern full-stack template',
  subheadline:
    'PHP Flight API and React frontend with JWT auth, user settings, and Material UI. '
    + 'Replace this copy when you fork the template.',
  primaryCtaLabel: 'Sign in',
  secondaryCtaLabel: 'Create account',
};

export const LANDING_FEATURES = [
  {
    icon: 'speed',
    title: 'Fast to ship',
    description:
      'Monorepo layout, Vite dev server, and production build script. Swap this for your product’s main value proposition.',
  },
  {
    icon: 'security',
    title: 'Auth built in',
    description:
      'JWT login, optional registration, and per-user settings stored server-side. Describe your security or compliance story here.',
  },
  {
    icon: 'extension',
    title: 'Easy to extend',
    description:
      'Controller–service–repository backend and React pages you own. List integrations, modules, or workflows you plan to add.',
  },
];

export const LANDING_STEPS = [
  {
    title: 'Configure your stack',
    description: 'Set environment variables, database, and branding in this repo.',
  },
  {
    title: 'Replace placeholder content',
    description: 'Edit landing/landingContent.js and about/aboutContent.js for your product.',
  },
  {
    title: 'Ship your features',
    description: 'Add API routes and React pages behind the existing auth shell.',
  },
];

export const LANDING_CTA = {
  title: 'Ready to get started?',
  body: 'Sign in to open the dashboard, or register when public signup is enabled.',
};

export const LANDING_FOOTER =
  'Template landing page — replace sections in frontend/src/landing/landingContent.js.';
