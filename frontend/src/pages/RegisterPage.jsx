import { useState } from 'react';
import { Link as RouterLink, useNavigate } from 'react-router-dom';
import { Box, Button, TextField, Typography, Link, Alert, CircularProgress } from '@mui/material';
import { useAuth } from '../contexts/AuthContext';
import { useAppConfig } from '../contexts/AppConfigContext';

export default function RegisterPage() {
  const { register, isLoading } = useAuth();
  const { isRegistrationEnabled, isLoading: configLoading } = useAppConfig();
  const navigate = useNavigate();
  const [username, setUsername] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [passwordReminder, setPasswordReminder] = useState('');
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  async function handleSubmit(event) {
    event.preventDefault();
    setError('');
    setSuccess('');
    try {
      await register(username, email, password, passwordReminder);
      setSuccess('Account created! Redirecting to login...');
      setTimeout(() => navigate('/login'), 1500);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Registration failed');
    }
  }

  if (configLoading) {
    return (
      <Box sx={{ display: 'flex', justifyContent: 'center', py: 4 }}>
        <CircularProgress />
      </Box>
    );
  }

  if (!isRegistrationEnabled) {
    return (
      <>
        <Typography variant="h4" component="h1" gutterBottom>
          Sign Up
        </Typography>
        <Alert severity="info">
          New user registration has been temporarily disabled.
        </Alert>
        <Typography variant="body2" align="center" sx={{ mt: 3 }}>
          Already have an account?{' '}
          <Link component={RouterLink} to="/login">Sign in</Link>
        </Typography>
      </>
    );
  }

  return (
    <>
      <Typography variant="h4" component="h1" gutterBottom>
        Sign Up
      </Typography>
      {success && (
        <Alert severity="success" sx={{ mb: 2 }}>
          {success}
        </Alert>
      )}
      {error && (
        <Alert severity="error" sx={{ mb: 2 }}>
          {error}
        </Alert>
      )}
      <Box component="form" onSubmit={handleSubmit}>
        <TextField
          label="Username"
          value={username}
          onChange={e => setUsername(e.target.value)}
          fullWidth
          margin="normal"
          required
          autoComplete="username"
        />
        <TextField
          label="Email"
          type="email"
          value={email}
          onChange={e => setEmail(e.target.value)}
          fullWidth
          margin="normal"
          required
          autoComplete="email"
        />
        <TextField
          label="Password"
          type="password"
          value={password}
          onChange={e => setPassword(e.target.value)}
          fullWidth
          margin="normal"
          required
          autoComplete="new-password"
          helperText="Min 8 chars with uppercase, lowercase, and number"
        />
        <TextField
          label="Password Reminder"
          value={passwordReminder}
          onChange={e => setPasswordReminder(e.target.value)}
          fullWidth
          margin="normal"
          autoComplete="off"
        />
        <Button type="submit" variant="contained" fullWidth sx={{ mt: 2 }} disabled={isLoading}>
          Sign Up
        </Button>
        <Typography variant="body2" align="center" sx={{ mt: 2 }}>
          Already have an account?{' '}
          <Link component={RouterLink} to="/login">Sign in</Link>
        </Typography>
      </Box>
    </>
  );
}