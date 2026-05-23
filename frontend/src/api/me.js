import apiClient from './client';

export async function getMe() {
  const response = await apiClient.get('/me');
  return response.data.data;
}

export async function updateSettings(key, value) {
  const response = await apiClient.patch('/settings', { [key]: value });
  return response.data.data;
}