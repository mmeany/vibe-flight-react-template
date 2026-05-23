import apiClient from './client';

export async function fetchPublicConfig() {
  const response = await apiClient.get('/config');
  return response.data.data;
}
