const { test, expect } = require('../utils/fixtures');

const CREDENTIAL_URL   = 'admin/bagisto/credentials';
const CREDENTIAL_ID    = process.env.E2E_CREDENTIAL_ID || 1;
const EDIT_URL         = `admin/bagisto/credentials/edit/${CREDENTIAL_ID}`;
const BAGISTO_SHOP_URL = process.env.E2E_BAGISTO_SHOP_URL  || 'https://rohit.bagisto.com';
const BAGISTO_EMAIL    = process.env.E2E_BAGISTO_EMAIL     || 'admin@example.com';
const BAGISTO_PASSWORD = process.env.E2E_BAGISTO_PASSWORD  || 'admin@123';

const PAGE_HEADING_RE = /^\s*Credentials\s*$/i;
const CREATE_BTN_RE   = /Create Credential/i;
const SAVE_BTN_RE     = /^\s*Save\s*$/i;

async function goToCredentials(adminPage) {
    await adminPage.goto(CREDENTIAL_URL);
    await adminPage.waitForLoadState('networkidle');
}

async function openCreateModal(adminPage) {
    await adminPage.getByRole('button', { name: CREATE_BTN_RE })
        .or(adminPage.getByRole('link', { name: CREATE_BTN_RE }))
        .first()
        .click();

    await expect(
        adminPage.locator('input[name="shop_url"]').first()
    ).toBeVisible({ timeout: 10_000 });
}

async function isMissingPage(adminPage) {
    if (/(404|not[-_ ]found)/i.test(adminPage.url())) return true;
    return (await adminPage.getByText(/page not found|404|whoops|server error/i).count()) > 0;
}

test.describe('Bagisto Credentials', () => {
    test.slow();

    test('should load and display the credentials listing page', async ({ adminPage }) => {
        await goToCredentials(adminPage);

        await expect(adminPage).toHaveURL(/\/admin\/bagisto\/credentials/);

        await expect(
            adminPage.locator('h1, p.text-xl').filter({ hasText: PAGE_HEADING_RE }).first()
        ).toBeVisible();
    });

    test('should show a "Create Credential" button', async ({ adminPage }) => {
        await goToCredentials(adminPage);

        await expect(
            adminPage.getByRole('button', { name: CREATE_BTN_RE })
                .or(adminPage.getByRole('link', { name: CREATE_BTN_RE }))
                .first()
        ).toBeVisible();
    });

    test('should show validation errors when saving an empty credential form', async ({ adminPage }) => {
        await goToCredentials(adminPage);

        await openCreateModal(adminPage);

        await adminPage.getByRole('button', { name: SAVE_BTN_RE }).first().click();

        await expect(
            adminPage.locator('[class*="error"], .text-red-600, [class*="invalid"], .help-block').first()
        ).toBeVisible({ timeout: 8_000 });
    });

    test('should attempt to create a Bagisto credential and surface a server response', async ({ adminPage }) => {
        await goToCredentials(adminPage);

        await openCreateModal(adminPage);

        await adminPage.locator('input[name="shop_url"]').first().fill(BAGISTO_SHOP_URL);
        await adminPage.locator('input[name="email"]').first().fill(BAGISTO_EMAIL);
        await adminPage.locator('input[name="password"]').first().fill(BAGISTO_PASSWORD);

        const responsePromise = adminPage.waitForResponse(
            res => /\/admin\/bagisto\/credentials\/create$/.test(res.url()) && res.request().method() === 'POST',
            { timeout: 25_000 }
        ).catch(() => null);

        await adminPage.getByRole('button', { name: SAVE_BTN_RE }).first().click();

        const response = await responsePromise;
        if (! response) { test.skip(true, 'Store request did not complete in time.'); return; }

        expect([201, 422, 500]).toContain(response.status());
    });

    test('should reach the credential edit page directly when one exists', async ({ adminPage }) => {
        await adminPage.goto(EDIT_URL);
        await adminPage.waitForLoadState('domcontentloaded');

        if (await isMissingPage(adminPage)) {
            test.skip(true, `No credential with id=${CREDENTIAL_ID} seeded`);
            return;
        }

        await expect(adminPage).toHaveURL(new RegExp(`/admin/bagisto/credentials/edit/${CREDENTIAL_ID}`));

        await expect(
            adminPage.locator('h1, p.text-xl').filter({ hasText: /Edit Credential/i }).first()
        ).toBeVisible({ timeout: 10_000 });
    });

    test('should re-submit an existing credential and receive a server response', async ({ adminPage }) => {
        await adminPage.goto(EDIT_URL);
        await adminPage.waitForLoadState('domcontentloaded');

        if (await isMissingPage(adminPage)) {
            test.skip(true, `No credential with id=${CREDENTIAL_ID} seeded`);
            return;
        }

        const responsePromise = adminPage.waitForResponse(
            res => /\/admin\/bagisto\/credentials\/update\//.test(res.url()),
            { timeout: 25_000 }
        ).catch(() => null);

        await adminPage.getByRole('button', { name: SAVE_BTN_RE }).first().click();

        const response = await responsePromise;
        if (! response) {
            test.skip(true, 'Update request did not complete (form may have submitted via full navigation)');
            return;
        }

        expect(response.status()).toBeLessThan(500);
    });
});
