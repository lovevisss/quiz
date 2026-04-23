# Deployment Documentation

## Feature Switches

### Quiz Participation

- **Environment Variable**: `QUIZ_ENABLED`
- **Default**: `true`
- **Description**: Controls whether users can participate in quizzes.
    - `true`: Quiz participation is enabled.
    - `false`: Quiz participation is disabled.

## Scheduled Tasks

### Leaderboard Refresh

- **Command**: `leaderboard:refresh`
- **Schedule**: Hourly

### Leaderboard Cleanup

- **Command**: `leaderboard:cleanup`
- **Schedule**: Daily at 2:00 AM

## Environment Variables

### Example `.env` Configuration

```env
# Feature switches
QUIZ_ENABLED=true

# Other environment variables...
```
