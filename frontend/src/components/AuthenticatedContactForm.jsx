import {
  Alert,
  Box,
  Button,
  MenuItem,
  Stack,
  TextField,
  Typography,
} from '@mui/material';
import { useState } from 'react';
import { submitAuthenticatedContact } from '../api/contact';
import { AUTHENTICATED_CONTACT_CATEGORIES, AUTHENTICATED_CONTACT_FORM } from '../content/siteContent';
import { useAuth } from '../contexts/AuthContext';

const INITIAL_FORM = {
  category: '',
  question: '',
};

export default function AuthenticatedContactForm() {
  const { user } = useAuth();
  const [form, setForm] = useState(INITIAL_FORM);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  const displayName = user?.settings?.user_alias || user?.username || 'User';
  const email = user?.email ?? '';

  const handleChange = (field) => (event) => {
    setForm(prev => ({ ...prev, [field]: event.target.value }));
  };

  const handleSubmit = async (event) => {
    event.preventDefault();

    setSubmitting(true);
    setError('');
    setSuccess('');

    try {
      await submitAuthenticatedContact({
        category: form.category,
        question: form.question,
      });
      setSuccess(AUTHENTICATED_CONTACT_FORM.successMessage);
      setForm(INITIAL_FORM);
    } catch (err) {
      setError(err.message ?? 'Unable to submit your message.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <Box component="form" onSubmit={handleSubmit} noValidate>
      <Typography variant="h4" component="h2" gutterBottom>
        {AUTHENTICATED_CONTACT_FORM.heading}
      </Typography>
      <Typography variant="body1" color="text.secondary" sx={{ mb: 3 }}>
        {AUTHENTICATED_CONTACT_FORM.subheading}
      </Typography>

      <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
        Submitting as {displayName} ({email})
      </Typography>

      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      {success && <Alert severity="success" sx={{ mb: 2 }}>{success}</Alert>}

      <Stack spacing={2}>
        <TextField
          select
          label={AUTHENTICATED_CONTACT_FORM.fields.category.label}
          value={form.category}
          onChange={handleChange('category')}
          required
          fullWidth
        >
          {AUTHENTICATED_CONTACT_CATEGORIES.map(option => (
            <MenuItem key={option.value} value={option.value}>
              {option.label}
            </MenuItem>
          ))}
        </TextField>

        <TextField
          label={AUTHENTICATED_CONTACT_FORM.fields.question.label}
          value={form.question}
          onChange={handleChange('question')}
          multiline
          minRows={3}
          inputProps={{ maxLength: AUTHENTICATED_CONTACT_FORM.fields.question.maxLength }}
          helperText={`${form.question.length}/${AUTHENTICATED_CONTACT_FORM.fields.question.maxLength}`}
          fullWidth
        />

        <Button
          type="submit"
          variant="contained"
          size="large"
          disabled={submitting}
        >
          {submitting ? 'Sending…' : AUTHENTICATED_CONTACT_FORM.submitLabel}
        </Button>
      </Stack>
    </Box>
  );
}
