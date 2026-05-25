import { List, ListItem, ListItemText, Typography } from '@mui/material';

export default function HelpTopicBody({ sections }) {
  return (
    <>
      {sections.map((section, index) => {
        if (section.type === 'heading') {
          return (
            <Typography
              key={`${section.type}-${index}`}
              variant="subtitle1"
              component="h2"
              fontWeight={600}
              sx={{ mt: index === 0 ? 0 : 2, mb: 1 }}
            >
              {section.text}
            </Typography>
          );
        }
        if (section.type === 'list') {
          return (
            <List key={`${section.type}-${index}`} dense disablePadding sx={{ pl: 0.5 }}>
              {section.items.map((item, itemIndex) => (
                <ListItem key={itemIndex} disableGutters sx={{ display: 'list-item', listStyleType: 'disc', ml: 2.5, py: 0.25 }}>
                  <ListItemText
                    primary={item}
                    primaryTypographyProps={{ variant: 'body2', color: 'text.primary' }}
                  />
                </ListItem>
              ))}
            </List>
          );
        }
        return (
          <Typography
            key={`${section.type}-${index}`}
            variant="body2"
            color="text.secondary"
            paragraph
            sx={{ mb: section.type === 'paragraph' ? 1.5 : 0 }}
          >
            {section.text}
          </Typography>
        );
      })}
    </>
  );
}
