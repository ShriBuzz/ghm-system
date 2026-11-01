import { expect, type Page, test } from '@playwright/test';
import { signedInAs } from './support';

/**
 * Enters a fake offline case and returns what the slip shows.
 */
async function enterOfflineCase(page: Page, { category = 'Wages and benefits', anonymous = false } = {}) {
    await page.goto('/admin/grievances/create');
    await page.getByLabel('Channel').selectOption('Phone');
    await page.getByLabel('Category').selectOption(category);
    await page.getByLabel('Location').selectOption('Head office');
    await page.getByLabel('When it happened').fill('2026-10-28');
    await page.getByLabel('How often').selectOption('Once');
    await page.getByLabel('Description').fill('Fake grievance for a browser spec.');

    if (anonymous) {
        await page.getByRole('switch', { name: /Anonymous/ }).click();
        await expect(page.getByRole('textbox', { name: 'Name' })).toBeHidden();
    } else {
        await page.getByRole('textbox', { name: 'Name' }).fill('Test Worker');
        await page.getByLabel('Phone').fill('9800000000');
        await page.getByLabel('Preferred contact').selectOption('Phone');
    }

    await page.getByRole('button', { name: 'Create', exact: true }).click();
    await expect(page).toHaveURL(/\/admin\/grievances\/recorded$/);

    const slip = await page.getByRole('main').innerText();

    return { reference: slip.match(/GHM-\d{4}-\d{4}/)![0], pin: slip.match(/PIN\s+(\d{6})\b/)![1] };
}

/**
 * Searches the case list for a reference and returns the link to that case.
 */
async function findCase(page: Page, reference: string) {
    await page.goto('/admin/grievances');
    await Promise.all([
        page.waitForResponse((r) => r.request().method() === 'POST' && r.url().includes('livewire')),
        page.getByRole('searchbox', { name: 'Search', exact: true }).fill(reference),
    ]);

    return page.getByRole('link', { name: reference, exact: true });
}

test.describe('focal person', () => {
    test.use({ storageState: signedInAs('focal_person') });

    test('enters an offline case and sees its reference and PIN once', { tag: ['@A6', '@P7'] }, async ({ page }) => {
        const { reference, pin } = await enterOfflineCase(page);

        expect(pin).toMatch(/^\d{6}$/);
        await expect(await findCase(page, reference)).toBeVisible();

        await page.goBack();
        await expect(page).toHaveURL(/\/admin\/grievances$/);
        await expect(page.getByRole('main')).not.toContainText(pin);
    });

    test('prints the slip without the panel around it', { tag: ['@A6'] }, async ({ page }) => {
        const { reference, pin } = await enterOfflineCase(page);
        await page.emulateMedia({ media: 'print' });

        await expect(page.getByRole('navigation', { name: 'Sidebar navigation' })).toBeHidden();
        await expect(page.getByRole('navigation', { name: 'Topbar' })).toBeHidden();
        await expect(page.getByRole('button', { name: 'Print slip' })).toBeHidden();
        await expect(page.getByRole('definition').filter({ hasText: reference })).toBeVisible();
        await expect(page.getByRole('definition').filter({ hasText: pin })).toBeVisible();
    });

    test('gets the slip for a restricted case, then cannot open it', { tag: ['@A5', '@A6'] }, async ({ page, browser, baseURL }) => {
        const { reference, pin } = await enterOfflineCase(page, { category: 'Sexual harassment or abuse', anonymous: true });
        expect(pin).toMatch(/^\d{6}$/);

        const officer = await browser.newContext({ baseURL, storageState: signedInAs('restricted_officer') });
        const officerPage = await officer.newPage();
        const link = await findCase(officerPage, reference);
        await expect(link).toBeVisible();
        const address = (await link.getAttribute('href'))!;
        await officer.close();

        await expect(await findCase(page, reference)).toHaveCount(0);
        expect([403, 404]).toContain((await page.goto(address))!.status());
    });
});

test.describe('administrator', () => {
    test.use({ storageState: signedInAs('administrator') });

    test('sets status and priority and assigns only to staff who can see the case', { tag: ['@A3', '@A4', '@A5'] }, async ({ page }) => {
        const { reference } = await enterOfflineCase(page);
        await (await findCase(page, reference)).click();
        await expect(page.getByRole('heading', { name: `View ${reference}` })).toBeVisible();

        await page.getByRole('button', { name: 'Set status' }).click();
        await page.getByRole('dialog', { name: 'Set status' }).getByLabel('Status').selectOption('Under assessment');
        await page.getByRole('dialog', { name: 'Set status' }).getByRole('button', { name: 'Submit' }).click();

        await page.getByRole('button', { name: 'Set priority' }).click();
        await page.getByRole('dialog', { name: 'Set priority' }).getByLabel('Priority').selectOption('High');
        await page.getByRole('dialog', { name: 'Set priority' }).getByRole('button', { name: 'Submit' }).click();

        await page.getByRole('button', { name: 'Assign' }).click();
        const assignTo = page.getByRole('dialog', { name: 'Assign' }).getByLabel('Assign to');
        await expect(assignTo.getByRole('option', { name: 'E2E Focal Person' })).toBeAttached();
        await expect(assignTo.getByRole('option', { name: 'E2E Restricted Officer' })).toHaveCount(0);
        await assignTo.selectOption('E2E Focal Person');
        await page.getByRole('dialog', { name: 'Assign' }).getByRole('button', { name: 'Submit' }).click();

        const caseSection = page.getByRole('region', { name: 'Case' });
        await expect(caseSection).toContainText('Under assessment');
        await expect(caseSection).toContainText('High');
        await expect(caseSection).toContainText('E2E Focal Person');
    });
});
