import { execSync } from 'node:child_process';
import { existsSync } from 'node:fs';
import { createHmac } from 'node:crypto';
import { test as base, expect, type Locator, type Page } from '@playwright/test';

export const roles = ['administrator', 'focal_person', 'restricted_officer'] as const;
export type Role = (typeof roles)[number];

export type User = { email: string; password: string; secret: string | null };

/**
 * A fake user on the server under test, with a fresh password and authenticator secret.
 * E2E_ARTISAN says how to run artisan on that server: `php artisan` here, or for staging
 * something like `ssh deploy@staging php /path/to/artisan`. Production refuses the command.
 */
export function freshUser(role: Role, key: string, { mfa = true } = {}): User {
    const artisan = process.env.E2E_ARTISAN ?? 'php artisan';
    const output = execSync(`${artisan} ghm:e2e-user ${role} ${key}${mfa ? '' : ' --without-mfa'}`, { encoding: 'utf8' });

    // The last line, in case a remote shell prints a banner first.
    return JSON.parse(output.trim().split('\n').pop()!);
}

/**
 * Signs in with the password and the current authenticator code, as staff would.
 */
export async function signIn(page: Page, user: User): Promise<void> {
    await page.goto('/admin/login');
    await enterCredentials(page, user.email, user.password);
    await page.locator('form button[type="submit"]').click();
    // Six one-digit boxes; typing moves to the next box and the last digit submits.
    await page.getByRole('textbox', { name: /6-digit code/ }).pressSequentially(totp(user.secret!));
    await expect(page).toHaveURL(/\/admin$/);
}

/**
 * Specs set `role` to run signed in. Each worker has its own user per role: a session
 * shared by parallel specs would lose one-time messages such as the slip's PIN, and an
 * authenticator code is accepted only once, so specs cannot each sign in. A session saved
 * by an earlier run is reused while the server accepts it, so quick reruns stay under
 * Filament's limit of five sign-ins a minute.
 */
export const test = base.extend<{ role: Role | undefined }, { staff: { sessionOf(role: Role): Promise<string> } }>({
    role: [undefined, { option: true }],
    staff: [
        async ({ browser }, use, workerInfo) => {
            const sessions = new Map<Role, Promise<string>>();
            const baseURL = workerInfo.project.use.baseURL;

            await use({
                sessionOf(role) {
                    if (!sessions.has(role)) {
                        sessions.set(role, (async () => {
                            const file = `playwright/.auth/${role}-${workerInfo.parallelIndex}.json`;

                            if (existsSync(file)) {
                                const context = await browser.newContext({ baseURL, storageState: file });
                                const page = await context.newPage();
                                await page.goto('/admin');
                                const accepted = new URL(page.url()).pathname === '/admin';
                                await context.close();

                                if (accepted) {
                                    return file;
                                }
                            }

                            // Empty on purpose: inside a test, a new context would otherwise get that test's session.
                            const context = await browser.newContext({ baseURL, storageState: { cookies: [], origins: [] } });
                            await signIn(await context.newPage(), freshUser(role, `${role}-${workerInfo.parallelIndex}`));
                            await context.storageState({ path: file });
                            await context.close();

                            return file;
                        })());
                    }

                    return sessions.get(role)!;
                },
            });
        },
        { scope: 'worker' },
    ],
    storageState: async ({ role, staff, storageState }, use) => {
        await use(role ? await staff.sessionOf(role) : storageState);
    },
});

/**
 * The current authenticator code for a base32 secret (RFC 6238: SHA-1, 6 digits,
 * 30 seconds), as an authenticator app would show it.
 */
export function totp(secret: string, now = Date.now()): string {
    const bits = [...secret.toUpperCase()]
        .map((c) => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'.indexOf(c).toString(2).padStart(5, '0'))
        .join('');
    const key = Buffer.from(bits.match(/.{8}/g)!.map((byte) => parseInt(byte, 2)));
    const counter = Buffer.alloc(8);
    counter.writeBigUInt64BE(BigInt(Math.floor(now / 30_000)));
    const hmac = createHmac('sha1', key).update(counter).digest();
    const offset = hmac[hmac.length - 1] & 0xf;

    return String((hmac.readUInt32BE(offset) & 0x7fffffff) % 1_000_000).padStart(6, '0');
}

/**
 * Clicks a button until what it opens is shown. A click that lands before Livewire has
 * started on a new page does nothing, and Filament gives no other sign that it is ready.
 * Once it shows, the button is not clicked again.
 */
export async function open(button: Locator, shownWhenOpen: Locator): Promise<void> {
    await expect(async () => {
        if (!(await shownWhenOpen.isVisible())) {
            await button.click();
        }

        await expect(shownWhenOpen).toBeVisible({ timeout: 2_000 });
    }).toPass({ timeout: 15_000 });
}

/**
 * A Filament dialog's heading. The dialog element itself has no size, so it always counts
 * as hidden; its heading shows whether the dialog is open.
 */
export function headingOf(dialog: Locator): Locator {
    return dialog.getByRole('heading').first();
}

/**
 * Fills the sign-in form and waits until Livewire's component state holds both values.
 * Filament can bind its fields after the page has loaded; text typed before then never
 * reaches the server.
 */
export async function enterCredentials(page: Page, email: string, password: string): Promise<void> {
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

/**
 * Types into a field of an open dialog until Livewire's copy of the dialog's form holds the
 * value, as enterCredentials does for sign-in: under load Filament can bind a dialog's field
 * after it shows, and text typed before then never reaches the server.
 */
export async function fillInDialog(field: Locator, statePath: string, value: string): Promise<void> {
    await expect(async () => {
        await field.fill(value);

        const state = await field.evaluate((input, path) => {
            const id = input.closest('[wire\\:id]')!.getAttribute('wire:id');

            return (window as any).Livewire.find(id).$get(`mountedActions.0.data.${path}`);
        }, statePath);

        expect(state).toBe(value);
    }).toPass({ timeout: 10_000 });
}
