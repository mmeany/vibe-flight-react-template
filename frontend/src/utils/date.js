/**
 * IANA timezone the browser reports (e.g. "Europe/Dublin").
 */
export function getBrowserTimezone() {
  return Intl.DateTimeFormat().resolvedOptions().timeZone;
}
