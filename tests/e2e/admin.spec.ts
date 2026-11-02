import { expect } from '@playwright/test';
import { enterCredentials, freshUser, roles, signIn, test, totp } from './support';
import { idleURL, local } from './target';

test('the staff sign-in page is reachable and locked down', { tag: ['@smoke', '@S1', '@A1'] }, async ({ page }) => {
    const response = await page.goto('/admin');

    await expect(page).toHaveURL(/\/admin\/login$/);
    await expect(page.locator('input[type="email"]')).toBeVisible();
    await expect(page.locator('input[type="password"]')).toBeVisible();

    const policy = (await page.request.get('/admin/login')).headers()['content-security-policy'];
    expect(policy).toMatch(/'nonce-[^']+'/);
    expect(policy).toContain("frame-ancestors 'none'");
    expect(response!.status()).toBe(200);
});

test('a sixth failed sign-in shows the lock message', { tag: ['@S2'] }, async ({ page }) => {
    const email = `e2e-lock-${Date.now()}-${Math.random().toString(36).slice(2)}@example.com`;
    await page.goto('/admin/login');

    for (let attempt = 1; attempt <= 6; attempt++) {
        await enterCredentials(page, email, `wrong-password-${attempt}`);
        await Promise.all([
            page.waitForResponse((r) => r.request().method() === 'POST' && r.url().includes('livewire')),
            page.locator('form button[type="submit"]').click(),
        ]);
    }

    await expect(page.getByRole('alert')).toContainText(/\d/);
});

test('a new user sets up an authenticator app before reaching any panel page', { tag: ['@S10', '@A1'] }, async ({ page }, testInfo) => {
    const user = freshUser('focal_person', `enrol-${testInfo.parallelIndex}`, { mfa: false });
    await page.goto('/admin/login');
    await enterCredentials(page, user.email, user.password);
    await page.locator('form button[type="submit"]').click();
    await expect(page).toHaveURL(/\/admin\/multi-factor-authentication\/set-up$/);

    await page.goto('/admin/grievances');
    await expect(page).toHaveURL(/\/admin\/multi-factor-authentication\/set-up$/);

    await page.getByRole('button', { name: 'Set up' }).click();
    const dialog = page.getByRole('dialog', { name: 'Set up authenticator app' });
    const secret = (await dialog.getByRole('button', { name: /^[A-Z2-7]{16}$/ }).textContent())!.trim();
    await dialog.getByLabel('Current password').fill(user.password);
    await dialog.getByRole('textbox', { name: /6-digit code/ }).pressSequentially(totp(secret));
    await dialog.getByRole('button', { name: 'Next' }).click();

    // Recovery codes, so a lost phone does not lock the user out.
    await expect(dialog.getByRole('group', { name: 'recovery' }).getByRole('listitem')).toHaveCount(8);
    await dialog.getByRole('button', { name: 'Enable authenticator app' }).click();
    // Slow on purpose: each of the eight recovery codes is stored as a bcrypt hash.
    await expect(page.getByText('Authenticator app has been enabled')).toBeVisible({ timeout: 20_000 });
    await page.getByRole('button', { name: 'Continue' }).click();

    // On to the page that was refused before setup.
    await expect(page).toHaveURL(/\/admin\/grievances$/);
    await expect(page.getByRole('heading', { name: 'Cases', level: 1 })).toBeVisible();
});

test.describe('on a server whose sessions last one idle minute', () => {
    test.skip(!local, 'That server runs only on this machine; a remote one keeps its own lifetime.');
    test.use({ baseURL: idleURL });

    test('an idle tab goes back to the sign-in page by itself', { tag: ['@S2'] }, async ({ page }, testInfo) => {
        test.setTimeout(150_000);
        await signIn(page, freshUser('focal_person', `idle-${testInfo.parallelIndex}`));

        // No clicks from here: the page's idle check reloads it once the session has expired.
        await expect(page).toHaveURL(/\/admin\/login$/, { timeout: 120_000 });
    });
});

const menus = {
    administrator: ['Dashboard', 'Cases', 'Summary', 'Activity log', 'Categories', 'Holidays', 'Locations', 'Users'],
    focal_person: ['Dashboard', 'Cases', 'Summary'],
    restricted_officer: ['Dashboard', 'Cases', 'Summary'],
};

for (const role of roles) {
    test.describe(role, () => {
        test.use({ role });

        test('sees only the menu for this role, in English', { tag: ['@A1', '@A7', '@S6', '@N3'] }, async ({ page }) => {
            await page.goto('/admin');

            await expect(page.getByRole('navigation', { name: 'Sidebar navigation' }).getByRole('link')).toHaveText(menus[role]);
        });
    });
}

for (const role of ['focal_person', 'restricted_officer'] as const) {
    test.describe(role, () => {
        test.use({ role });

        test('is refused the settings pages', { tag: ['@A7', '@A5'] }, async ({ page }) => {
            for (const settings of ['categories', 'holidays', 'locations', 'users']) {
                expect((await page.goto(`/admin/${settings}`))!.status()).toBe(403);
            }
        });
    });
}
