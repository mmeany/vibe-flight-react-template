import {
  Extension as ExtensionIcon,
  Security as SecurityIcon,
  Speed as SpeedIcon,
} from '@mui/icons-material';
import {
  Box,
  Button,
  Card,
  CardContent,
  Container,
  Grid,
  Paper,
  Stack,
  Typography,
  useTheme,
} from '@mui/material';
import { Link as RouterLink } from 'react-router-dom';
import ContactForm from '../components/ContactForm';
import LegalFooter from '../components/LegalFooter';
import { trackEvent } from '../cookieConsent/analytics';
import { useAppConfig } from '../contexts/AppConfigContext';
import { CONTACT_FORM } from '../content/siteContent';
import {
  LANDING_CTA,
  LANDING_FEATURES,
  LANDING_FOOTER,
  LANDING_HERO,
  LANDING_STEPS,
} from '../landing/landingContent';
import { APP_VERSION } from '../version';

const FEATURE_ICONS = {
  speed: SpeedIcon,
  security: SecurityIcon,
  extension: ExtensionIcon,
};

function FeatureIcon({ iconKey }) {
  const Icon = FEATURE_ICONS[iconKey] ?? ExtensionIcon;
  return (
    <Box
      sx={{
        display: 'inline-flex',
        p: 1.5,
        borderRadius: 2,
        bgcolor: 'primary.main',
        color: 'primary.contrastText',
        mb: 2,
      }}
    >
      <Icon fontSize="medium" />
    </Box>
  );
}

export default function LandingPage() {
  const theme = useTheme();
  const { isRegistrationEnabled, isLoading: configLoading } = useAppConfig();
  const showRegister = !configLoading && isRegistrationEnabled;

  const authButtons = (
    <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2} justifyContent="center">
      <Button component={RouterLink} to="/login" variant="contained" size="large">
        {LANDING_HERO.primaryCtaLabel}
      </Button>
      {showRegister && (
        <Button component={RouterLink} to="/register" variant="outlined" size="large">
          {LANDING_HERO.secondaryCtaLabel}
        </Button>
      )}
    </Stack>
  );

  return (
    <Box component="main" sx={{ flexGrow: 1 }}>
      <Box
        sx={{
          py: { xs: 8, md: 12 },
          px: 2,
          background:
            theme.palette.mode === 'dark'
              ? `linear-gradient(180deg, ${theme.palette.primary.dark}22 0%, transparent 100%)`
              : `linear-gradient(180deg, ${theme.palette.primary.main}18 0%, transparent 100%)`,
        }}
      >
        <Container maxWidth="md" sx={{ textAlign: 'center' }}>
          <Typography variant="h3" component="h1" gutterBottom sx={{ fontWeight: 700 }}>
            {LANDING_HERO.headline}
          </Typography>
          <Typography variant="h6" color="text.secondary" sx={{ mb: 4, fontWeight: 400 }}>
            {LANDING_HERO.subheadline}
          </Typography>
          {authButtons}
        </Container>
      </Box>

      <Container maxWidth="lg" sx={{ py: { xs: 6, md: 8 }, px: { xs: 2, sm: 3 } }}>
        <Typography variant="h4" component="h2" align="center" gutterBottom>
          Why use this template
        </Typography>
        <Typography variant="body1" color="text.secondary" align="center" sx={{ mb: 5, maxWidth: 560, mx: 'auto' }}>
          Three placeholder feature cards — edit titles and descriptions in landingContent.js.
        </Typography>
        <Grid container spacing={3}>
          {LANDING_FEATURES.map(feature => (
            <Grid key={feature.title} size={{ xs: 12, md: 4 }}>
              <Card variant="outlined" sx={{ height: '100%' }}>
                <CardContent sx={{ p: 3 }}>
                  <FeatureIcon iconKey={feature.icon} />
                  <Typography variant="h6" component="h3" gutterBottom>
                    {feature.title}
                  </Typography>
                  <Typography variant="body2" color="text.secondary">
                    {feature.description}
                  </Typography>
                </CardContent>
              </Card>
            </Grid>
          ))}
        </Grid>
      </Container>

      <Box sx={{ bgcolor: 'action.hover', py: { xs: 6, md: 8 } }}>
        <Container maxWidth="lg" sx={{ px: { xs: 2, sm: 3 } }}>
          <Typography variant="h4" component="h2" align="center" gutterBottom>
            How it works
          </Typography>
          <Grid container spacing={4} sx={{ mt: 2 }}>
            {LANDING_STEPS.map((step, index) => (
              <Grid key={step.title} size={{ xs: 12, md: 4 }}>
                <Stack alignItems={{ xs: 'flex-start', md: 'center' }} textAlign={{ xs: 'left', md: 'center' }}>
                  <Box
                    sx={{
                      width: 40,
                      height: 40,
                      borderRadius: '50%',
                      bgcolor: 'primary.main',
                      color: 'primary.contrastText',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      fontWeight: 700,
                      mb: 2,
                    }}
                  >
                    {index + 1}
                  </Box>
                  <Typography variant="h6" component="h3" gutterBottom>
                    {step.title}
                  </Typography>
                  <Typography variant="body2" color="text.secondary">
                    {step.description}
                  </Typography>
                </Stack>
              </Grid>
            ))}
          </Grid>
        </Container>
      </Box>

      <Container
        id={CONTACT_FORM.sectionId}
        maxWidth="md"
        sx={{ py: { xs: 6, md: 8 }, px: { xs: 2, sm: 3 } }}
      >
        <ContactForm onSuccess={category => trackEvent('contact_form_submit', { category })} />
      </Container>

      <Container maxWidth="md" sx={{ py: { xs: 6, md: 8 }, px: { xs: 2, sm: 3 } }}>
        <Paper
          elevation={0}
          sx={{
            p: { xs: 3, sm: 5 },
            textAlign: 'center',
            bgcolor: 'primary.main',
            color: 'primary.contrastText',
          }}
        >
          <Typography variant="h5" component="h2" gutterBottom>
            {LANDING_CTA.title}
          </Typography>
          <Typography variant="body1" sx={{ mb: 3, opacity: 0.9 }}>
            {LANDING_CTA.body}
          </Typography>
          <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2} justifyContent="center">
            <Button
              component={RouterLink}
              to="/login"
              variant="contained"
              color="secondary"
              size="large"
            >
              {LANDING_HERO.primaryCtaLabel}
            </Button>
            {showRegister && (
              <Button
                component={RouterLink}
                to="/register"
                variant="outlined"
                size="large"
                sx={{
                  borderColor: 'primary.contrastText',
                  color: 'primary.contrastText',
                  '&:hover': { borderColor: 'primary.contrastText', bgcolor: 'rgba(255,255,255,0.08)' },
                }}
              >
                {LANDING_HERO.secondaryCtaLabel}
              </Button>
            )}
          </Stack>
        </Paper>
      </Container>

      <Box
        component="footer"
        sx={{
          py: 3,
          px: 2,
          textAlign: 'center',
          borderTop: 1,
          borderColor: 'divider',
        }}
      >
        <Typography variant="body2" color="text.secondary" sx={{ mb: 0.5 }}>
          {LANDING_FOOTER}
        </Typography>
        <Typography variant="caption" color="text.secondary" sx={{ mb: 2, display: 'block' }}>
          Version {APP_VERSION}
        </Typography>
        <LegalFooter />
      </Box>
    </Box>
  );
}
