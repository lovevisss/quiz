import { chromium } from 'playwright';

(async () => {
    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext({
        viewport: { width: 390, height: 844 }, // iPhone 12 viewport
    });
    const page = await context.newPage();

    await page.goto('http://localhost:5174/quiz/question');

    // Wait for the page to load
    await page.waitForLoadState('domcontentloaded');

    // Check if the "Next" button is present
    const nextButton = await page.$('[data-testid="next-button"]');
    if (nextButton) {
        console.log('Next button found!');
    } else {
        console.log('Next button not found.');
    }

    // Close the browser
    await browser.close();
})();
