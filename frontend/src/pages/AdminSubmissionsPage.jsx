import {
  Email as EmailIcon,
  MarkEmailRead as MarkEmailReadIcon,
  Visibility as VisibilityIcon,
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
  FormControlLabel,
  IconButton,
  Paper,
  Stack,
  Switch,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  TextField,
  Tooltip,
  Typography,
} from '@mui/material';
import { useEffect, useState } from 'react';
import {
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
  };
  return labels[category] ?? category;
}

export default function AdminSubmissionsPage() {
  const [items, setItems] = useState([]);
  const [total, setTotal] = useState(0);
  const [includeIgnored, setIncludeIgnored] = useState(false);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [selected, setSelected] = useState(null);
  const [replyOpen, setReplyOpen] = useState(false);
  const [replyMessage, setReplyMessage] = useState('');
  const [actionError, setActionError] = useState('');
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    let active = true;
    (async () => {
      setLoading(true);
      setError('');
      try {
        const result = await listSubmissions({ includeIgnored });
        if (active) {
          setItems(result.items);
          setTotal(result.meta?.total ?? result.items.length);
        }
      } catch (err) {
        if (active) {
          setError(err.message ?? 'Failed to load submissions.');
        }
      } finally {
        if (active) {
          setLoading(false);
        }
      }
    })();
    return () => {
      active = false;
    };
  }, [includeIgnored]);

  const handleToggleIgnored = async (submission) => {
    setActionError('');
    try {
      const updated = await setSubmissionIgnored(submission.id, !submission.ignored);
      setItems(prev => prev.map(item => (item.id === updated.id ? updated : item)));
      if (selected?.id === updated.id) {
        setSelected(updated);
      }
    } catch (err) {
      setActionError(err.message ?? 'Failed to update submission.');
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

        <FormControlLabel
          control={
            <Switch
              checked={includeIgnored}
              onChange={event => setIncludeIgnored(event.target.checked)}
            />
          }
          label="Show ignored"
        />

        {error && <Alert severity="error">{error}</Alert>}
        {actionError && <Alert severity="error">{actionError}</Alert>}

        <TableContainer component={Paper} variant="outlined">
          <Table size="small">
            <TableHead>
              <TableRow>
                <TableCell>Email</TableCell>
                <TableCell>Category</TableCell>
                <TableCell>Received</TableCell>
                <TableCell>Status</TableCell>
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
                <TableRow key={submission.id} hover>
                  <TableCell>{submission.email}</TableCell>
                  <TableCell>{categoryLabel(submission.payload)}</TableCell>
                  <TableCell>{formatDate(submission.created_at)}</TableCell>
                  <TableCell>
                    {submission.ignored ? (
                      <Chip label="Ignored" size="small" color="default" />
                    ) : (
                      <Chip label="New" size="small" color="primary" />
                    )}
                  </TableCell>
                  <TableCell align="right">
                    <Tooltip title="View details">
                      <IconButton onClick={() => setSelected(submission)} size="small">
                        <VisibilityIcon fontSize="small" />
                      </IconButton>
                    </Tooltip>
                    <Tooltip title={submission.ignored ? 'Unignore' : 'Mark ignored'}>
                      <IconButton onClick={() => handleToggleIgnored(submission)} size="small">
                        <MarkEmailReadIcon fontSize="small" />
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
