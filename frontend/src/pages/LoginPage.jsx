import { useState } from 'react';
import { Link as RouterLink, useNavigate } from 'react-router-dom';
import { Box, Button, TextField, Typography, Link, Alert } from '@mui/material';
import { useAuth } from '../contexts/AuthContext';
import { useAppConfig } from '../contexts/AppConfigContext';

export default function LoginPage() {
  const { login, isLoading } = useAuth();
  const { isRegistrationEnabled, isLoading: configLoading } = useAppConfig();
  const navigate = useNavigate();
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');

  async function handleSubmit(event) {
    event.preventDefault();
    setError('');
    try {
      await login(username, password);
      navigate('/dashboard');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Login failed');
    }
  }

  return (
    <>
      <Typography variant="h4" component="h1" gutterBottom>
        Sign In
      </Typography>
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
          label="Password"
          type="password"
          value={password}
          onChange={e => setPassword(e.target.value)}
          fullWidth
          margin="normal"
          required
          autoComplete="current-password"
        />
        <Button type="submit" variant="contained" fullWidth sx={{ mt: 2 }} disabled={isLoading}>
          Sign In
        </Button>
        {!configLoading && isRegistrationEnabled && (
          <Typography variant="body2" align="center" sx={{ mt: 2 }}>
            Don&apos;t have an account?{' '}
            <Link component={RouterLink} to="/register">
              Sign up
            </Link>
          </Typography>
        )}
      </Box>
    </>
  );
}