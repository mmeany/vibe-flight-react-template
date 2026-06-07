import {
  Alert,
  Box,
  Button,
  MenuItem,
  Stack,
  TextField,
  Typography,
} from '@mui/material';
import { useCallback, useEffect, useState } from 'react';
import { fetchChallenge, submitContact } from '../api/contact';
import { CONTACT_CATEGORIES, CONTACT_FORM } from '../content/siteContent';

const INITIAL_FORM = {
  firstname: '',
  surname: '',
  email: '',
  known_as: '',
  category: '',
  question: '',
  challenge_answer: '',
  _website: '',
};

export default function ContactForm({ onSuccess }) {
  const [form, setForm] = useState(INITIAL_FORM);
  const [challenge, setChallenge] = useState(null);
  const [loadingChallenge, setLoadingChallenge] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  const loadChallenge = useCallback(async () => {
    setLoadingChallenge(true);
    setError('');
    try {
      const data = await fetchChallenge();
      setChallenge(data);
    } catch (err) {
      setError(err.message ?? 'Unable to load security check.');
      setChallenge(null);
    } finally {
      setLoadingChallenge(false);
    }
  }, []);

  useEffect(() => {
    let active = true;
    (async () => {
      setLoadingChallenge(true);
      setError('');
      try {
        const data = await fetchChallenge();
        if (active) {
          setChallenge(data);
        }
      } catch (err) {
        if (active) {
          setError(err.message ?? 'Unable to load security check.');
          setChallenge(null);
        }
      } finally {
        if (active) {
          setLoadingChallenge(false);
        }
      }
    })();
    return () => {
      active = false;
    };
  }, []);

  const handleChange = (field) => (event) => {
    setForm(prev => ({ ...prev, [field]: event.target.value }));
  };

  const handleSubmit = async (event) => {
    event.preventDefault();
    if (!challenge) return;

    setSubmitting(true);
    setError('');
    setSuccess('');

    try {
      await submitContact({
        ...form,
        challenge_token: challenge.token,
        form_loaded_at: challenge.form_loaded_at,
      });
      setSuccess(CONTACT_FORM.successMessage);
      setForm(INITIAL_FORM);
      onSuccess?.(form.category);
      await loadChallenge();
    } catch (err) {
      setError(err.message ?? 'Unable to submit your message.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <Box component="form" onSubmit={handleSubmit} noValidate>
      <Typography variant="h4" component="h2" gutterBottom>
        {CONTACT_FORM.heading}
      </Typography>
      <Typography variant="body1" color="text.secondary" sx={{ mb: 3 }}>
        {CONTACT_FORM.subheading}
      </Typography>

      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      {success && <Alert severity="success" sx={{ mb: 2 }}>{success}</Alert>}

      <Stack spacing={2}>
        <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2}>
          <TextField
            label={CONTACT_FORM.fields.firstname.label}
            value={form.firstname}
            onChange={handleChange('firstname')}
            required
            fullWidth
          />
          <TextField
            label={CONTACT_FORM.fields.surname.label}
            value={form.surname}
            onChange={handleChange('surname')}
            required
            fullWidth
          />
        </Stack>

        <TextField
          label={CONTACT_FORM.fields.email.label}
          type="email"
          value={form.email}
          onChange={handleChange('email')}
          required
          fullWidth
        />

        <TextField
          label={CONTACT_FORM.fields.knownAs.label}
          value={form.known_as}
          onChange={handleChange('known_as')}
          required
          fullWidth
        />

        <TextField
          select
          label={CONTACT_FORM.fields.category.label}
          value={form.category}
          onChange={handleChange('category')}
          required
          fullWidth
        >
          {CONTACT_CATEGORIES.map(option => (
            <MenuItem key={option.value} value={option.value}>
              {option.label}
            </MenuItem>
          ))}
        </TextField>

        <TextField
          label={CONTACT_FORM.fields.question.label}
          value={form.question}
          onChange={handleChange('question')}
          multiline
          minRows={3}
          inputProps={{ maxLength: CONTACT_FORM.fields.question.maxLength }}
          helperText={`${form.question.length}/${CONTACT_FORM.fields.question.maxLength}`}
          fullWidth
        />

        <TextField
          label={challenge?.question ?? CONTACT_FORM.fields.challenge.label}
          value={form.challenge_answer}
          onChange={handleChange('challenge_answer')}
          required
          fullWidth
          disabled={loadingChallenge || !challenge}
        />

        <Box sx={{ display: 'none' }} aria-hidden="true">
          <TextField
            tabIndex={-1}
            autoComplete="off"
            label="Website"
            value={form._website}
            onChange={handleChange('_website')}
          />
        </Box>

        <Button
          type="submit"
          variant="contained"
          size="large"
          disabled={submitting || loadingChallenge || !challenge}
        >
          {submitting ? 'Sending…' : CONTACT_FORM.submitLabel}
        </Button>
      </Stack>
    </Box>
  );
}
