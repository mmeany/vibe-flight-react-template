import { Divider, Paper, Typography } from '@mui/material';
import {
  ABOUT_APP_TITLE,
  ABOUT_CREDITS,
  ABOUT_DESCRIPTION,
} from '../about/aboutContent';
import { APP_VERSION } from '../version';

export default function AboutPage() {
  return (
    <>
      <Typography variant="h4" component="h1" gutterBottom>
        About
      </Typography>

      <Paper sx={{ p: { xs: 2, sm: 3 } }}>
        <Typography variant="h5" component="h2" gutterBottom>
          {ABOUT_APP_TITLE}
        </Typography>
        <Typography variant="body1" color="text.secondary" paragraph>
          {ABOUT_DESCRIPTION}
        </Typography>

        <Divider sx={{ my: 2 }} />

        <Typography variant="subtitle2" color="text.secondary" gutterBottom>
          Version
        </Typography>
        <Typography variant="body1" sx={{ mb: 2 }}>
          {APP_VERSION}
        </Typography>

        <Divider sx={{ my: 2 }} />

        <Typography variant="subtitle2" color="text.secondary" gutterBottom>
          Credits
        </Typography>
        <Typography variant="body1">
          {ABOUT_CREDITS}
        </Typography>
      </Paper>
    </>
  );
}
