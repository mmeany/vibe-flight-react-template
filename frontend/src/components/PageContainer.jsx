import { Box, useTheme } from '@mui/material';

export default function PageContainer({ children, fullPage = false }) {
  const theme = useTheme();
  return (
    <Box
      sx={{
        p: 3,
        mx: 'auto',
        mt: fullPage ? 4 : 0,
        maxWidth: theme.custom.maxContentWidth,
      }}
    >
      {children}
    </Box>
  );
}