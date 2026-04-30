const { test: base, expect } = require('@playwright/test');

const ADMIN_LOGIN_PATH = 'admin/login';

const ADMIN_EMAIL    = process.env.E2E_ADMIN_EMAIL    || process.env.ADMIN_USERNAME || 'admin@example.com';
const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD || process.env.ADMIN_PASSWORD || 'admin123';

async function loginAsAdmin(page) {
    await page.goto(ADMIN_LOGIN_PATH);
    await page.waitForLoadState('networkidle');

    if (! /\/admin\/login/.test(page.url())) {
        return;
    }

    await page.locator('input[name="email"], input[type="email"]').first().fill(ADMIN_EMAIL);
    await page.locator('input[name="password"], input[type="password"]').first().fill(ADMIN_PASSWORD);

    await Promise.all([
        page.waitForLoadState('networkidle'),
        page.getByRole('button', { name: /sign in|login|log in/i }).first().click(),
    ]);
}

const test = base.extend({
    adminPage: async ({ page }, use) => {
        await loginAsAdmin(page);
        await use(page);
    },
});

module.exports = { test, expect };
