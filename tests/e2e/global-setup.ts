import { execFileSync } from 'node:child_process';
import { local } from './target';

// This machine's test database, up to date and with the placeholder lists the specs pick
// from. A staging server is migrated and seeded by its deploy, never from here.
export default function globalSetup(): void {
    if (local) {
        execFileSync('php', ['artisan', 'migrate', '--force']);
        execFileSync('php', ['artisan', 'db:seed', '--class=PlaceholderListsSeeder', '--force']);
    }
}
