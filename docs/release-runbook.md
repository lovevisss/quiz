# Release Runbook: Task 9 - Leaderboard Operations

## Overview

This document outlines the procedures for managing the leaderboard refresh and cleanup tasks, including activation switches, rollback mechanisms, and monitoring.

---

## Activation Switches

### Environment Variables

- **`QUIZ_ENABLED`**: Enables or disables the quiz feature.
    - Default: `true`
- **`MAX_QUEUE_JOBS`**: Sets the maximum number of jobs allowed in the queue.
    - Default: `100`

---

## Scheduled Tasks

### Leaderboard Refresh

- **Command**: `php artisan leaderboard:refresh`
- **Schedule**: Hourly
- **Description**: Refreshes leaderboard data.

### Leaderboard Cleanup

- **Command**: `php artisan leaderboard:cleanup`
- **Schedule**: Daily at 02:00
- **Description**: Cleans up old leaderboard data.

> **调度注册入口唯一：**
> 仅通过 `bootstrap/app.php` 的 `withSchedule` 注册，所有调度变更请同步此处。

---

## Rollback Procedures

1. **Disable Tasks**:
    - Set `QUIZ_ENABLED=false` in `.env`.
    - Run `php artisan config:cache` to apply changes.

2. **Clear Queues**:
    - Run `php artisan queue:clear` to remove pending jobs.

3. **Revert Changes**:
    - Restore the previous `.env` file from backup.

---

## Monitoring

1. **View Scheduled Tasks**:
    - Run `php artisan schedule:list` to verify task schedules.

2. **Check Queue Status**:
    - Use `php artisan queue:work --once` to process jobs manually.

3. **Logs**:
    - Check `storage/logs/laravel.log` for task execution details.

---

## Verification

1. **Run Commands**:
    - `php artisan schedule:list`
    - `php artisan queue:work --once`

2. **Expected Outputs**:
    - Tasks should appear in the schedule list.
    - Queue worker should process jobs without errors.

---

## Notes

- Ensure `.env` changes are committed only if necessary.
- Always test changes in a staging environment before deploying to production.
