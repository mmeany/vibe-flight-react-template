import {
  Cancel,
  CheckCircle,
  Clear as ClearIcon,
  Delete,
  Edit,
  Restore,
} from '@mui/icons-material';
import {
  Alert,
  Box,
  Button,
  Checkbox,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  Divider,
  FormControlLabel,
  IconButton,
  InputAdornment,
  Paper,
  Stack,
  Tab,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TablePagination,
  TableRow,
  TableSortLabel,
  Tabs,
  TextField,
  Typography,
  useMediaQuery,
  useTheme,
} from '@mui/material';
import { useCallback, useEffect, useState } from 'react';
import {
  createUser,
  deactivateUser,
  importUsers,
  listUsers,
  restoreUser,
  updateUser,
} from '../api/adminUsers';

function CreateUserForm({ onCreated }) {
  const [username, setUsername] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [passwordReminder, setPasswordReminder] = useState('');
  const [userAlias, setUserAlias] = useState('');
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [submitting, setSubmitting] = useState(false);

  async function handleSubmit(event) {
    event.preventDefault();
    setError('');
    setSuccess('');
    setSubmitting(true);
    try {
      await createUser({
        username,
        email,
        password,
        password_reminder: passwordReminder.trim(),
        user_alias: userAlias || undefined,
      });
      setSuccess(`User "${username}" created.`);
      setUsername('');
      setEmail('');
      setPassword('');
      setPasswordReminder('');
      setUserAlias('');
      onCreated();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to create user');
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <Paper sx={{ p: 3 }} component="form" onSubmit={handleSubmit}>
      <Typography variant="h6" gutterBottom>Create user</Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      {success && <Alert severity="success" sx={{ mb: 2 }}>{success}</Alert>}
      <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2, maxWidth: 400 }}>
        <TextField label="Username" value={username} onChange={e => setUsername(e.target.value)} required />
        <TextField label="Email" type="email" value={email} onChange={e => setEmail(e.target.value)} required />
        <TextField
          label="Password"
          type="password"
          value={password}
          onChange={e => setPassword(e.target.value)}
          required
          helperText="Min 8 chars with uppercase, lowercase, and number"
        />
        <TextField
          label="Password reminder"
          value={passwordReminder}
          onChange={e => setPasswordReminder(e.target.value)}
          required
          inputProps={{ maxLength: 255 }}
          helperText="A personal hint to help the user remember their password"
        />
        <TextField label="User alias (optional)" value={userAlias} onChange={e => setUserAlias(e.target.value)} />
        <Button type="submit" variant="contained" disabled={submitting}>
          {submitting ? 'Creating...' : 'Create user'}
        </Button>
      </Box>
    </Paper>
  );
}

