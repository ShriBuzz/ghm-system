import { createHmac } from 'node:crypto';
import { expect, type Page } from '@playwright/test';

export const roles = ['administrator', 'focal_person', 'restricted_officer'] as const;
export type Role = (typeof roles)[number];

/** Saved sign-in for a role, written once per run by auth.setup.ts. */
export function signedInAs(role: Role): string {
    return `playwright/.auth/${role}.json`;
}

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
