import apiClient from './client';
import { ApiError } from './errors';

function downloadBlob(blob, filename) {
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  link.click();
  URL.revokeObjectURL(url);
}

function filenameFromContentDisposition(header) {
  if (!header) return null;
  const match = /filename="([^"]+)"/.exec(header);
  return match?.[1] ?? null;
}

async function parseBlobError(blob) {
  const text = await blob.text();
  try {
    const payload = JSON.parse(text);
    return payload?.error?.message ?? 'Export failed.';
  } catch {
    return 'Export failed.';
  }
}

export async function listSubmissions({
  page = 1,
  perPage = 25,
  includeIgnored = false,
  search = '',
  sort = 'created_at',
  order = 'desc',
  status = 'all',
} = {}) {
  const response = await apiClient.get('/admin/submissions', {
    params: {
      page,
      per_page: perPage,
      include_ignored: includeIgnored,
      search: search || undefined,
      sort,
      order,
      status,
    },
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

export async function exportSubmissionsCsv({
  includeIgnored = false,
  search = '',
  sort = 'created_at',
  order = 'desc',
  status = 'all',
} = {}) {
  try {
    const response = await apiClient.get('/admin/submissions/export', {
      params: {
        include_ignored: includeIgnored,
        search: search || undefined,
        sort,
        order,
        status,
      },
      responseType: 'blob',
    });
    const filename = filenameFromContentDisposition(response.headers['content-disposition'])
      ?? `submissions-${new Date().toISOString().slice(0, 10)}.csv`;
    downloadBlob(response.data, filename);
  } catch (error) {
    const blob = error.response?.data;
    if (blob instanceof Blob) {
      const message = await parseBlobError(blob);
      throw new ApiError(message, error.response?.status ?? 0, '');
    }
    throw error;
  }
}