function CsvImportPanel({ onImported }) {
  const [file, setFile] = useState(null);
  const [error, setError] = useState('');
  const [result, setResult] = useState(null);
  const [submitting, setSubmitting] = useState(false);

  async function handleImport(event) {
    event.preventDefault();
    if (!file) {
      setError('Select a CSV file first');
      return;
    }
    setError('');
    setResult(null);
    setSubmitting(true);
    try {
      const data = await importUsers(file);
      setResult(data);
      onImported();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Import failed');
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <Paper sx={{ p: 3 }} component="form" onSubmit={handleImport}>
      <Typography variant="h6" gutterBottom>Import CSV</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
        Required columns: username, email, password, password_reminder. Optional: user_alias.
      </Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
        The CSV file MUST have a header row:
      </Typography>
      <Typography
        component="code"
        variant="body2"
        sx={{
          display: 'block',
          fontFamily: 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace',
          fontSize: '0.8125rem',
          color: 'text.primary',
          bgcolor: theme => (theme.palette.mode === 'dark' ? 'grey.800' : 'grey.100'),
          border: 1,
          borderColor: theme => (theme.palette.mode === 'dark' ? 'grey.700' : 'grey.300'),
          borderRadius: 1,
          px: 1.5,
          py: 1,
          mb: 2,
          overflowX: 'auto',
        }}
      >
        username,email,password,password_reminder,user_alias
      </Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
        Use 'CHOOSE CSV' to select a CSV file, then 'IMPORT' to import it.
      </Typography>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      {result?.summary && (
        <Alert severity="info" sx={{ mb: 2 }}>
          Created {result.summary.created}, failed {result.summary.failed}
        </Alert>
      )}
      <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2, maxWidth: 400 }}>
        <Button variant="outlined" component="label">
          Choose CSV
          <input type="file" accept=".csv,text/csv" hidden onChange={e => setFile(e.target.files?.[0] ?? null)} />
        </Button>
        {file && <Typography variant="body2">{file.name}</Typography>}
        <Button type="submit" variant="contained" disabled={submitting || !file}>
          {submitting ? 'Importing...' : 'import'}
        </Button>
      </Box>
      {result?.rows?.length > 0 && (
        <Table size="small" sx={{ mt: 3 }}>
          <TableHead>
            <TableRow>
              <TableCell>Line</TableCell>
              <TableCell>Username</TableCell>
              <TableCell>Status</TableCell>
              <TableCell>Detail</TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {result.rows.map((row, index) => (
              <TableRow key={`${row.line}-${index}`}>
                <TableCell>{row.line}</TableCell>
                <TableCell>{row.username}</TableCell>
                <TableCell>{row.status}</TableCell>
                <TableCell>{row.message || row.id || ''}</TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      )}
    </Paper>
  );
}

function EditUserDialog({ user, open, onClose, onSaved }) {
  const [username, setUsername] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [passwordReminder, setPasswordReminder] = useState('');
  const [userAlias, setUserAlias] = useState('');
  const [error, setError] = useState('');
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    if (user) {
      setUsername(user.username);
      setEmail(user.email);
      setPassword('');
      setPasswordReminder(user.password_reminder ?? 'No hint');
      setUserAlias(user.settings?.user_alias || user.username);
      setError('');
    }
  }, [user]);

  async function handleSave() {
    setError('');
    setSubmitting(true);
    try {
      await updateUser(user.id, {
        username,
        email,
        password: password || undefined,
        password_reminder: passwordReminder.trim(),
        user_alias: userAlias,
      });
      onSaved();
      onClose();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to update user');
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <Dialog open={open} onClose={onClose} maxWidth="sm" fullWidth>
      <DialogTitle>Edit user</DialogTitle>
      <DialogContent>
        {error && <Alert severity="error" sx={{ mb: 2, mt: 1 }}>{error}</Alert>}
        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2, pt: 1 }}>
          <TextField label="Username" value={username} onChange={e => setUsername(e.target.value)} fullWidth />
          <TextField label="Email" type="email" value={email} onChange={e => setEmail(e.target.value)} fullWidth />
          <TextField
            label="New password (leave blank to keep)"
            type="password"
            value={password}
            onChange={e => setPassword(e.target.value)}
            fullWidth
          />
          <TextField
            label="Password reminder"
            value={passwordReminder}
            onChange={e => setPasswordReminder(e.target.value)}
            fullWidth
            required
            inputProps={{ maxLength: 255 }}
          />
          <TextField label="User alias" value={userAlias} onChange={e => setUserAlias(e.target.value)} fullWidth />
        </Box>
      </DialogContent>
      <DialogActions>
        <Button onClick={onClose}>Cancel</Button>
        <Button onClick={handleSave} variant="contained" disabled={submitting}>
          Save
        </Button>
      </DialogActions>
    </Dialog>
  );
}

