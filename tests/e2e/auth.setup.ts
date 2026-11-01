import { execFileSync } from 'node:child_process';
import { expect, test as setup } from '@playwright/test';
import { enterCredentials, roles, signedInAs, totp } from './support';

// Seeds this machine's database, so it runs only against the local server: away from
// this machine the config runs @smoke specs only, and this one is not tagged @smoke.
setup('each role signs in with password and authenticator code', async ({ browser, baseURL }) => {
    execFileSync('php', ['artisan', 'migrate', '--force']);
    const users = JSON.parse(execFileSync('php', ['artisan', 'ghm:e2e-users'], { encoding: 'utf8' }));

    for (const role of roles) {
        const context = await browser.newContext({ baseURL });
        const page = await context.newPage();

        await page.goto('/admin/login');
        await enterCredentials(page, users[role].email, users[role].password);
        await page.locator('form button[type="submit"]').click();
        // Six one-digit boxes; typing moves to the next box and the last digit submits.
        await page.getByRole('textbox', { name: /6-digit code/ }).pressSequentially(totp(users[role].secret));

        await expect(page).toHaveURL(/\/admin$/);
        await context.storageState({ path: signedInAs(role) });
        await context.close();
    }
});
