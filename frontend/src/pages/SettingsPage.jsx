import { useState, useCallback } from 'react';
import { Typography, Paper, Switch, FormControlLabel, FormGroup, TextField, Alert, Divider, MenuItem } from '@mui/material';
import { useThemeContext } from '../contexts/ThemeContext';
import { useAuth } from '../contexts/AuthContext';

export default function SettingsPage() {
  const { themeMode, toggleTheme, dateFormat, setDateFormat } = useThemeContext();
  const { user, updateSettings } = useAuth();

  const settings = user?.settings || {};
  const [alias, setAlias] = useState(settings.user_alias || user?.username || '');
  const [aliasError, setAliasError] = useState('');
  const [saved, setSaved] = useState(false);

  const handleAliasBlur = useCallback(async () => {
    const trimmed = alias.trim();
    if (trimmed === '') {
      setAliasError('Alias cannot be empty');
      return;
    }
    if (!/^[a-zA-Z0-9]+$/.test(trimmed)) {
      setAliasError('Alias must be alphanumeric');
      return;
    }
    if (trimmed.length > 40) {
      setAliasError('Alias must be 40 characters or fewer');
      return;
    }
    setAliasError('');
    try {
      await updateSettings('user_alias', trimmed);
      setAlias(trimmed);
      setSaved(true);
      setTimeout(() => setSaved(false), 3000);
    } catch {
      setAliasError('Failed to save alias');
    }
  }, [alias, updateSettings]);

  const displayName = settings.user_alias || user?.username || 'Unknown';

  return (
    <>
      <Typography variant="h4" component="h1" gutterBottom>
        Settings
      </Typography>

      {saved && (
        <Alert severity="success" sx={{ mb: 2 }}>
          Settings saved successfully!
        </Alert>
      )}

      <Paper sx={{ p: 3, mb: 3 }}>
        <Typography variant="h6" gutterBottom>Appearance</Typography>
        <Divider sx={{ mb: 2 }} />
        <FormGroup>
          <FormControlLabel
            control={<Switch checked={themeMode === 'dark'} onChange={toggleTheme} color="primary" />}
            label={`Dark Mode (${themeMode === 'dark' ? 'On' : 'Off'})`}
          />
        </FormGroup>
      </Paper>

      <Paper sx={{ p: 3, mb: 3 }}>
        <Typography variant="h6" gutterBottom>Date Format</Typography>
        <Divider sx={{ mb: 2 }} />
        <TextField
          select
          label="Date Format"
          value={dateFormat}
          onChange={e => setDateFormat(e.target.value)}
          fullWidth
          sx={{ mb: 2 }}
        >
          <MenuItem value="MM/DD/YYYY">MM/DD/YYYY</MenuItem>
          <MenuItem value="DD/MM/YYYY">DD/MM/YYYY</MenuItem>
          <MenuItem value="YYYY-MM-DD">YYYY-MM-DD</MenuItem>
        </TextField>
      </Paper>

      <Paper sx={{ p: 3, mb: 3 }}>
        <Typography variant="h6" gutterBottom>Display Alias</Typography>
        <Divider sx={{ mb: 2 }} />
        <TextField
          label="Display Alias"
          value={alias}
          onChange={e => { setAlias(e.target.value); setAliasError(''); }}
          onBlur={handleAliasBlur}
          error={!!aliasError}
          helperText={aliasError || `Shown as "${displayName}" throughout the app`}
          fullWidth
          inputProps={{ maxLength: 40 }}
        />
      </Paper>

      <Paper sx={{ p: 3 }}>
        <Typography variant="h6" gutterBottom>Account Information</Typography>
        <Divider sx={{ mb: 2 }} />
        <Typography variant="body1"><strong>Alias:</strong> {displayName}</Typography>
        <Typography variant="body1"><strong>Username:</strong> {user?.username}</Typography>
        <Typography variant="body1"><strong>Email:</strong> {user?.email}</Typography>
      </Paper>
    </>
  );
}