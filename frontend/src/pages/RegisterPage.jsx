import { useCallback, useEffect, useState } from 'react';
import { Link as RouterLink, useNavigate } from 'react-router-dom';
import { Box, Button, TextField, Typography, Link, Alert, CircularProgress } from '@mui/material';
import { resendVerification } from '../api/auth';
import { fetchChallenge } from '../api/contact';
import { ApiError } from '../api/errors';
import { useAuth } from '../contexts/AuthContext';
import { useAppConfig } from '../contexts/AppConfigContext';
import { CONTACT_FORM } from '../content/siteContent';

const RESEND_COOLDOWN_SECONDS = 60;

export default function RegisterPage() {
  const { startRegistration, completeRegistration } = useAuth();
  const { isRegistrationEnabled, isLoading: configLoading } = useAppConfig();
  const navigate = useNavigate();
  const [step, setStep] = useState(1);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [username, setUsername] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [passwordReminder, setPasswordReminder] = useState('');
  const [challengeAnswer, setChallengeAnswer] = useState('');
  const [website, setWebsite] = useState('');
  const [challenge, setChallenge] = useState(null);
  const [loadingChallenge, setLoadingChallenge] = useState(true);
  const [pendingToken, setPendingToken] = useState('');
  const [code, setCode] = useState('');
  const [error, setError] = useState('');
  const [resendCooldown, setResendCooldown] = useState(0);
  const [resendLoading, setResendLoading] = useState(false);

  const loadChallenge = useCallback(async () => {
    setLoadingChallenge(true);
    try {
      const data = await fetchChallenge();
      setChallenge(data);
    } catch (err) {
      setChallenge(null);
      setError(err instanceof Error ? err.message : 'Unable to load security check.');
    } finally {
      setLoadingChallenge(false);
    }
  }, []);

  useEffect(() => {
    loadChallenge();
  }, [loadChallenge]);

  useEffect(() => {
    if (resendCooldown <= 0) {
      return undefined;
    }
    const timer = setInterval(() => {
      setResendCooldown(prev => (prev <= 1 ? 0 : prev - 1));
    }, 1000);
    return () => clearInterval(timer);
  }, [resendCooldown]);

  function resetToStepOne(message) {
    setStep(1);
    setPendingToken('');
    setCode('');
    setResendCooldown(0);
    if (message) {
      setError(message);
    }
  }

  async function handleCredentialsSubmit(event) {
    event.preventDefault();
    if (!challenge) return;

    setError('');
    setIsSubmitting(true);
    try {
      const result = await startRegistration({
        username,
        email,
        password,
        passwordReminder,
        challengeToken: challenge.token,
        challengeAnswer,
        formLoadedAt: challenge.form_loaded_at,
        website,
      });
      setPendingToken(result.pending_token);
      setStep(2);
      setResendCooldown(RESEND_COOLDOWN_SECONDS);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Registration failed');
      await loadChallenge();
      setChallengeAnswer('');
    } finally {
      setIsSubmitting(false);
    }
  }

  async function handleVerifySubmit(event) {
    event.preventDefault();
    setError('');
    setIsSubmitting(true);
    try {
      await completeRegistration(pendingToken, code);
      navigate('/dashboard');
    } catch (err) {
      if (err instanceof ApiError && err.statusCode === 410) {
        resetToStepOne(err.message);
        return;
      }
      setError(err instanceof Error ? err.message : 'Verification failed');
    } finally {
      setIsSubmitting(false);
    }
  }

  async function handleResend() {
    if (resendCooldown > 0 || !pendingToken) {
      return;
    }
    setError('');
    setResendLoading(true);
    try {
      await resendVerification(pendingToken);
      setResendCooldown(RESEND_COOLDOWN_SECONDS);
      setCode('');
    } catch (err) {
      if (err instanceof ApiError && err.statusCode === 410) {
        resetToStepOne(err.message);
        return;
      }
      setError(err instanceof Error ? err.message : 'Could not resend code');
    } finally {
      setResendLoading(false);
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

  if (step === 2) {
    return (
      <>
        <Typography variant="h4" component="h1" gutterBottom>
          Verify your email
        </Typography>
        <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
          We sent a 6-digit code to {email}. Enter it below to complete registration.
        </Typography>
        {error && (
          <Alert severity="error" sx={{ mb: 2 }}>
            {error}
          </Alert>
        )}
        <Box component="form" onSubmit={handleVerifySubmit}>
          <TextField
            label="Verification code"
            value={code}
            onChange={e => setCode(e.target.value.replace(/\D/g, '').slice(0, 6))}
            fullWidth
            margin="normal"
            required
            inputMode="numeric"
            autoComplete="one-time-code"
            placeholder="000000"
            helperText="Code expires in 15 minutes"
          />
          <Button type="submit" variant="contained" fullWidth sx={{ mt: 2 }} disabled={isSubmitting}>
            Verify and sign in
          </Button>
          <Button
            type="button"
            variant="text"
            fullWidth
            sx={{ mt: 1 }}
            disabled={resendCooldown > 0 || resendLoading || isSubmitting}
            onClick={handleResend}
          >
            {resendCooldown > 0
              ? `Resend code (${resendCooldown}s)`
              : 'Resend code'}
          </Button>
          <Typography variant="body2" align="center" sx={{ mt: 2 }}>
            <Link
              component="button"
              type="button"
              onClick={() => {
                setError('');
                setStep(1);
              }}
            >
              Back to edit details
            </Link>
          </Typography>
        </Box>
      </>
    );
  }

  return (
    <>
      <Typography variant="h4" component="h1" gutterBottom>
        Sign Up
      </Typography>
      {error && (
        <Alert severity="error" sx={{ mb: 2 }}>
          {error}
        </Alert>
      )}
      <Box component="form" onSubmit={handleCredentialsSubmit}>
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
        <TextField
          label={challenge?.question ?? CONTACT_FORM.fields.challenge.label}
          value={challengeAnswer}
          onChange={e => setChallengeAnswer(e.target.value)}
          fullWidth
          margin="normal"
          required
          disabled={loadingChallenge || !challenge}
        />
        <Box sx={{ display: 'none' }} aria-hidden="true">
          <TextField
            tabIndex={-1}
            autoComplete="off"
            label="Website"
            value={website}
            onChange={e => setWebsite(e.target.value)}
          />
        </Box>
        <Button
          type="submit"
          variant="contained"
          fullWidth
          sx={{ mt: 2 }}
          disabled={isSubmitting || loadingChallenge || !challenge}
        >
          Continue
        </Button>
        <Typography variant="body2" align="center" sx={{ mt: 2 }}>
          Already have an account?{' '}
          <Link component={RouterLink} to="/login">Sign in</Link>
        </Typography>
      </Box>
    </>
  );
}
