import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';

test('the public home page loads with its security headers', { tag: ['@smoke', '@S1'] }, async ({ page, baseURL }) => {
    const response = await page.goto('/ne');
    const headers = response!.headers();

    expect(response!.status()).toBe(200);
    expect(headers['content-security-policy']).toContain("frame-ancestors 'none'");
    expect(headers['referrer-policy']).toBe('no-referrer');

    if (baseURL!.startsWith('https://')) {
        expect(headers['strict-transport-security']).toBeTruthy();
    }
});

test('opens in Nepali and switches to English and back', { tag: ['@P2', '@S9'] }, async ({ page }) => {
    await page.goto('/');
    await expect(page).toHaveURL(/\/ne$/);
    await expect(page.locator('html')).toHaveAttribute('lang', 'ne');
    await expect(page.locator('[aria-current="true"]')).toHaveText('नेपाली');

    await page.getByRole('link', { name: 'English' }).click();
    await expect(page).toHaveURL(/\/en$/);
    await expect(page.locator('html')).toHaveAttribute('lang', 'en');
    await expect(page.getByRole('heading', { level: 1 })).toHaveText('Grievance handling');

    await page.getByRole('link', { name: 'नेपाली' }).click();
    await expect(page).toHaveURL(/\/ne$/);
    await expect(page.locator('html')).toHaveAttribute('lang', 'ne');
});

test('remembers the language for the next visit to "/"', { tag: ['@P2'] }, async ({ page }) => {
    await page.goto('/en');
    await page.goto('/');

    await expect(page).toHaveURL(/\/en$/);
});

test.describe('with JavaScript off', () => {
    test.use({ javaScriptEnabled: false });

    test('the language switch still works', { tag: ['@P2', '@S9'] }, async ({ page }) => {
        await page.goto('/ne');
        await page.getByRole('link', { name: 'English' }).click();

        await expect(page.locator('html')).toHaveAttribute('lang', 'en');
    });
});

for (const locale of ['ne', 'en']) {
    test(`/${locale} passes an axe WCAG 2.2 AA scan and fits 360 px`, { tag: ['@S9'] }, async ({ page }) => {
        await page.goto(`/${locale}`);

        const scan = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa']).analyze();

        expect(scan.violations).toEqual([]);
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
    });
}

test('public pages load nothing from other sites', { tag: ['@X1'] }, async ({ page, baseURL }) => {
    const offsite: string[] = [];
    page.on('request', (request) => {
        if (!request.url().startsWith(baseURL!)) offsite.push(request.url());
    });

    await page.goto('/ne');
    await page.goto('/en');

    expect(offsite).toEqual([]);
});
