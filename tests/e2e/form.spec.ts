import AxeBuilder from '@axe-core/playwright';
import { expect, test, type Page } from '@playwright/test';
import { fileURLToPath } from 'node:url';

// The public grievance form and the page with the reference and PIN, on a 360 px phone (mobile project).
const photo = fileURLToPath(new URL('./fixtures/photo.jpg', import.meta.url));
const wcag = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'];

/** The required answers, in English, with a fake description. */
async function fillRequired(page: Page, description = 'Test grievance: wages unpaid for three months'): Promise<void> {
    await page.getByLabel('What is it about?').selectOption('Wages and benefits');
    await page.getByLabel('Where did it happen?').selectOption('Head office, Thapathali');
    await page.getByLabel('Day', { exact: true }).selectOption('3');
    await page.getByLabel('Month', { exact: true }).selectOption('10');
    await page.getByText('Once', { exact: true }).click();
    await page.getByLabel('Describe what happened').fill(description);
}

test.describe('with JavaScript off', () => {
    test.use({ javaScriptEnabled: false });

    test('sends an anonymous grievance with a photo and keeps the reference and PIN on screen until Done', { tag: ['@P1', '@P5', '@P7'] }, async ({ page }) => {
        await page.goto('/ne');
        // The Nepali page lists categories and locations by their Nepali names.
        await page.getByLabel('गुनासो केबारे हो?').selectOption({ index: 1 });
        await page.getByLabel('कहाँ भयो?').selectOption({ index: 1 });
        await page.getByLabel('गते', { exact: true }).selectOption('3');
        await page.getByLabel('महिना', { exact: true }).selectOption('10');
        await page.getByText('एक पटक', { exact: true }).click();
        await page.getByLabel('के भयो, लेख्नुहोस्').fill('परीक्षण गुनासो: तीन महिनादेखि तलब पाएको छैन।');
        await page.getByLabel('फोटो वा कागजात (चाहेमा)').setInputFiles(photo);
        await page.getByRole('button', { name: 'गुनासो पठाउनुहोस्' }).click();

        await expect(page).toHaveURL(/\/ne\/received$/);
        const reference = page.locator('[data-reference]');
        const pin = page.locator('[data-pin]');
        await expect(reference).toHaveText(/^GHM-\d{4}-\d{4}$/);
        await expect(pin).toHaveText(/^\d{6}$/);
        const shown = await pin.textContent();

        await page.reload();
        await expect(pin).toHaveText(shown!);

        await page.getByRole('button', { name: 'भयो' }).click();
        await expect(page).toHaveURL(/\/ne$/);
        await page.goto('/ne/received');
        await expect(page).toHaveURL(/\/ne$/);
    });

    test('hides the contact fields for an anonymous grievance and shows them for a named one', { tag: ['@P4'] }, async ({ page }) => {
        await page.goto('/en');
        await expect(page.getByLabel('Your name')).toBeHidden();

        await page.getByText('With my name').click();
        await expect(page.getByLabel('Your name')).toBeVisible();
        await expect(page.getByLabel('Phone number')).toBeVisible();

        await page.getByText('Without my name (anonymous)').click();
        await expect(page.getByLabel('Your name')).toBeHidden();
    });

    test('refuses a file over 5 MB with a clear message and keeps what was typed', { tag: ['@P5', '@S4'] }, async ({ page }) => {
        await page.goto('/en');
        await fillRequired(page, 'Test grievance kept after a refused file');
        await page.getByLabel('A photo or document (optional)').setInputFiles({
            name: 'big.pdf',
            mimeType: 'application/pdf',
            buffer: Buffer.alloc(6.5 * 1024 * 1024, 'a'),
        });
        await page.getByRole('button', { name: 'Send grievance' }).click();

        await expect(page).toHaveURL(/\/en$/);
        await expect(page.locator('.error-summary')).toContainText('5 MB');
        await expect(page.getByLabel('Describe what happened')).toHaveValue('Test grievance kept after a refused file');
    });

    test('sends the grievance even when the session ended while it was being typed', { tag: ['@S2', '@P1'] }, async ({ page }) => {
        await expectTypedTextSurvivesAnEndedSession(page);
    });
});

