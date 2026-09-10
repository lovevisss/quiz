import { expect, test } from '@playwright/test';

test.describe('quiz flow', () => {
    test('renders activity, question, result, leaderboard, and certificate pages', async ({
        page,
    }) => {
        await page.goto('/quiz');
        await page.waitForSelector('[data-testid="activity-home"]');
        await expect(page.getByTestId('activity-home')).toBeVisible();
        await expect(page.getByTestId('quiz-cas-start-link')).toHaveAttribute(
            'href',
            /\/auth\/cas\/redirect\?return=/,
        );

        await page.goto('/quiz/question');
        await expect(page.getByTestId('quiz-question-card')).toBeVisible();

        await page.goto('/quiz/result');
        await page.screenshot({
            path: '.sisyphus/evidence/task-6-mobile-ui.png',
            fullPage: true,
        });
        await expect(page.getByTestId('quiz-result')).toBeVisible();

        await page.goto('/quiz/leaderboard');
        await expect(page.getByTestId('leaderboard-page')).toBeVisible();

        await page.goto('/quiz/certificate');
        await expect(page.getByTestId('certificate-page')).toBeVisible();
    });

    test('keeps fixed actions visible on mobile viewports', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto('/quiz/question');
        await page.screenshot({
            path: '.sisyphus/evidence/quiz-question-page-debug.png',
            fullPage: true,
        });
        await page.waitForSelector('[data-testid="next-button"]');
        await expect(page.getByTestId('next-button')).toBeVisible();

        await page.goto('/quiz/result');
        await expect(
            page.getByRole('button', { name: /重新获取/ }),
        ).toBeVisible();
    });

    test('shows error on invalid quiz page', async ({ page }) => {
        await page.goto('/quiz/nonexistent');
        await expect(page.locator('body')).toContainText(
            /not found|404|不存在|页面不存在/i,
        );
    });
});
