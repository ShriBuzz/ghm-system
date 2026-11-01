import { defineConfig, devices } from '@playwright/test';

// Playwright runs its own server on 8001, so it never tests whatever else is on the usual 8000.
const baseURL = process.env.BASE_URL ?? 'http://127.0.0.1:8001';
const local = /^http:\/\/(127\.0\.0\.1|localhost)(:\d+)?\/?$/.test(baseURL);

// Specs write fake cases and users, so locally they use ghm_testing, as CI does,
// never the ghm database you demo from. The server and auth.setup.ts both inherit it.
process.env.DB_DATABASE ??= 'ghm_testing';

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
            // Signs each role in once and saves the session: an authenticator code is
            // accepted only once, so specs cannot each sign in for themselves.
            name: 'setup',
            testMatch: /auth\.setup\.ts/,
        },
        {
            name: 'desktop',
            testMatch: /(admin|cases)\.spec\.ts/,
            dependencies: ['setup'],
            // One at a time: specs for a role share its one server session, and parallel
            // requests in one session drop each other's one-time messages, such as the slip's PIN.
            workers: 1,
            use: { ...devices['Desktop Chrome'] },
        },
    ],
    webServer: local
        ? {
              command: 'php artisan serve --host=127.0.0.1 --port=8001',
              url: 'http://127.0.0.1:8001/up',
              // Several PHP workers, so parallel specs do not queue behind one request.
              // The shipped public language, whatever .env says, as in phpunit.xml.
              env: { PHP_CLI_SERVER_WORKERS: '4', APP_LOCALE: 'ne' },
              reuseExistingServer: !process.env.CI,
          }
        : undefined,
});
