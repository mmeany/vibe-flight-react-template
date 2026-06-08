import {
  Block as BlockIcon,
  Clear as ClearIcon,
  Download as DownloadIcon,
  Email as EmailIcon,
} from '@mui/icons-material';
import {
  Alert,
  Box,
  Button,
  Chip,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  FormControl,
  FormControlLabel,
  IconButton,
  InputAdornment,
  InputLabel,
  MenuItem,
  Paper,
  Select,
  Stack,
  Switch,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TablePagination,
  TableRow,
  TableSortLabel,
  TextField,
  Tooltip,
  Typography,
} from '@mui/material';
import { useCallback, useEffect, useState } from 'react';
import {
  exportSubmissionsCsv,
  listSubmissions,
  replyToSubmission,
  setSubmissionIgnored,
} from '../api/adminSubmissions';
import PageContainer from '../components/PageContainer';

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

function categoryLabel(payload) {
  const category = payload?.category ?? '';
  const labels = {
    general_enquiry: 'General enquiry',
    feature_request: 'Feature request',
    partnership: 'Partnership / collaboration',
    bug_report: 'Bug Report',
  };
  return labels[category] ?? category;
}

function SubmissionStatusChip({ submission }) {
  if (submission.ignored) {
    return <Chip label="Ignored" size="small" color="default" />;
  }
  if (submission.follow_up_sent_at) {
    return <Chip label="Replied" size="small" color="success" />;
  }
  return <Chip label="New" size="small" color="primary" />;
}

