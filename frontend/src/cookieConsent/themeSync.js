export function syncCookieConsentTheme(theme) {
  const root = document.documentElement;
  const palette = theme.palette;

  root.style.setProperty('--cc-bg', palette.background.paper);
  root.style.setProperty('--cc-text', palette.text.primary);
  root.style.setProperty('--cc-btn-primary-bg', palette.primary.main);
  root.style.setProperty('--cc-btn-primary-text', palette.primary.contrastText);
  root.style.setProperty('--cc-btn-secondary-bg', palette.action.hover);
  root.style.setProperty('--cc-btn-secondary-text', palette.text.primary);
  root.style.setProperty('--cc-separator-border-color', palette.divider);
}
