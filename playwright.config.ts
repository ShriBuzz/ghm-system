import { defineConfig, devices } from '@playwright/test';
import { baseURL, local } from './tests/e2e/target';

// Specs write fake cases and users, so locally they use ghm_testing, as CI does,
// never the ghm database you demo from. The servers and the setup both inherit it.
process.env.DB_DATABASE ??= 'ghm_testing';

// Playwright runs its own servers, so it never tests whatever else is on the usual 8000.
// Both use the shipped public language, whatever .env says, as in phpunit.xml.
const server = (port: number, env: Record<string, string>) => ({
    command: `php artisan serve --host=127.0.0.1 --port=${port}`,
    url: `http://127.0.0.1:${port}/up`,
    // Several PHP workers, so parallel specs do not queue behind one request.
    env: { PHP_CLI_SERVER_WORKERS: '4', APP_LOCALE: 'ne', ...env },
    reuseExistingServer: !process.env.CI,
});

export default defineConfig({
    testDir: 'tests/e2e',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? [['list'], ['html', { open: 'never' }]] : 'list',
    // Away from this machine only the read-only @smoke specs run, because production
    // holds real grievances (CLAUDE.md). Staging sets E2E_FULL=1 for the full suite.
    grep: local || process.env.E2E_FULL === '1' ? undefined : /@smoke/,
    globalSetup: './tests/e2e/global-setup.ts',
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
            testMatch: /(admin|cases|settings)\.spec\.ts/,
            use: { ...devices['Desktop Chrome'] },
        },
    ],
    webServer: local
        ? [
              server(8001, {}),
              // S2: sessions end after one idle minute instead of 30. They live in cookies here,
              // so this server's session clean-up cannot delete the other servers' session files.
              server(8002, { SESSION_LIFETIME: '1', SESSION_DRIVER: 'cookie' }),
          ]
        : undefined,
});
