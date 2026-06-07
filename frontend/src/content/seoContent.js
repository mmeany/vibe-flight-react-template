const siteUrl = import.meta.env.VITE_SITE_URL || '';

export const seoContent = {
  terms: {
    title: 'Terms & Conditions',
    description: 'Terms and conditions for using this application.',
    canonical: `${siteUrl}/terms`,
  },
  privacy: {
    title: 'Privacy Policy',
    description: 'How we collect, use, and protect your personal data.',
    canonical: `${siteUrl}/privacy`,
  },
  landing: {
    title: 'Flight React App',
    description: 'A modern full-stack template with PHP Flight API and React frontend.',
    canonical: siteUrl || '/',
  },
};