/**
 * Fill the form, lose the session (cookies gone, as after 30 idle minutes), and send. A browser that sends
 * Sec-Fetch-Site passes Laravel's forgery check on that header, so nothing typed is lost; an older one gets
 * the form back with its text (PublicFormTest covers that path).
 */
async function expectTypedTextSurvivesAnEndedSession(page: Page): Promise<void> {
    await page.goto('/en');
    await fillRequired(page, 'Test grievance typed before the session ended');
    await page.context().clearCookies();
    await page.getByRole('button', { name: 'Send grievance' }).click();

    await expect(page).toHaveURL(/\/en\/received$/);
    await expect(page.locator('[data-pin]')).toHaveText(/^\d{6}$/);
}

test('sends the grievance even when the session ended while it was being typed, with JavaScript on', { tag: ['@S2', '@P1'] }, async ({ page }) => {
    await expectTypedTextSurvivesAnEndedSession(page);
});

test('hides the spam trap from people and from screen readers', { tag: ['@P9', '@S9'] }, async ({ page }) => {
    await page.goto('/en');

    await expect(page.locator('#website')).toBeHidden();
    expect(await page.locator('form[data-grievance]').ariaSnapshot()).not.toContain('Leave this empty');
});

test('carries what was typed across a language switch, then forgets the copy', { tag: ['@P2', '@P1'] }, async ({ page }) => {
    await page.goto('/en');
    await page.getByText('With my name').click();
    await page.getByLabel('Your name').fill('Test Complainant');
    await page.getByLabel('Describe what happened').fill('Test grievance typed before switching language');

    await page.getByRole('link', { name: 'नेपाली' }).click();

    await expect(page).toHaveURL(/\/ne$/);
    await expect(page.getByLabel('के भयो, लेख्नुहोस्')).toHaveValue('Test grievance typed before switching language');
    await expect(page.getByLabel('तपाईंको नाम')).toHaveValue('Test Complainant');
    expect(await page.evaluate(() => sessionStorage.getItem('grievance-draft'))).toBeNull();
});

test('turns away a file over 5 MB before it is sent', { tag: ['@P5'] }, async ({ page }) => {
    await page.goto('/en');
    const file = page.getByLabel('A photo or document (optional)');
    await file.setInputFiles({ name: 'big.pdf', mimeType: 'application/pdf', buffer: Buffer.alloc(6 * 1024 * 1024, 'a') });

    await expect(page.getByRole('alert')).toHaveText('This file is larger than 5 MB. Choose a smaller photo or PDF.');
    await expect(file).toHaveValue('');
});

test('takes the visitor to what needs fixing, each message linked to its field', { tag: ['@S9', '@P1'] }, async ({ page }) => {
    await page.goto('/en');
    await page.getByRole('button', { name: 'Send grievance' }).click();

    const summary = page.locator('.error-summary');
    await expect(summary).toBeFocused();
    await summary.getByRole('link', { name: 'Choose where it happened.' }).click();
    await expect(page.getByLabel('Where did it happen?')).toBeFocused();

    const scan = await new AxeBuilder({ page }).withTags(wcag).analyze();
    expect(scan.violations).toEqual([]);
});

test('the received page passes an axe WCAG 2.2 AA scan in both languages', { tag: ['@S9', '@P7'] }, async ({ page }) => {
    await page.goto('/en');
    await fillRequired(page);
    await page.getByRole('button', { name: 'Send grievance' }).click();
    await expect(page).toHaveURL(/\/en\/received$/);

    for (const locale of ['en', 'ne']) {
        await page.goto(`/${locale}/received`);
        const scan = await new AxeBuilder({ page }).withTags(wcag).analyze();
        expect(scan.violations).toEqual([]);
        expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(360);
    }
});

