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
  Paper,
  Tab,
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableRow,
  Tabs,
  TextField,
  Typography,
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
        user_alias: userAlias || undefined,
      });
      setSuccess(`User "${username}" created.`);
      setUsername('');
      setEmail('');
      setPassword('');
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
        <TextField label="Password" type="password" value={password} onChange={e => setPassword(e.target.value)} required />
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
        Required columns: username, email, password. Optional: user_alias.
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
        username,email,password,user_alias
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
  const [userAlias, setUserAlias] = useState('');
  const [error, setError] = useState('');
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    if (user) {
      setUsername(user.username);
      setEmail(user.email);
      setPassword('');
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
  const [users, setUsers] = useState([]);
  const [includeInactive, setIncludeInactive] = useState(false);
  const [error, setError] = useState('');
  const [editUser, setEditUser] = useState(null);
  const [loading, setLoading] = useState(true);

  const loadUsers = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const data = await listUsers(includeInactive);
      setUsers(data);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load users');
    } finally {
      setLoading(false);
    }
  }, [includeInactive]);

  useEffect(() => {
    loadUsers();
  }, [loadUsers]);

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
    <Paper sx={{ p: 3 }}>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
        <Typography variant="h6">Manage users</Typography>
        <FormControlLabel
          control={
            <Checkbox
              checked={includeInactive}
              onChange={e => setIncludeInactive(e.target.checked)}
            />
          }
          label="Show inactive"
        />
      </Box>
      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      {loading ? (
        <Typography color="text.secondary">Loading...</Typography>
      ) : (
        <Table size="small">
          <TableHead>
            <TableRow>
              <TableCell>ID</TableCell>
              <TableCell>Username</TableCell>
              <TableCell>Email</TableCell>
              <TableCell>Alias</TableCell>
              <TableCell>Status</TableCell>
              <TableCell align="right">Actions</TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {users.map(user => (
              <TableRow key={user.id}>
                <TableCell>{user.id}</TableCell>
                <TableCell>{user.username}</TableCell>
                <TableCell>{user.email}</TableCell>
                <TableCell>{user.settings?.user_alias || '—'}</TableCell>
                <TableCell>{user.is_active ? 'Active' : 'Inactive'}</TableCell>
                <TableCell align="right">
                  {user.is_active ? (
                    <>
                      <Button size="small" onClick={() => setEditUser(user)}>Edit</Button>
                      <Button size="small" color="error" onClick={() => handleDeactivate(user)}>
                        Deactivate
                      </Button>
                    </>
                  ) : (
                    <Button size="small" color="primary" onClick={() => handleRestore(user)}>
                      Restore
                    </Button>
                  )}
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      )}
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
        Public registration can be disabled; admin create always works for allowlisted accounts.
      </Typography>
    </>
  );
}
