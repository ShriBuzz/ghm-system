import { expect, test } from '@playwright/test';
import { enterCredentials, roles, signedInAs } from './support';

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

const menus = {
    administrator: ['Dashboard', 'Cases', 'Categories', 'Holidays', 'Locations', 'Users'],
    focal_person: ['Dashboard', 'Cases'],
    restricted_officer: ['Dashboard', 'Cases'],
};

for (const role of roles) {
    test.describe(role, () => {
        test.use({ storageState: signedInAs(role) });

        test('sees only the menu for this role, in English', { tag: ['@A1', '@A7'] }, async ({ page }) => {
            await page.goto('/admin');

            await expect(page.getByRole('navigation', { name: 'Sidebar navigation' }).getByRole('link')).toHaveText(menus[role]);
        });
    });
}

for (const role of ['focal_person', 'restricted_officer'] as const) {
    test.describe(role, () => {
        test.use({ storageState: signedInAs(role) });

        test('is refused the settings pages', { tag: ['@A7', '@A5'] }, async ({ page }) => {
            for (const settings of ['categories', 'holidays', 'locations', 'users']) {
                expect((await page.goto(`/admin/${settings}`))!.status()).toBe(403);
            }
        });
    });
}
