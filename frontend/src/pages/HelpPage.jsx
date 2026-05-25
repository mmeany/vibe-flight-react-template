import {
  ChevronLeft as ChevronLeftIcon,
  ChevronRight as ChevronRightIcon,
} from '@mui/icons-material';
import {
  IconButton,
  MenuItem,
  Paper,
  Stack,
  TextField,
  Typography,
} from '@mui/material';
import { useEffect } from 'react';
import { Navigate, useNavigate, useParams } from 'react-router-dom';
import HelpTopicBody from '../components/HelpTopicBody';
import {
  DEFAULT_HELP_TOPIC_ID,
  getHelpTopic,
  HELP_TOPICS,
} from '../help/helpTopics';

export default function HelpPage() {
  const { topicId } = useParams();
  const navigate = useNavigate();
  const topic = getHelpTopic(topicId);

  useEffect(() => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }, [topicId]);

  if (!topic) {
    return <Navigate to={`/help/${DEFAULT_HELP_TOPIC_ID}`} replace />;
  }

  const prevTopic = topic.index > 0 ? HELP_TOPICS[topic.index - 1] : null;
  const nextTopic = topic.index < HELP_TOPICS.length - 1 ? HELP_TOPICS[topic.index + 1] : null;

  const goToTopic = (id) => navigate(`/help/${id}`);

  return (
    <>
      <Typography variant="h4" component="h1" gutterBottom>
        Help
      </Typography>

      <Stack direction="row" alignItems="center" spacing={0.5} sx={{ mb: 2 }}>
        <IconButton
          aria-label="Previous help topic"
          onClick={() => prevTopic && goToTopic(prevTopic.id)}
          disabled={!prevTopic}
          size="small"
        >
          <ChevronLeftIcon />
        </IconButton>
        <TextField
          select
          label="Topic"
          value={topic.id}
          onChange={e => goToTopic(e.target.value)}
          size="small"
          sx={{ flexGrow: 1, minWidth: 0 }}
          inputProps={{ 'aria-label': 'Help topic' }}
        >
          {HELP_TOPICS.map(item => (
            <MenuItem key={item.id} value={item.id}>
              {item.title}
            </MenuItem>
          ))}
        </TextField>
        <IconButton
          aria-label="Next help topic"
          onClick={() => nextTopic && goToTopic(nextTopic.id)}
          disabled={!nextTopic}
          size="small"
        >
          <ChevronRightIcon />
        </IconButton>
      </Stack>

      <Paper sx={{ p: { xs: 2, sm: 3 }, mb: 2 }}>
        <Typography variant="h6" component="h2" gutterBottom>
          {topic.title}
        </Typography>
        <Typography variant="caption" color="text.secondary" display="block" sx={{ mb: 2 }}>
          {topic.index + 1} of {HELP_TOPICS.length}
        </Typography>
        <HelpTopicBody sections={topic.sections} />
      </Paper>
    </>
  );
}
