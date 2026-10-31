import { expect, type Page, test } from '@playwright/test';

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

/**
 * Fills the sign-in form and waits until Livewire's component state holds both values.
 * Filament can bind its fields after the page has loaded; text typed before then never
 * reaches the server.
 */
async function enterCredentials(page: Page, email: string, password: string): Promise<void> {
    await expect(async () => {
        await page.locator('input[type="email"]').fill(email);
        await page.locator('input[type="password"]').fill(password);

        const state = await page.evaluate(() => {
            const id = document.querySelector('form')!.closest('[wire\\:id]')!.getAttribute('wire:id');
            const wire = (window as any).Livewire.find(id);

            return [wire.$get('data.email'), wire.$get('data.password')];
        });

        expect(state).toEqual([email, password]);
    }).toPass({ timeout: 10_000 });
}