export default function AdminSubmissionsPage() {
  const [items, setItems] = useState([]);
  const [total, setTotal] = useState(0);
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(25);
  const [includeIgnored, setIncludeIgnored] = useState(false);
  const [searchInput, setSearchInput] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [sort, setSort] = useState('created_at');
  const [order, setOrder] = useState('desc');
  const [statusFilter, setStatusFilter] = useState('all');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [selected, setSelected] = useState(null);
  const [replyOpen, setReplyOpen] = useState(false);
  const [replyMessage, setReplyMessage] = useState('');
  const [actionError, setActionError] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [exporting, setExporting] = useState(false);

  useEffect(() => {
    const timer = setTimeout(() => {
      const term = searchInput.trim();
      const nextSearch = term.length >= 2 ? term : '';
      setDebouncedSearch(nextSearch);
      setPage(1);
    }, 300);
    return () => clearTimeout(timer);
  }, [searchInput]);

  const loadSubmissions = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const result = await listSubmissions({
        page,
        perPage,
        includeIgnored,
        search: debouncedSearch,
        sort,
        order,
        status: statusFilter,
      });
      setItems(result.items);
      setTotal(result.meta?.total ?? result.items.length);
    } catch (err) {
      setError(err.message ?? 'Failed to load submissions.');
    } finally {
      setLoading(false);
    }
  }, [page, perPage, includeIgnored, debouncedSearch, sort, order, statusFilter]);

  useEffect(() => {
    loadSubmissions();
  }, [loadSubmissions]);

  const handleSort = (column) => {
    if (sort === column) {
      setOrder(prev => (prev === 'asc' ? 'desc' : 'asc'));
    } else {
      setSort(column);
      setOrder(column === 'created_at' ? 'desc' : 'asc');
    }
    setPage(1);
  };

  const maybeRefetchAfterAction = async () => {
    if (statusFilter !== 'all') {
      await loadSubmissions();
    }
  };

  const handleToggleIgnored = async (submission) => {
    setActionError('');
    try {
      const updated = await setSubmissionIgnored(submission.id, !submission.ignored);
      setItems(prev => prev.map(item => (item.id === updated.id ? updated : item)));
      if (selected?.id === updated.id) {
        setSelected(updated);
      }
      await maybeRefetchAfterAction();
    } catch (err) {
      setActionError(err.message ?? 'Failed to update submission.');
    }
  };

  const handleExportCsv = async () => {
    setActionError('');
    setExporting(true);
    try {
      await exportSubmissionsCsv({
        includeIgnored,
        search: debouncedSearch,
        sort,
        order,
        status: statusFilter,
      });
    } catch (err) {
      setActionError(err.message ?? 'Failed to export submissions.');
    } finally {
      setExporting(false);
    }
  };

  const handleReply = async () => {
    if (!selected) return;
    setSubmitting(true);
    setActionError('');
    try {
      const updated = await replyToSubmission(selected.id, replyMessage);
      setItems(prev => prev.map(item => (item.id === updated.id ? updated : item)));
      setSelected(updated);
      setReplyOpen(false);
      setReplyMessage('');
      await maybeRefetchAfterAction();
    } catch (err) {
      setActionError(err.message ?? 'Failed to send reply.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <PageContainer>
      <Stack spacing={3}>
        <Box>
          <Typography variant="h4" component="h1" gutterBottom>
            Contact submissions
          </Typography>
          <Typography variant="body2" color="text.secondary">
            {total} submission{total === 1 ? '' : 's'}
          </Typography>
        </Box>

        <Stack
          direction={{ xs: 'column', sm: 'row' }}
          spacing={2}
          alignItems={{ xs: 'stretch', sm: 'center' }}
          flexWrap="wrap"
        >
          <TextField
            label="Search"
            placeholder="Email, known as, or message"
            value={searchInput}
            onChange={event => setSearchInput(event.target.value)}
            size="small"
            sx={{ minWidth: 220, flexGrow: 1 }}
            slotProps={{
              input: {
                endAdornment: searchInput ? (
                  <InputAdornment position="end">
                    <IconButton
                      aria-label="Clear search"
                      onClick={() => setSearchInput('')}
                      edge="end"
                      size="small"
                    >
                      <ClearIcon fontSize="small" />
                    </IconButton>
                  </InputAdornment>
                ) : null,
              },
            }}
          />
          <FormControl size="small" sx={{ minWidth: 140 }}>
            <InputLabel id="submission-status-filter-label">Status</InputLabel>
            <Select
              labelId="submission-status-filter-label"
              label="Status"
              value={statusFilter}
              onChange={event => {
                setStatusFilter(event.target.value);
                setPage(1);
              }}
            >
              <MenuItem value="all">All</MenuItem>
              <MenuItem value="new">New</MenuItem>
              <MenuItem value="replied">Replied</MenuItem>
            </Select>
          </FormControl>
          <FormControlLabel
            control={
              <Switch
                checked={includeIgnored}
                onChange={event => {
                  setIncludeIgnored(event.target.checked);
                  setPage(1);
                }}
              />
            }
            label="Show ignored"
          />
          <Button
            variant="outlined"
            size="small"
            startIcon={<DownloadIcon />}
            onClick={handleExportCsv}
            disabled={exporting || loading}
            sx={{ ml: { sm: 'auto' } }}
          >
            {exporting ? 'Exporting…' : 'Download CSV'}
          </Button>
        </Stack>

        {error && <Alert severity="error">{error}</Alert>}
        {actionError && <Alert severity="error">{actionError}</Alert>}

        <Paper variant="outlined">
          <TableContainer>
            <Table size="small">
              <TableHead>
                <TableRow>
                  <TableCell sortDirection={sort === 'email' ? order : false}>
                    <TableSortLabel
                      active={sort === 'email'}
                      direction={sort === 'email' ? order : 'asc'}
                      onClick={() => handleSort('email')}
                    >
                      Email
                    </TableSortLabel>
                  </TableCell>
                  <TableCell>Category</TableCell>
                  <TableCell sortDirection={sort === 'created_at' ? order : false}>
                    <TableSortLabel
                      active={sort === 'created_at'}
                      direction={sort === 'created_at' ? order : 'desc'}
                      onClick={() => handleSort('created_at')}
                    >
                      Received
                    </TableSortLabel>
                  </TableCell>
                  <TableCell sortDirection={sort === 'status' ? order : false}>
                    <TableSortLabel
                      active={sort === 'status'}
                      direction={sort === 'status' ? order : 'asc'}
                      onClick={() => handleSort('status')}
                    >
                      Status
                    </TableSortLabel>
                  </TableCell>
                  <TableCell align="right">Actions</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {loading && (
                  <TableRow>
                    <TableCell colSpan={5}>Loading…</TableCell>
                  </TableRow>
                )}
                {!loading && items.length === 0 && (
                  <TableRow>
                    <TableCell colSpan={5}>No submissions found.</TableCell>
                  </TableRow>
                )}
                {!loading && items.map(submission => (
                  <TableRow
                    key={submission.id}
                    hover
                    onClick={() => setSelected(submission)}
                    sx={{ cursor: 'pointer' }}
                    role="button"
                    tabIndex={0}
                    aria-label={`View submission from ${submission.email}`}
                    onKeyDown={(event) => {
                      if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        setSelected(submission);
                      }
                    }}
                  >
                    <TableCell>{submission.email}</TableCell>
                    <TableCell>{categoryLabel(submission.payload)}</TableCell>
                    <TableCell>{formatDate(submission.created_at)}</TableCell>
                    <TableCell>
                      <SubmissionStatusChip submission={submission} />
                    </TableCell>
                    <TableCell align="right" onClick={event => event.stopPropagation()}>
                      <Tooltip title={submission.ignored ? 'Unignore' : 'Mark ignored'}>
                        <IconButton
                          onClick={() => handleToggleIgnored(submission)}
                          size="small"
                          color="error"
                          aria-label={submission.ignored ? 'Unignore submission' : 'Mark submission ignored'}
                        >
                          <BlockIcon fontSize="small" />
                        </IconButton>
                      </Tooltip>
                      <Tooltip title="Send reply">
                        <IconButton
                          onClick={() => {
                            setSelected(submission);
                            setReplyMessage(submission.follow_up_response ?? '');
                            setReplyOpen(true);
                          }}
                          size="small"
                          aria-label="Send reply"
                        >
                          <EmailIcon fontSize="small" />
                        </IconButton>
                      </Tooltip>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </TableContainer>
          <TablePagination
            component="div"
            count={total}
            page={Math.max(0, page - 1)}
            onPageChange={(_, nextPage) => setPage(nextPage + 1)}
            rowsPerPage={perPage}
            onRowsPerPageChange={event => {
              setPerPage(parseInt(event.target.value, 10));
              setPage(1);
            }}
            rowsPerPageOptions={[10, 25, 50]}
          />
        </Paper>
      </Stack>

      <Dialog open={Boolean(selected) && !replyOpen} onClose={() => setSelected(null)} maxWidth="sm" fullWidth>
        <DialogTitle>Submission #{selected?.id}</DialogTitle>
        <DialogContent dividers>
          {selected && (
            <Stack spacing={1}>
              <Typography variant="body2"><strong>Email:</strong> {selected.email}</Typography>
              <Typography variant="body2"><strong>Known as:</strong> {selected.payload?.known_as}</Typography>
              <Typography variant="body2"><strong>Name:</strong> {selected.payload?.firstname} {selected.payload?.surname}</Typography>
              <Typography variant="body2"><strong>Category:</strong> {categoryLabel(selected.payload)}</Typography>
              <Typography variant="body2"><strong>Message:</strong> {selected.payload?.question || '—'}</Typography>
              <Typography variant="body2"><strong>Auto-ack sent:</strong> {formatDate(selected.auto_response_sent_at)}</Typography>
              <Typography variant="body2"><strong>Follow-up sent:</strong> {formatDate(selected.follow_up_sent_at)}</Typography>
              {selected.follow_up_response && (
                <Typography variant="body2"><strong>Last reply:</strong> {selected.follow_up_response}</Typography>
              )}
            </Stack>
          )}
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setSelected(null)}>Close</Button>
        </DialogActions>
      </Dialog>

      <Dialog open={replyOpen} onClose={() => setReplyOpen(false)} maxWidth="sm" fullWidth>
        <DialogTitle>Reply to {selected?.email}</DialogTitle>
        <DialogContent>
          <TextField
            label="Message"
            value={replyMessage}
            onChange={event => setReplyMessage(event.target.value)}
            multiline
            minRows={4}
            fullWidth
            sx={{ mt: 1 }}
          />
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setReplyOpen(false)}>Cancel</Button>
          <Button onClick={handleReply} variant="contained" disabled={submitting || !replyMessage.trim()}>
            {submitting ? 'Sending…' : 'Send reply'}
          </Button>
        </DialogActions>
      </Dialog>
    </PageContainer>
  );
}
