import { useTheme } from '@mui/material';
import { useEffect } from 'react';
import { useLocation } from 'react-router-dom';
import '../cookieConsent/cookieConsent.css';
import 'vanilla-cookieconsent/dist/cookieconsent.css';
import { hideCookieConsent, initCookieConsent, showCookieConsent } from '../cookieConsent/config';
import { syncCookieConsentTheme } from '../cookieConsent/themeSync';

export default function CookieConsentManager() {
  const theme = useTheme();
  const location = useLocation();
  const isAdminRoute = location.pathname.startsWith('/admin');

  useEffect(() => {
    initCookieConsent();
  }, []);

  useEffect(() => {
    syncCookieConsentTheme(theme);
  }, [theme]);

  useEffect(() => {
    if (isAdminRoute) {
      hideCookieConsent();
    } else {
      showCookieConsent();
    }
  }, [isAdminRoute]);

  return null;
}
