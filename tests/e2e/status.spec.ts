import AxeBuilder from '@axe-core/playwright';
import { devices, expect, type Page } from '@playwright/test';
import { findCase, openTab, test, writeOnCase } from './support';

// The status page (P8) on a 360 px phone (mobile project), with a focal person replying from the panel.
const wcag = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'];

/** Sends an anonymous grievance from the public form and returns its reference and PIN. */
async function sendGrievance(page: Page): Promise<{ reference: string; pin: string }> {
    await page.goto('/en');
    await page.getByLabel('What is it about?').selectOption('Wages and benefits');
    await page.getByLabel('Where did it happen?').selectOption('Head office');
    await page.getByLabel('Day', { exact: true }).selectOption('3');
    await page.getByLabel('Month', { exact: true }).selectOption('10');
    await page.getByText('Once', { exact: true }).click();
    await page.getByLabel('Describe what happened').fill('Test grievance for the status page');
    await page.getByRole('button', { name: 'Send grievance' }).click();

    const reference = (await page.locator('[data-reference]').textContent())!.trim();
    const pin = (await page.locator('[data-pin]').textContent())!.trim();
    await page.getByRole('button', { name: 'Done' }).click();

    return { reference, pin };
}

async function lookUp(page: Page, reference: string, pin: string, locale = 'en'): Promise<void> {
    await page.goto(`/${locale}/status`);
    await page.getByLabel(locale === 'en' ? 'Reference number' : 'सन्दर्भ नम्बर').fill(reference);
    await page.getByLabel(locale === 'en' ? 'PIN' : 'पिन', { exact: true }).fill(pin);
    await page.getByRole('button', { name: locale === 'en' ? 'Check' : 'हेर्नुहोस्' }).click();
}

test('a complainant reads a staff reply and adds information, with the reference and PIN in no address', { tag: ['@P8', '@A3', '@N1'] }, async ({ page, browser, baseURL, staff }) => {
    const addresses: string[] = [];
    page.on('request', (request) => addresses.push(request.url()));

    const { reference, pin } = await sendGrievance(page);

    // The staff panel as the desktop project runs it: this project's phone settings would carry over otherwise.
    const focal = await browser.newContext({ ...devices['Desktop Chrome'], baseURL, storageState: await staff.sessionOf('focal_person') });
    const panel = await focal.newPage();
    await (await findCase(panel, reference)).click();
    await openTab(panel, 'Messages');
    await writeOnCase(panel, 'Send reply', 'Reply to the complainant', 'Reply', 'Payroll is checking your wages.');
    await focal.close();

    await lookUp(page, reference, pin);
    await expect(page).toHaveURL(/\/en\/status\/case$/);
    await expect(page.locator('.status-value')).toHaveText('Received');
    const thread = page.locator('.thread');
    await expect(thread).toContainText('Grievance team');
    await expect(thread).toContainText('Payroll is checking your wages.');

    await page.getByLabel('Add information or reply').fill('It started in July.');
    await page.getByRole('button', { name: 'Send', exact: true }).click();
    await expect(page.getByRole('status')).toHaveText('Your message was sent.');
    await expect(thread).toContainText('It started in July.');

    await page.getByRole('button', { name: 'Close' }).click();
    await expect(page).toHaveURL(/\/en\/status$/);
    await page.goto('/en/status/case');
    await expect(page).toHaveURL(/\/en\/status$/);

    expect(addresses.join(' ')).not.toContain(reference);
    expect(addresses.join(' ')).not.toContain(pin);
});

test('locks a reference after 5 wrong PINs and says so', { tag: ['@P8'] }, async ({ page }) => {
    for (let attempt = 0; attempt < 5; attempt++) {
        await lookUp(page, 'GHM-2026-9998', '000000');
    }

    await expect(page.locator('.error-summary')).toContainText('locked for 15 minutes');
});

test('the lookup and status pages pass an axe WCAG 2.2 AA scan and fit 360 px, in both languages', { tag: ['@S9', '@P8'] }, async ({ page }) => {
    const { reference, pin } = await sendGrievance(page);

    for (const locale of ['ne', 'en']) {
        await page.goto(`/${locale}/status`);
        expect((await new AxeBuilder({ page }).withTags(wcag).analyze()).violations).toEqual([]);

        await lookUp(page, reference, pin, locale);
        await expect(page).toHaveURL(new RegExp(`/${locale}/status/case$`));
        expect((await new AxeBuilder({ page }).withTags(wcag).analyze()).violations).toEqual([]);
        expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(360);
    }
});
