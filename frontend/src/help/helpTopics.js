export const DEFAULT_HELP_TOPIC_ID = 'getting-started';

const RAW_TOPICS = [
  {
    id: 'getting-started',
    title: 'Getting started',
    sections: [
      {
        type: 'paragraph',
        text: 'Flight React App is a template for authenticated web applications. The public home page is at / (marketing landing when signed out). After login, the Dashboard at /dashboard is your home screen. Use Settings to personalize appearance, dates, timezone, and account security.',
      },
      {
        type: 'heading',
        text: 'Opening the menu',
      },
      {
        type: 'list',
        items: [
          'On a phone or narrow screen, tap the menu icon (☰) at the top left to open the navigation drawer.',
          'On a wider screen, tap your avatar at the top right for the same links in a dropdown menu.',
        ],
      },
      {
        type: 'heading',
        text: 'Main areas of the app',
      },
      {
        type: 'list',
        items: [
          'Dashboard — app home after sign-in (/dashboard).',
          'Settings — theme, date format, display alias, timezone, and password change.',
          'Help — these instructions.',
          'About — app description and version.',
        ],
      },
      {
        type: 'heading',
        text: 'Suggested first-time setup',
      },
      {
        type: 'list',
        items: [
          'Open Settings and confirm your timezone.',
          'Set a display alias if you want a friendly name in the header.',
          'Update your password reminder under Security if you still have the default “No hint”.',
        ],
      },
      {
        type: 'paragraph',
        text: 'If your account is an administrator, you will also see Users in the menu for managing accounts. See the User management (administrators) topic.',
      },
    ],
  },
  {
    id: 'settings',
    title: 'Settings',
    sections: [
      {
        type: 'paragraph',
        text: 'Settings controls how the app looks and how your account is secured. Changes to theme and date format apply immediately in the browser.',
      },
      {
        type: 'heading',
        text: 'Appearance and dates',
      },
      {
        type: 'list',
        items: [
          'Dark Mode — toggles light/dark theme.',
          'Timezone — pick your IANA timezone; used for date and time display.',
          'Date Format — choose how dates are shown (MM/DD/YYYY, DD/MM/YYYY, or YYYY-MM-DD).',
          'Display Alias — alphanumeric name shown in the header (max 40 characters). Saves when you leave the field.',
        ],
      },
      {
        type: 'heading',
        text: 'Security',
      },
      {
        type: 'list',
        items: [
          'Enter your current password, a new password, and confirmation to change your password.',
          'Password Reminder — a personal hint stored with your account (not your actual password). Required when changing password.',
          'New passwords must be at least 8 characters with uppercase, lowercase, and a number.',
        ],
      },
    ],
  },
  {
    id: 'account',
    title: 'Login and registration',
    sections: [
      {
        type: 'paragraph',
        text: 'Use Login with your username and password. If public registration is enabled for this deployment, you can create an account from the Register page.',
      },
      {
        type: 'heading',
        text: 'Registration fields',
      },
      {
        type: 'list',
        items: [
          'Username and email must be unique.',
          'Password must meet complexity rules shown on the form.',
          'Password Reminder — required hint to help you remember your password later.',
        ],
      },
      {
        type: 'paragraph',
        text: 'After registering, sign in with your new credentials. Administrators can also create accounts for you from the Users screen.',
      },
    ],
  },
  {
    id: 'admin-users',
    title: 'User management (administrators)',
    sections: [
      {
        type: 'paragraph',
        text: 'Administrators manage accounts from Users in the navigation menu. You cannot deactivate your own account while logged in.',
      },
      {
        type: 'heading',
        text: 'Create tab',
      },
      {
        type: 'list',
        items: [
          'Create a single user with username, email, password, password reminder, and optional display alias.',
        ],
      },
      {
        type: 'heading',
        text: 'Import CSV tab',
      },
      {
        type: 'list',
        items: [
          'CSV must include a header row.',
          'Required columns: username, email, password, password_reminder.',
          'Optional column: user_alias.',
          'Each data row creates one user; errors are reported per line.',
        ],
      },
      {
        type: 'heading',
        text: 'Manage tab',
      },
      {
        type: 'list',
        items: [
          'Edit username, email, optional new password, password reminder, and alias.',
          'Deactivate users to soft-delete them; restore inactive users when needed.',
          'Enable “Show inactive users” to see deactivated accounts.',
        ],
      },
    ],
  },
];

export const HELP_TOPICS = RAW_TOPICS.map((topic, index) => ({ ...topic, index }));

export function getHelpTopic(topicId) {
  if (!topicId) {
    return HELP_TOPICS[0] ?? null;
  }
  return HELP_TOPICS.find(t => t.id === topicId) ?? null;
}
