import { expect, test } from '@playwright/test';

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
    const email = `e2e-lock-${Date.now()}@example.com`;
    await page.goto('/admin/login');

    for (let attempt = 1; attempt <= 5; attempt++) {
        await page.locator('input[type="email"]').fill(email);
        await page.locator('input[type="password"]').fill(`wrong-password-${attempt}`);
        await Promise.all([
            page.waitForResponse((r) => r.request().method() === 'POST' && r.url().includes('livewire')),
            page.locator('form button[type="submit"]').click(),
        ]);
    }

    await page.locator('input[type="password"]').fill('wrong-password-6');
    await page.locator('form button[type="submit"]').click();

    await expect(page.getByRole('alert')).toContainText(/\d/);
});
