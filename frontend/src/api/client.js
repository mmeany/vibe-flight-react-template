import axios from 'axios';
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
    const message = error.response?.data?.error?.message;
    if (message) {
      return Promise.reject(new Error(message));
    }
    return Promise.reject(error);
  },
);

export default apiClient;