function UsersTable() {
  const theme = useTheme();
  const isSmallScreen = useMediaQuery(theme.breakpoints.down('sm'));
  const [users, setUsers] = useState([]);
  const [total, setTotal] = useState(0);
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(25);
  const [searchInput, setSearchInput] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [sort, setSort] = useState('username');
  const [order, setOrder] = useState('asc');
  const [includeInactive, setIncludeInactive] = useState(false);
  const [error, setError] = useState('');
  const [editUser, setEditUser] = useState(null);
  const [loading, setLoading] = useState(true);

  const compactCellSx = isSmallScreen ? { py: 0.5, fontSize: '0.8125rem' } : undefined;

  useEffect(() => {
    const timer = setTimeout(() => {
      const term = searchInput.trim();
      const nextSearch = term.length >= 2 ? term : '';
      setDebouncedSearch(nextSearch);
      setPage(1);
    }, 300);
    return () => clearTimeout(timer);
  }, [searchInput]);

  const loadUsers = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const result = await listUsers({
        page,
        perPage,
        includeInactive,
        search: debouncedSearch,
        sort,
        order,
      });
      setUsers(result.items);
      setTotal(result.meta?.total ?? result.items.length);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load users');
    } finally {
      setLoading(false);
    }
  }, [page, perPage, includeInactive, debouncedSearch, sort, order]);

  useEffect(() => {
    loadUsers();
  }, [loadUsers]);

  const handleSort = (column) => {
    if (sort === column) {
      setOrder(prev => (prev === 'asc' ? 'desc' : 'asc'));
    } else {
      setSort(column);
      setOrder('asc');
    }
    setPage(1);
  };

  async function handleDeactivate(user) {
    if (!window.confirm(`Deactivate user "${user.username}"?`)) {
      return;
    }
    try {
      await deactivateUser(user.id);
      loadUsers();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to deactivate user');
    }
  }

  async function handleRestore(user) {
    try {
      await restoreUser(user.id);
      loadUsers();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to restore user');
    }
  }

  return (
    <Paper sx={{ p: { xs: 1.5, sm: 3 } }}>
      <Box sx={{ mb: { xs: 1, sm: 2 } }}>
        <Typography variant="h6" gutterBottom>Manage users</Typography>
        <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
          {total} user{total === 1 ? '' : 's'}
        </Typography>
        <Stack
          direction={{ xs: 'column', sm: 'row' }}
          spacing={2}
          alignItems={{ xs: 'stretch', sm: 'center' }}
          flexWrap="wrap"
        >
          <TextField
            label="Search"
            placeholder="Username, email, or alias"
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
          <FormControlLabel
            control={
              <Checkbox
                checked={includeInactive}
                onChange={e => {
                  setIncludeInactive(e.target.checked);
                  setPage(1);
                }}
              />
            }
            label="Show inactive"
          />
        </Stack>
      </Box>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      <TableContainer sx={{ overflowX: 'auto' }}>
        <Table size="small">
          <TableHead>
            <TableRow>
              <TableCell sx={compactCellSx}>ID</TableCell>
              <TableCell sx={compactCellSx} sortDirection={sort === 'username' ? order : false}>
                <TableSortLabel
                  active={sort === 'username'}
                  direction={sort === 'username' ? order : 'asc'}
                  onClick={() => handleSort('username')}
                >
                  Username
                </TableSortLabel>
              </TableCell>
              {!isSmallScreen && (
                <TableCell sx={compactCellSx} sortDirection={sort === 'email' ? order : false}>
                  <TableSortLabel
                    active={sort === 'email'}
                    direction={sort === 'email' ? order : 'asc'}
                    onClick={() => handleSort('email')}
                  >
                    Email
                  </TableSortLabel>
                </TableCell>
              )}
              <TableCell sx={compactCellSx} sortDirection={sort === 'user_alias' ? order : false}>
                <TableSortLabel
                  active={sort === 'user_alias'}
                  direction={sort === 'user_alias' ? order : 'asc'}
                  onClick={() => handleSort('user_alias')}
                >
                  Alias
                </TableSortLabel>
              </TableCell>
              <TableCell sx={compactCellSx} align="center">Status</TableCell>
              <TableCell sx={compactCellSx} align="right">
                {!isSmallScreen && 'Actions'}
              </TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {loading && (
              <TableRow>
                <TableCell colSpan={isSmallScreen ? 5 : 6}>Loading…</TableCell>
              </TableRow>
            )}
            {!loading && users.length === 0 && (
              <TableRow>
                <TableCell colSpan={isSmallScreen ? 5 : 6}>No users found.</TableCell>
              </TableRow>
            )}
            {!loading && users.map(user => (
              <TableRow key={user.id}>
                <TableCell sx={compactCellSx}>{user.id}</TableCell>
                <TableCell sx={compactCellSx}>{user.username}</TableCell>
                {!isSmallScreen && <TableCell sx={compactCellSx}>{user.email}</TableCell>}
                <TableCell sx={compactCellSx}>{user.settings?.user_alias || '—'}</TableCell>
                <TableCell sx={compactCellSx} align="center">
                  <Box
                    component="span"
                    role="img"
                    aria-label={user.is_active ? 'Active' : 'Inactive'}
                    sx={{ display: 'inline-flex', verticalAlign: 'middle' }}
                  >
                    {user.is_active ? (
                      <CheckCircle color="success" fontSize="small" />
                    ) : (
                      <Cancel color="error" fontSize="small" />
                    )}
                  </Box>
                </TableCell>
                <TableCell align="right" sx={{ ...compactCellSx, whiteSpace: 'nowrap' }}>
                  {user.is_active ? (
                    <>
                      <IconButton
                        size="small"
                        aria-label={`Edit ${user.username}`}
                        onClick={() => setEditUser(user)}
                      >
                        <Edit fontSize="small" />
                      </IconButton>
                      <IconButton
                        size="small"
                        color="error"
                        aria-label={`Deactivate ${user.username}`}
                        onClick={() => handleDeactivate(user)}
                      >
                        <Delete fontSize="small" />
                      </IconButton>
                    </>
                  ) : (
                    <IconButton
                      size="small"
                      color="primary"
                      aria-label={`Restore ${user.username}`}
                      onClick={() => handleRestore(user)}
                    >
                      <Restore fontSize="small" />
                    </IconButton>
                  )}
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
      <EditUserDialog
        user={editUser}
        open={Boolean(editUser)}
        onClose={() => setEditUser(null)}
        onSaved={loadUsers}
      />
    </Paper>
  );
}

export default function AdminUsersPage() {
  const [tab, setTab] = useState(0);
  const [refreshKey, setRefreshKey] = useState(0);

  const bumpRefresh = () => setRefreshKey(k => k + 1);

  return (
    <>
      <Typography variant="h4" component="h1" gutterBottom>
        User management
      </Typography>

      <Tabs value={tab} onChange={(_, v) => setTab(v)} sx={{ mb: 2 }}>
        <Tab label="Create" />
        <Tab label="Import CSV" />
        <Tab label="Manage" />
      </Tabs>

      {tab === 0 && <CreateUserForm onCreated={bumpRefresh} />}
      {tab === 1 && <CsvImportPanel onImported={bumpRefresh} />}
      {tab === 2 && <UsersTable key={refreshKey} />}

      <Divider sx={{ my: 3 }} />
      <Typography variant="caption" color="text.secondary">
        Public registration can be disabled; admin create and CSV import always work for allowlisted accounts.
        CSV import requires columns: username, email, password, password_reminder (optional: user_alias).
      </Typography>
    </>
  );
}
