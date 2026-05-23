const DEFAULT_META = {
  name: 'Flight React App',
};

let cachedMeta = null;

export async function fetchProjectMeta() {
  if (cachedMeta) {
    return cachedMeta;
  }

  const base = import.meta.env.BASE_URL;
  const url = `${base}${base.endsWith('/') ? '' : '/'}project.json`;

  try {
    const response = await fetch(url);
    if (!response.ok) {
      cachedMeta = DEFAULT_META;
      return cachedMeta;
    }
    const data = await response.json();
    cachedMeta = {
      name: typeof data.name === 'string' && data.name.trim() !== ''
        ? data.name.trim()
        : DEFAULT_META.name,
    };
  } catch {
    cachedMeta = DEFAULT_META;
  }

  return cachedMeta;
}
