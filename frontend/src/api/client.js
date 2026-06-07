import axios from 'axios';
import { ApiError } from './errors';
import { TOKEN_KEY } from './storage';

const apiClient = axios.create({
  baseURL: `${import.meta.env.BASE_URL}api/v1`,
  headers: { 'Content-Type': 'application/json' },
});

apiClient.interceptors.request.use(config => {
  const token = localStorage.getItem(TOKEN_KEY);
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

apiClient.interceptors.response.use(
  response => response,
  error => {
    const payload = error.response?.data?.error;
    if (payload?.message) {
      return Promise.reject(
        new ApiError(payload.message, error.response?.status ?? 0, payload.code ?? ''),
      );
    }
    return Promise.reject(error);
  },
);

export default apiClient;
