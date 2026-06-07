const API_BASE = `${import.meta.env.BASE_URL}api/v1`;

async function parseResponse(response) {
  const payload = await response.json();
  if (!response.ok) {
    const message = payload?.error?.message ?? 'Request failed';
    throw new Error(message);
  }
  return payload.data;
}

export async function fetchChallenge() {
  const response = await fetch(`${API_BASE}/challenge`);
  return parseResponse(response);
}

export async function submitContact(body) {
  const response = await fetch(`${API_BASE}/contact`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });
  return parseResponse(response);
}
