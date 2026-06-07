import apiClient from './client';

export async function listSubmissions({ page = 1, perPage = 25, includeIgnored = false } = {}) {
  const response = await apiClient.get('/admin/submissions', {
    params: { page, per_page: perPage, include_ignored: includeIgnored },
  });
  return {
    items: response.data.data,
    meta: response.data.meta,
  };
}

export async function setSubmissionIgnored(id, ignored) {
  const response = await apiClient.patch(`/admin/submissions/${id}/ignore`, { ignored });
  return response.data.data;
}

export async function replyToSubmission(id, message) {
  const response = await apiClient.post(`/admin/submissions/${id}/reply`, { message });
  return response.data.data;
}
