import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/e2e',
    use: {
        baseURL: 'http://127.0.0.1:8001',
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
        {
            name: 'iPhone 12',
            use: { ...devices['iPhone 12'] },
        },
        {
            name: 'Pixel 7',
            use: { ...devices['Pixel 7'] },
        },
    ],
    webServer: {
        command:
            'powershell -NoProfile -Command "if (Test-Path public/hot) { Remove-Item public/hot }; $env:APP_URL=\'http://127.0.0.1:8001\'; php -S 127.0.0.1:8001 -t public server.php"',
        url: 'http://127.0.0.1:8001',
        reuseExistingServer: false,
        timeout: 120000,
    },
});
