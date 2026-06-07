import { useEffect } from 'react';
import { useLocation } from 'react-router-dom';
import { onAnalyticsReady, trackPageView } from '../cookieConsent/analytics';

export default function AnalyticsRouteTracker() {
  const location = useLocation();

  useEffect(() => {
    if (location.pathname.startsWith('/admin')) {
      return undefined;
    }

    const path = `${location.pathname}${location.search}`;
    const fire = () => trackPageView(path);
    fire();

    return onAnalyticsReady(fire);
  }, [location]);

  return null;
}
