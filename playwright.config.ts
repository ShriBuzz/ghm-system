import { defineConfig, devices } from '@playwright/test';

// Playwright runs its own server on 8001, so it never tests whatever else is on the usual 8000.
const baseURL = process.env.BASE_URL ?? 'http://127.0.0.1:8001';
const local = /^http:\/\/(127\.0\.0\.1|localhost)(:\d+)?\/?$/.test(baseURL);

export default defineConfig({
    testDir: 'tests/e2e',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? [['list'], ['html', { open: 'never' }]] : 'list',
    // Away from this machine only the read-only @smoke specs run, because production
    // holds real grievances (CLAUDE.md). Staging sets E2E_FULL=1 for the full suite.
    grep: local || process.env.E2E_FULL === '1' ? undefined : /@smoke/,
    use: {
        baseURL,
        trace: 'retain-on-failure',
    },
    projects: [
        {
            name: 'mobile',
            testMatch: /public\.spec\.ts/,
            use: { ...devices['Pixel 5'], viewport: { width: 360, height: 740 } },
        },
        {
            name: 'desktop',
            testMatch: /admin\.spec\.ts/,
            use: { ...devices['Desktop Chrome'] },
        },
    ],
    webServer: local
        ? {
              command: 'php artisan serve --host=127.0.0.1 --port=8001',
              url: 'http://127.0.0.1:8001/up',
              // Several PHP workers, so parallel specs do not queue behind one request.
              env: { PHP_CLI_SERVER_WORKERS: '4' },
              reuseExistingServer: !process.env.CI,
          }
        : undefined,
});
