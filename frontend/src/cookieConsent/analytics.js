const listeners = new Set();
let ready = false;
let gtagLoaded = false;

function getMeasurementId() {
  if (!import.meta.env.PROD) {
    return null;
  }
  const id = import.meta.env.VITE_GA_MEASUREMENT_ID;
  return id && id.trim() !== '' ? id.trim() : null;
}

function notifyReady() {
  ready = true;
  listeners.forEach(fn => fn());
}

export function isAnalyticsReady() {
  return ready && typeof window.gtag === 'function';
}

export function onAnalyticsReady(listener) {
  listeners.add(listener);
  if (isAnalyticsReady()) {
    listener();
  }
  return () => listeners.delete(listener);
}

function loadGtag(measurementId) {
  if (gtagLoaded || typeof document === 'undefined') {
    return;
  }

  window.dataLayer = window.dataLayer || [];
  window.gtag = function gtag() {
    window.dataLayer.push(arguments);
  };

  window.gtag('consent', 'default', {
    analytics_storage: 'denied',
    ad_storage: 'denied',
  });

  const script = document.createElement('script');
  script.async = true;
  script.src = `https://www.googletagmanager.com/gtag/js?id=${measurementId}`;
  document.head.appendChild(script);

  window.gtag('js', new Date());
  window.gtag('config', measurementId, { send_page_view: false });
  gtagLoaded = true;
  notifyReady();
}

export function initAnalyticsIfConsented() {
  const measurementId = getMeasurementId();
  if (!measurementId) {
    return;
  }

  loadGtag(measurementId);
  window.gtag('consent', 'update', { analytics_storage: 'granted' });
}

export function revokeAnalytics() {
  if (typeof window.gtag === 'function') {
    window.gtag('consent', 'update', { analytics_storage: 'denied' });
  }
}

export function trackPageView(path) {
  if (!isAnalyticsReady()) {
    return;
  }
  const measurementId = getMeasurementId();
  if (!measurementId) {
    return;
  }
  window.gtag('event', 'page_view', {
    page_path: path,
    page_location: window.location.href,
    send_to: measurementId,
  });
}

export function trackEvent(name, params = {}) {
  if (!isAnalyticsReady()) {
    return;
  }
  window.gtag('event', name, params);
}
