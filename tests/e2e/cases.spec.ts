import { expect, type Page } from '@playwright/test';
import { headingOf, open, test } from './support';

/**
 * Enters a fake offline case and returns what the slip shows.
 */
async function enterOfflineCase(
    page: Page,
    { category = 'Wages and benefits', location = 'Head office', receivedAt = '', anonymous = false } = {},
) {
    await page.goto('/admin/grievances/create');
    await page.getByLabel('Channel').selectOption('Phone');

    if (receivedAt) {
        await page.getByRole('textbox', { name: 'Received' }).fill(receivedAt);
    }

    await page.getByLabel('Category').selectOption(category);
    await page.getByLabel('Location').selectOption(location);
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
 * Opens the case list, searches it for a reference and returns the link to that case.
 */
async function findCase(page: Page, reference: string) {
    await page.goto('/admin/grievances');

    return search(page, reference);
}

/**
 * Searches the list on screen, keeping any filters, and returns the link to that case.
 */
async function search(page: Page, reference: string) {
    await Promise.all([
        page.waitForResponse((r) => r.request().method() === 'POST' && r.url().includes('livewire')),
        page.getByRole('searchbox', { name: 'Search', exact: true }).fill(reference),
    ]);

    return page.getByRole('link', { name: reference, exact: true });
}

/**
 * Picks a value in one of the case page's dialogs, such as Set status, and saves it.
 */
async function setOnCase(page: Page, action: string, field: string, value: string) {
    const dialog = page.getByRole('dialog', { name: action });
    await open(page.getByRole('button', { name: action }), headingOf(dialog));
    await dialog.getByLabel(field).selectOption(value);
    await dialog.getByRole('button', { name: 'Submit' }).click();
    await expect(headingOf(dialog)).toBeHidden();
}

/**
 * The list's empty state. Waiting for it, rather than for a missing row, proves the
 * list has been redrawn after the search.
 */
function noCases(page: Page) {
    return page.getByRole('heading', { name: 'No cases' });
}

/**
 * The days-open badge in a case's row of the list.
 */
function daysOpenBadge(page: Page, reference: string) {
    return page.getByRole('row').filter({ has: page.getByRole('link', { name: reference, exact: true }) }).locator('.fi-badge').first();
}

/**
 * A date some days back, as the date and date-time fields take it.
 */
function daysAgo(days: number) {
    const date = new Date(Date.now() - days * 86_400_000);
    const pad = (n: number) => String(n).padStart(2, '0');
    const day = `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;

    return { day, dateTime: `${day}T09:00` };
}

test.describe('focal person', () => {
    test.use({ role: 'focal_person' });

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

    test('gets the slip for a restricted case, then cannot open it', { tag: ['@A5', '@A6'] }, async ({ page, browser, baseURL, staff }) => {
        const { reference, pin } = await enterOfflineCase(page, { category: 'Sexual harassment or abuse', anonymous: true });
        expect(pin).toMatch(/^\d{6}$/);

        const officer = await browser.newContext({ baseURL, storageState: await staff.sessionOf('restricted_officer') });
        const officerPage = await officer.newPage();
        const link = await findCase(officerPage, reference);
        await expect(link).toBeVisible();
        const address = (await link.getAttribute('href'))!;
        await officer.close();

        await findCase(page, reference);
        await expect(noCases(page)).toBeVisible();
        expect([403, 404]).toContain((await page.goto(address))!.status());
    });

    test('counts the cases received on a day by status and category, leaving out restricted ones', { tag: ['@N3', '@A5'] }, async ({ page }) => {
        // A day of its own in the past, so cases from other specs and workers do not land in it.
        const day = `2021-${String(1 + Math.floor(Math.random() * 12)).padStart(2, '0')}-${String(1 + Math.floor(Math.random() * 28)).padStart(2, '0')}`;
        const before = await summaryOf(page, day);

        await enterOfflineCase(page, { receivedAt: `${day}T09:00` });
        await enterOfflineCase(page, { receivedAt: `${day}T10:00`, category: 'Sexual harassment or abuse', anonymous: true });

        const after = await summaryOf(page, day);
        expect(after.received).toBe(before.received + 1);
        expect(after.wages).toBe(before.wages + 1);
        expect(after.restricted).toBe(0);
    });
});

/**
 * Opens the summary for one day and reads the counts this spec checks.
 */
async function summaryOf(page: Page, day: string) {
    await page.goto('/admin/summary');
    // Each date goes to the server on change; read the counts only after both answers.
    for (const field of ['Received from', 'Received until']) {
        const updated = page.waitForResponse((response) => response.url().includes('/livewire'));
        await page.getByLabel(field).fill(day);
        await updated;
    }

    const count = async (name: string) => {
        const row = page.getByRole('row').filter({ has: page.getByRole('rowheader', { name, exact: true }) });

        return (await row.count()) ? Number(await row.getByRole('cell').innerText()) : 0;
    };

    return { received: await count('Received'), wages: await count('Wages and benefits'), restricted: await count('Sexual harassment or abuse') };
}

test.describe('administrator', () => {
    test.use({ role: 'administrator' });

    test('sets status and priority and assigns only to staff who can see the case', { tag: ['@A3', '@A4', '@A5'] }, async ({ page, staff }, testInfo) => {
        // This worker's focal person and restricted-case officer, so both exist to be offered or not.
        await staff.sessionOf('focal_person');
        await staff.sessionOf('restricted_officer');
        const focal = `E2E Focal person ${testInfo.parallelIndex}`;
        const officer = `E2E Restricted officer ${testInfo.parallelIndex}`;
        const { reference } = await enterOfflineCase(page);
        await (await findCase(page, reference)).click();
        await expect(page.getByRole('heading', { name: `View ${reference}` })).toBeVisible();

        await setOnCase(page, 'Set status', 'Status', 'Under assessment');
        await setOnCase(page, 'Set priority', 'Priority', 'High');

        const assign = page.getByRole('dialog', { name: 'Assign' });
        await open(page.getByRole('button', { name: 'Assign' }), headingOf(assign));
        const assignTo = assign.getByLabel('Assign to');
        await expect(assignTo.getByRole('option', { name: focal, exact: true })).toBeAttached();
        await expect(assignTo.getByRole('option', { name: officer, exact: true })).toHaveCount(0);
        await assignTo.selectOption(focal);
        await assign.getByRole('button', { name: 'Submit' }).click();

        const caseSection = page.getByRole('region', { name: 'Case' });
        await expect(caseSection).toContainText('Under assessment');
        await expect(caseSection).toContainText('High');
        await expect(caseSection).toContainText(focal);
    });

    test('filters the case list and flags cases open past 15 working days', { tag: ['@A2'] }, async ({ page }) => {
        test.setTimeout(90_000);
        const old = await enterOfflineCase(page, { category: 'Discrimination', location: 'Production plant', receivedAt: daysAgo(30).dateTime });
        const fresh = await enterOfflineCase(page);

        await (await findCase(page, fresh.reference)).click();
        await setOnCase(page, 'Set status', 'Status', 'Under assessment');
        await expect(page.getByRole('region', { name: 'Case' })).toContainText('Under assessment');

        await findCase(page, old.reference);
        await expect(daysOpenBadge(page, old.reference)).toHaveClass(/fi-color-danger/);
        await findCase(page, fresh.reference);
        await expect(daysOpenBadge(page, fresh.reference)).not.toHaveClass(/fi-color-danger/);

        const filters: [string, () => Promise<void>, { reference: string }, { reference: string }][] = [
            ['status', async () => {
                await page.getByRole('combobox', { name: 'Status' }).click();
                await page.getByRole('option', { name: 'Under assessment' }).click();
            }, fresh, old],
            ['overdue only', () => page.getByRole('switch', { name: /Overdue only/ }).click(), old, fresh],
            ['category', () => page.getByLabel('Category').selectOption('Discrimination'), old, fresh],
            ['location', () => page.getByLabel('Location').selectOption('Head office'), fresh, old],
            ['received dates', async () => {
                await page.getByLabel('Received from').fill(daysAgo(31).day);
                await page.getByLabel('Received until').fill(daysAgo(29).day);
            }, old, fresh],
        ];

        for (const [name, choose, shown, hidden] of filters) {
            await test.step(name, async () => {
                await page.goto('/admin/grievances');
                await open(page.getByRole('button', { name: 'Filter' }), page.getByRole('button', { name: 'Apply filters' }));
                await choose();
                await Promise.all([
                    page.waitForResponse((r) => r.request().method() === 'POST' && r.url().includes('livewire')),
                    page.getByRole('button', { name: 'Apply filters' }).click(),
                ]);

                await expect(await search(page, shown.reference)).toBeVisible();
                await search(page, hidden.reference);
                await expect(noCases(page)).toBeVisible();
            });
        }
    });
});

/**
 * A 1 × 1 PNG, enough for the document upload to accept and redraw.
 */
const tinyPng = { name: 'site-visit.png', mimeType: 'image/png', buffer: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==', 'base64') };

/**
 * Opens one of the case page's tabs (messages, notes, documents). Filament loads each
 * tab's table only once it is on screen.
 */
async function openTab(page: Page, name: string) {
    await open(page.getByRole('tab', { name }), page.getByRole('tabpanel', { name }));
}

/**
 * Opens a case page action that writes text, fills it and creates the record.
 */
async function writeOnCase(page: Page, button: string, heading: string, field: string, text: string) {
    const dialog = page.getByRole('dialog', { name: heading });
    await open(page.getByRole('button', { name: button }), headingOf(dialog));
    await dialog.getByLabel(field).fill(text);
    await dialog.getByRole('button', { name: 'Create', exact: true }).click();
    await expect(headingOf(dialog)).toBeHidden();
}

/**
 * Uploads a document on the case page on screen and returns its download address.
 */
async function uploadDocument(page: Page) {
    await openTab(page, 'Documents');
    const dialog = page.getByRole('dialog', { name: 'Upload document' });
    await open(page.getByRole('button', { name: 'Upload document' }), headingOf(dialog));
    // FilePond's own input, which exists only once its script has started; a file given earlier is lost.
    await dialog.locator('input.filepond--browser').setInputFiles(tinyPng);
    // Shown once on screen and once for screen readers.
    await expect(dialog.getByText('Upload complete').first()).toBeVisible();
    await dialog.getByRole('button', { name: 'Submit' }).click();
    await expect(headingOf(dialog)).toBeHidden();

    const link = page.getByRole('tabpanel', { name: 'Documents' }).getByRole('link', { name: tinyPng.name });
    await expect(link).toBeVisible();

    return (await link.getAttribute('href'))!;
}

test.describe('administrator, working a case', () => {
    test.use({ role: 'administrator' });

    test('adds a note, replies to the complainant and uploads a document', { tag: ['@A3', '@S4'] }, async ({ page }) => {
        const { reference } = await enterOfflineCase(page);
        await (await findCase(page, reference)).click();

        await openTab(page, 'Notes');
        await writeOnCase(page, 'Add note', 'Add internal note', 'Note', 'Spoke to the supervisor.');
        await expect(page.getByRole('tabpanel', { name: 'Notes' })).toContainText('Spoke to the supervisor.');

        await openTab(page, 'Messages');
        await writeOnCase(page, 'Send reply', 'Reply to the complainant', 'Reply', 'Payroll is checking your overtime.');
        const thread = page.getByRole('tabpanel', { name: 'Messages' });
        await expect(thread).toContainText('Payroll is checking your overtime.');
        await expect(thread).toContainText('Staff');

        const download = await page.request.get(await uploadDocument(page));
        expect(download.status()).toBe(200);
        expect(download.headers()['content-disposition']).toContain(`attachment; filename=${tinyPng.name}`);
    });

    test('shows every case page tab on a 360 px screen', { tag: ['@A3'] }, async ({ page }) => {
        const { reference } = await enterOfflineCase(page);
        await (await findCase(page, reference)).click();
        await page.setViewportSize({ width: 360, height: 740 });

        await page.getByRole('tab', { name: 'Messages' }).scrollIntoViewIfNeeded();

        for (const name of ['Messages', 'Notes', 'Documents']) {
            await expect(page.getByRole('tab', { name })).toBeInViewport({ ratio: 1 });
        }
    });

    test('sees a status change on the activity log', { tag: ['@S6'] }, async ({ page }) => {
        const { reference } = await enterOfflineCase(page);
        await (await findCase(page, reference)).click();
        await setOnCase(page, 'Set status', 'Status', 'Under investigation');

        await page.goto('/admin/activity-log');
        await open(page.getByRole('button', { name: 'Filter' }), page.getByRole('button', { name: 'Apply filters' }));
        await page.getByLabel('Case reference').fill(reference);
        await Promise.all([
            page.waitForResponse((r) => r.request().method() === 'POST' && r.url().includes('livewire')),
            page.getByRole('button', { name: 'Apply filters' }).click(),
        ]);

        const entry = page.getByRole('row').filter({ hasText: 'Status Changed' }).filter({ hasText: reference });
        await expect(entry).toContainText(reference);
        await expect(entry).toContainText('Received → Under Investigation');
    });
});

test.describe('focal person, documents', () => {
    test.use({ role: 'focal_person' });

    test('cannot download a document from a restricted case', { tag: ['@S4', '@A5'] }, async ({ page, browser, baseURL, staff }) => {
        const officer = await browser.newContext({ baseURL, storageState: await staff.sessionOf('restricted_officer') });
        const officerPage = await officer.newPage();
        const { reference } = await enterOfflineCase(officerPage, { category: 'Sexual harassment or abuse', anonymous: true });
        await (await findCase(officerPage, reference)).click();
        const address = await uploadDocument(officerPage);
        await officer.close();

        expect((await page.request.get(address)).status()).toBe(403);
    });
});
