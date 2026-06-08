export const CONTACT_FORM = {
  sectionId: 'contact',
  heading: 'Contact Us',
  subheading: 'Have a question or idea? Send us a message.',
  submitLabel: 'Send message',
  successMessage: 'Thank you — your message has been received.',
  fields: {
    firstname: { label: 'First name', required: true },
    surname: { label: 'Surname', required: true },
    email: { label: 'Email address', required: true },
    knownAs: { label: 'Known as', required: true },
    category: { label: 'What is this about?', required: true },
    question: { label: 'Your message', required: false, maxLength: 250 },
    challenge: { label: 'Security check', required: true },
  },
};

export const AUTHENTICATED_CONTACT_FORM = {
  heading: 'Contact Us',
  subheading: 'Send a message to the team. Your account details will be attached automatically.',
  submitLabel: 'Send message',
  successMessage: 'Thank you — your message has been received.',
  fields: {
    category: { label: 'What is this about?', required: true },
    question: { label: 'Your message', required: false, maxLength: 250 },
  },
};

export const CONTACT_CATEGORIES = [
  { value: 'general_enquiry', label: 'General enquiry' },
  { value: 'feature_request', label: 'Feature request' },
  { value: 'partnership', label: 'Partnership / collaboration' },
];

export const AUTHENTICATED_CONTACT_CATEGORIES = [
  { value: 'general_enquiry', label: 'General enquiry' },
  { value: 'bug_report', label: 'Bug Report' },
  { value: 'feature_request', label: 'Feature request' },
  { value: 'partnership', label: 'Partnership / collaboration' },
];
