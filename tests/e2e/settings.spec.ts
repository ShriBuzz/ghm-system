import { expect, type Locator, type Page } from '@playwright/test';
import { fillInDialog, headingOf, open, test } from './support';

// Staging runs this too, in front of UAT testers, so the spec leaves the lists as it found
// them: new categories and locations are not offered on the forms, the new user ends
// deactivated, and the holiday is far in the future and deleted again.
test.use({ role: 'administrator' });

const unique = () => `${Date.now().toString(36)}${Math.random().toString(36).slice(2, 6)}`;

/**
 * Opens a settings list, searches it and returns the row that holds the text.
 */
async function rowWith(page: Page, list: string, text: string) {
    await page.goto(`/admin/${list}`);
    await Promise.all([
        page.waitForResponse((r) => r.request().method() === 'POST' && r.url().includes('livewire')),
        page.getByRole('searchbox', { name: 'Search', exact: true }).fill(text),
    ]);

    return page.getByRole('row').filter({ hasText: text });
}

/**
 * Clicks a dialog's save button and waits for the server to answer: leaving the page
 * earlier would cancel the save.
 */
async function save(page: Page, button: Locator) {
    await Promise.all([
        page.waitForResponse((r) => r.request().method() === 'POST' && r.url().includes('livewire')),
        button.click(),
    ]);
}

/**
 * Opens the create dialog of a settings list, lets the caller fill it, and saves.
 */
async function create(page: Page, list: string, label: string, fill: (dialog: Locator) => Promise<void>) {
    await page.goto(`/admin/${list}`);
    const dialog = page.getByRole('dialog', { name: `Create ${label}` });
    await open(page.getByRole('button', { name: `New ${label}` }), headingOf(dialog));
    await fill(dialog);
    await save(page, dialog.getByRole('button', { name: 'Create', exact: true }));
    await expect(headingOf(dialog)).toBeHidden();
}

/**
 * Opens the edit dialog of a row, titled after the record, and returns it.
 */
async function edit(page: Page, list: string, text: string) {
    const row = await rowWith(page, list, text);
    const dialog = page.getByRole('dialog', { name: `Edit ${text}` });
    await open(row.getByRole('button', { name: 'Edit' }), headingOf(dialog));

    return dialog;
}

test('adds a category and marks it restricted', { tag: ['@A7', '@A5'] }, async ({ page }) => {
    const name = `Spec category ${unique()}`;
    await create(page, 'categories', 'category', async (dialog) => {
        await dialog.getByLabel('Name in English').fill(name);
        await dialog.getByLabel('Name in Nepali').fill('परीक्षण वर्ग');
        await dialog.getByRole('switch', { name: 'Offered on the forms' }).click();
    });

    let dialog = await edit(page, 'categories', name);
    await expect(dialog.getByRole('switch', { name: 'Restricted' })).toHaveAttribute('aria-checked', 'false');
    await dialog.getByRole('switch', { name: 'Restricted' }).click();
    await save(page, dialog.getByRole('button', { name: 'Save changes' }));
    await expect(headingOf(dialog)).toBeHidden();

    dialog = await edit(page, 'categories', name);
    await expect(dialog.getByRole('switch', { name: 'Restricted' })).toHaveAttribute('aria-checked', 'true');
    await expect(dialog.getByRole('switch', { name: 'Offered on the forms' })).toHaveAttribute('aria-checked', 'false');
});

test('adds a location and renames it', { tag: ['@A7'] }, async ({ page }) => {
    const name = `Spec location ${unique()}`;
    await create(page, 'locations', 'location', async (dialog) => {
        await dialog.getByLabel('Name in English').fill(name);
        await dialog.getByLabel('Name in Nepali').fill('परीक्षण स्थान');
        await dialog.getByRole('switch', { name: 'Offered on the forms' }).click();
    });

    const dialog = await edit(page, 'locations', name);
    await dialog.getByLabel('Name in English').fill(`${name} renamed`);
    await save(page, dialog.getByRole('button', { name: 'Save changes' }));
    await expect(headingOf(dialog)).toBeHidden();

    await expect(await rowWith(page, 'locations', `${name} renamed`)).toBeVisible();
});

test('adds a holiday and deletes it', { tag: ['@A7', '@A2'] }, async ({ page }) => {
    const name = `Spec holiday ${unique()}`;
    const date = `20${90 + Math.floor(Math.random() * 10)}-0${1 + Math.floor(Math.random() * 9)}-1${Math.floor(Math.random() * 9)}`;
    await create(page, 'holidays', 'holiday', async (dialog) => {
        await dialog.getByLabel('Date').fill(date);
        await dialog.getByLabel('Name').fill(name);
    });

    const row = await rowWith(page, 'holidays', name);
    const confirm = page.getByRole('alertdialog', { name: `Delete ${name}` });
    await open(row.getByRole('button', { name: 'Delete' }), headingOf(confirm));
    await save(page, confirm.getByRole('button', { name: 'Delete' }));

    await rowWith(page, 'holidays', name);
    await expect(page.getByRole('heading', { name: 'No holidays' })).toBeVisible();
});

test('adds a user, changes their role and deactivates them', { tag: ['@A7', '@A1'] }, async ({ page }) => {
    const name = `Spec user ${unique()}`;
    await create(page, 'users', 'user', async (dialog) => {
        await dialog.getByLabel('Name').fill(name);
        await dialog.getByLabel('Email address').fill(`${name.replaceAll(' ', '-').toLowerCase()}@example.com`);
        await dialog.getByLabel('Role').selectOption('Focal person');
        await fillInDialog(dialog.getByRole('textbox', { name: 'Password' }), 'password', `spec-${unique()}-${unique()}`);
    });
    await expect(await rowWith(page, 'users', name)).toContainText('Focal person');

    const dialog = await edit(page, 'users', name);
    await dialog.getByLabel('Role').selectOption('Restricted-case officer');
    await dialog.getByRole('switch', { name: 'Active' }).click();
    await save(page, dialog.getByRole('button', { name: 'Save changes' }));
    await expect(headingOf(dialog)).toBeHidden();

    await expect(await rowWith(page, 'users', name)).toContainText('Restricted-case officer');
    const reopened = await edit(page, 'users', name);
    await expect(reopened.getByRole('switch', { name: 'Active' })).toHaveAttribute('aria-checked', 'false');
});
