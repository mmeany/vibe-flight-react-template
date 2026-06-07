import { Box, Container, Typography } from '@mui/material';
import LegalFooter from '../components/LegalFooter';
import SeoHead from '../components/SeoHead';

export default function LegalPage({ title, lastUpdated, sections, seo }) {
  return (
    <>
      <SeoHead title={seo.title} description={seo.description} canonical={seo.canonical} />
      <Container maxWidth="md" sx={{ py: { xs: 4, md: 6 }, px: { xs: 2, sm: 3 } }}>
        <Typography variant="h3" component="h1" gutterBottom>
          {title}
        </Typography>
        {lastUpdated && (
          <Typography variant="body2" color="text.secondary" sx={{ mb: 4 }}>
            Last updated: {lastUpdated}
          </Typography>
        )}
        {sections.map(section => (
          <Box key={section.heading} sx={{ mb: 4 }}>
            <Typography variant="h5" component="h2" gutterBottom>
              {section.heading}
            </Typography>
            <Typography variant="body1" color="text.secondary">
              {section.body}
            </Typography>
          </Box>
        ))}
        <LegalFooter />
      </Container>
    </>
  );
}
