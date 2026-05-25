import { useState, useCallback, useMemo, useEffect } from 'react';
import {
  Typography, Paper, Switch, FormControlLabel, FormGroup, TextField, Alert, Divider, MenuItem, Autocomplete,
  Button,
} from '@mui/material';
import { changePassword } from '../api/me';
import { USER_KEY } from '../api/storage';
import { useThemeContext } from '../contexts/ThemeContext';
import { useAuth } from '../contexts/AuthContext';
import { getBrowserTimezone } from '../utils/date';
import { getTimezoneOptions } from '../utils/timezones';

export default function SettingsPage() {
  const { themeMode, toggleTheme, dateFormat, setDateFormat } = useThemeContext();
  const { user, updateSettings } = useAuth();

  const settings = user?.settings || {};
  const timezoneOptions = useMemo(() => getTimezoneOptions(), []);
  const [alias, setAlias] = useState(settings.user_alias || user?.username || '');
  const [timezone, setTimezone] = useState(settings.timezone || getBrowserTimezone());
  const [aliasError, setAliasError] = useState('');
  const [timezoneError, setTimezoneError] = useState('');
  const [saved, setSaved] = useState(false);
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [passwordReminder, setPasswordReminder] = useState(user?.password_reminder ?? 'No hint');
  const [passwordError, setPasswordError] = useState('');
  const [passwordSuccess, setPasswordSuccess] = useState(false);
  const [passwordSubmitting, setPasswordSubmitting] = useState(false);

  useEffect(() => {
    if (user?.password_reminder) {
      setPasswordReminder(user.password_reminder);
    }
  }, [user?.password_reminder]);

  useEffect(() => {
    if (settings.timezone) {
      setTimezone(settings.timezone);
    }
  }, [settings.timezone]);

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

  const handleTimezoneChange = useCallback(async (_event, value) => {
    if (!value) return;
    setTimezone(value);
    setTimezoneError('');
    try {
      await updateSettings('timezone', value);
      setSaved(true);
      setTimeout(() => setSaved(false), 3000);
    } catch {
      setTimezoneError('Failed to save timezone');
    }
  }, [updateSettings]);

  const displayName = settings.user_alias || user?.username || 'Unknown';

  const passwordsFilled = currentPassword && newPassword && confirmPassword && passwordReminder.trim();
  const passwordsMatch = newPassword === confirmPassword;
  const canSubmitPassword = passwordsFilled && passwordsMatch && !passwordSubmitting;

  const handlePasswordSubmit = useCallback(async (event) => {
    event.preventDefault();
    setPasswordError('');
    setPasswordSuccess(false);

    if (!passwordsMatch) {
      setPasswordError('New passwords do not match');
      return;
    }

    setPasswordSubmitting(true);
    try {
      await changePassword({
        current_password: currentPassword,
        new_password: newPassword,
        password_reminder: passwordReminder.trim(),
      });
      const storedUser = localStorage.getItem(USER_KEY);
      if (storedUser) {
        try {
          const parsed = JSON.parse(storedUser);
          parsed.password_reminder = passwordReminder.trim();
          localStorage.setItem(USER_KEY, JSON.stringify(parsed));
        } catch {
          // ignore corrupt storage
        }
      }
      setCurrentPassword('');
      setNewPassword('');
      setConfirmPassword('');
      setPasswordSuccess(true);
      setTimeout(() => setPasswordSuccess(false), 3000);
    } catch (err) {
      setPasswordError(err instanceof Error ? err.message : 'Failed to change password');
    } finally {
      setPasswordSubmitting(false);
    }
  }, [confirmPassword, currentPassword, newPassword, passwordReminder, passwordsMatch]);

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
        <Typography variant="h6" gutterBottom>Timezone</Typography>
        <Divider sx={{ mb: 2 }} />
        <Autocomplete
          options={timezoneOptions}
          value={timezone}
          onChange={handleTimezoneChange}
          renderInput={params => (
            <TextField
              {...params}
              label="Timezone"
              error={!!timezoneError}
              helperText={timezoneError || 'Used for date and time display in the app'}
            />
          )}
          fullWidth
          disableClearable
        />
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

      <Paper sx={{ p: 3, mb: 3 }} component="form" onSubmit={handlePasswordSubmit}>
        <Typography variant="h6" gutterBottom>Security</Typography>
        <Divider sx={{ mb: 2 }} />
        {passwordSuccess && (
          <Alert severity="success" sx={{ mb: 2 }}>
            Password updated successfully!
          </Alert>
        )}
        {passwordError && (
          <Alert severity="error" sx={{ mb: 2 }}>
            {passwordError}
          </Alert>
        )}
        <TextField
          label="Current Password"
          type="password"
          value={currentPassword}
          onChange={e => { setCurrentPassword(e.target.value); setPasswordError(''); }}
          fullWidth
          margin="normal"
          required
          autoComplete="current-password"
        />
        <TextField
          label="New Password"
          type="password"
          value={newPassword}
          onChange={e => { setNewPassword(e.target.value); setPasswordError(''); }}
          fullWidth
          margin="normal"
          required
          autoComplete="new-password"
          helperText="Min 8 chars with uppercase, lowercase, and number"
        />
        <TextField
          label="Confirm New Password"
          type="password"
          value={confirmPassword}
          onChange={e => { setConfirmPassword(e.target.value); setPasswordError(''); }}
          fullWidth
          margin="normal"
          required
          autoComplete="new-password"
          error={confirmPassword !== '' && !passwordsMatch}
          helperText={
            confirmPassword !== '' && !passwordsMatch ? 'Passwords do not match' : ''
          }
        />
        <TextField
          label="Password Reminder"
          value={passwordReminder}
          onChange={e => { setPasswordReminder(e.target.value); setPasswordError(''); }}
          fullWidth
          margin="normal"
          required
          autoComplete="off"
          helperText="Replace “No hint” with something only you would recognize"
          inputProps={{ maxLength: 255 }}
        />
        <Button
          type="submit"
          variant="contained"
          disabled={!canSubmitPassword}
          sx={{ mt: 1 }}
        >
          Change Password
        </Button>
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
