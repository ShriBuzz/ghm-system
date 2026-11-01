// The server under test: Playwright's own on this machine unless BASE_URL names another.
export const baseURL = process.env.BASE_URL ?? 'http://127.0.0.1:8001';
export const local = /^http:\/\/(127\.0\.0\.1|localhost)(:\d+)?\/?$/.test(baseURL);

// A second local server whose sessions last one idle minute, for the S2 idle spec.
export const idleURL = 'http://127.0.0.1:8002';