test('fits a 320 px screen with its gutters, in both languages', { tag: ['@S9', '@P1'] }, async ({ page }) => {
    await page.setViewportSize({ width: 320, height: 800 });

    for (const path of ['/ne', '/en', '/ne/status', '/en/status']) {
        await page.goto(path);
        expect(await page.evaluate(() => document.documentElement.scrollWidth), path).toBeLessThanOrEqual(320);
        // The 16 px gutter on the right as on the left: no field runs into it.
        const right = await page.locator('main form').evaluate((form) => form.getBoundingClientRect().right);
        expect(right, path).toBeLessThanOrEqual(320 - 16);
    }
});

test('sends the grievance once, however often Send is tapped on a slow connection', { tag: ['@P1', '@P7'] }, async ({ page }) => {
    let posts = 0;
    await page.route('**/en', async (route) => {
        if (route.request().method() === 'POST') {
            posts++;
            await new Promise((resolve) => setTimeout(resolve, 1500));
        }
        // A second send would cancel the first in the browser, after the server already has it.
        await route.continue().catch(() => {});
    });

    await page.goto('/en');
    await fillRequired(page);
    // Two taps from the page itself, the second while the first is on its way: Playwright's own click
    // and locators wait for the navigation to finish.
    const label = await page.evaluate(async () => {
        const send = document.querySelector<HTMLButtonElement>('#send')!;
        send.click();
        await new Promise((resolve) => setTimeout(resolve, 300));
        const shown = send.textContent;
        send.click();
        return shown;
    });

    await expect(page).toHaveURL(/\/en\/received$/);
    expect(posts).toBe(1);
    expect(label).toBe('Sending…');
});

test('can still be sent after the browser\'s Stop button cancelled a send', { tag: ['@P1'] }, async ({ page }) => {
    await page.route('**/en', async (route) => {
        if (route.request().method() === 'POST') {
            await new Promise((resolve) => setTimeout(resolve, 1500));
        }
        await route.continue().catch(() => {});
    });

    await page.goto('/en');
    await fillRequired(page);
    // Stop fires no event a page can hear, so the guard against a second tap only lasts 10 seconds.
    // In one call: Playwright cannot start another while the send is on its way.
    await page.evaluate(async () => {
        document.querySelector<HTMLButtonElement>('#send')!.click();
        await new Promise((resolve) => setTimeout(resolve, 300));
        window.stop();
    });
    await expect(page).toHaveURL(/\/en$/);
    await expect(page.locator('#send')).toHaveText('Sending…');
    // Eleven seconds later, without the wait: the page's clock moves on.
    await page.evaluate(() => {
        const now = Date.now();
        Date.now = () => now + 11_000;
    });
    await page.locator('#send').click();

    await expect(page).toHaveURL(/\/en\/received$/);
});

test('the form weighs under 50 KB with everything it loads', { tag: ['@X1', '@P1'] }, async ({ page }) => {
    let bytes = 0;
    page.on('response', async (response) => {
        bytes += (await response.body().catch(() => Buffer.alloc(0))).length;
    });

    await page.goto('/ne', { waitUntil: 'networkidle' });

    expect(bytes).toBeGreaterThan(0);
    expect(bytes).toBeLessThan(50 * 1024);
});

test('asks a named complainant who does not want to be contacted for a phone or an email', { tag: ['@U8', '@P4'] }, async ({ page }) => {
    await page.goto('/en');
    await fillRequired(page);
    await page.getByText('With my name').click();
    await page.getByLabel('Your name').fill('Test Complainant');
    await page.getByLabel('Do not contact me').check();
    await page.getByRole('button', { name: 'Send grievance' }).click();

    await expect(page.locator('[data-error-summary]')).toContainText('Give a phone number or an email.');

    await page.getByLabel('Phone number').fill('9800000000');
    await page.getByRole('button', { name: 'Send grievance' }).click();
    await expect(page).toHaveURL(/\/en\/received$/);
});
