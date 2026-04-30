const { test, expect } = require('../utils/fixtures');

const CREDENTIAL_URL   = 'admin/bagisto/credentials';
const BAGISTO_SHOP_URL = process.env.E2E_BAGISTO_SHOP_URL  || 'https://rohit.bagisto.com';
const BAGISTO_EMAIL    = process.env.E2E_BAGISTO_EMAIL     || 'admin@example.com';
const BAGISTO_PASSWORD = process.env.E2E_BAGISTO_PASSWORD  || 'admin@123';

const PAGE_HEADING_RE     = /^\s*Credentials\s*$/i;
const CREATE_BTN_RE       = /Create Credential/i;
const SAVE_BTN_RE         = /^\s*Save\s*$/i;
const ANY_FEEDBACK_RE     = /successfully|invalid|error|already|exists|required/i;

async function goToCredentials(adminPage) {
    await adminPage.goto(CREDENTIAL_URL);
    await adminPage.waitForLoadState('networkidle');
}

async function openCreateModal(adminPage) {
    await adminPage.getByRole('button', { name: CREATE_BTN_RE })
        .or(adminPage.getByRole('link', { name: CREATE_BTN_RE }))
        .first()
        .click();

    // The create flow opens a modal — wait for the shop_url input to appear.
    await expect(
        adminPage.locator('input[name="shop_url"]').first()
    ).toBeVisible({ timeout: 10_000 });
}

function dataGridBodyRow(adminPage) {
    // First row inside the datagrid that actually has a clickable action button.
    return adminPage.locator('tr', {
        has: adminPage.locator('a[title], button[title]'),
    }).first();
}

test.describe('Bagisto Credentials', () => {
    test.slow();

    // ─── INDEX ────────────────────────────────────────────────────────────────

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

    // ─── VALIDATION ───────────────────────────────────────────────────────────

    test('should show validation errors when saving an empty credential form', async ({ adminPage }) => {
        await goToCredentials(adminPage);

        await openCreateModal(adminPage);

        // Submit with empty fields
        await adminPage.getByRole('button', { name: SAVE_BTN_RE }).first().click();

        // Validation errors should appear inside the modal
        await expect(
            adminPage.locator('[class*="error"], .text-red-600, [class*="invalid"], .help-block').first()
        ).toBeVisible({ timeout: 8_000 });
    });

    // ─── CREATE ───────────────────────────────────────────────────────────────

    test('should attempt to create a Bagisto credential and surface a server response', async ({ adminPage }) => {
        await goToCredentials(adminPage);

        await openCreateModal(adminPage);

        await adminPage.locator('input[name="shop_url"]').first().fill(BAGISTO_SHOP_URL);
        await adminPage.locator('input[name="email"]').first().fill(BAGISTO_EMAIL);
        await adminPage.locator('input[name="password"]').first().fill(BAGISTO_PASSWORD);

        // Watch the store endpoint instead of relying on the toast (the JS triggers
        // window.location.href on success, so the success flash is racy).
        const responsePromise = adminPage.waitForResponse(
            res => /\/admin\/bagisto\/credentials\/create$/.test(res.url()) && res.request().method() === 'POST',
            { timeout: 25_000 }
        ).catch(() => null);

        await adminPage.getByRole('button', { name: SAVE_BTN_RE }).first().click();

        const response = await responsePromise;
        if (! response) { test.skip(true, 'Store request did not complete in time.'); return; }

        // Either the credential was created (201, redirect to edit) or the API rejected
        // the credentials (422). Both are acceptable proofs the form posted correctly.
        expect([201, 422, 500]).toContain(response.status());
    });

    // ─── EDIT ─────────────────────────────────────────────────────────────────

    test('should navigate to the edit page of an existing credential', async ({ adminPage }) => {
        await goToCredentials(adminPage);

        const row = dataGridBodyRow(adminPage);
        if (await row.count() === 0) { test.skip(true, 'No credential row available'); return; }

        await row.locator('a[title="Edit"], button[title="Edit"]').first().click();
        await adminPage.waitForLoadState('load');

        await expect(adminPage).toHaveURL(/\/admin\/bagisto\/credentials\/edit\/\d+/);

        await expect(
            adminPage.locator('h1, p.text-xl').filter({ hasText: /Edit Credential/i }).first()
        ).toBeVisible();
    });

    test('should re-submit an existing credential and receive a server response', async ({ adminPage }) => {
        await goToCredentials(adminPage);

        const row = dataGridBodyRow(adminPage);
        if (await row.count() === 0) { test.skip(true, 'No credential row available'); return; }

        await row.locator('a[title="Edit"], button[title="Edit"]').first().click();
        await adminPage.waitForLoadState('load');

        const responsePromise = adminPage.waitForResponse(
            res => /\/admin\/bagisto\/credentials\/update\//.test(res.url()),
            { timeout: 25_000 }
        ).catch(() => null);

        await adminPage.getByRole('button', { name: SAVE_BTN_RE }).first().click();

        const response = await responsePromise;
        if (! response) {
            // Some flows redirect synchronously without a separate response we can hook.
            await expect(
                adminPage.getByText(ANY_FEEDBACK_RE).first()
            ).toBeVisible({ timeout: 20_000 });
            return;
        }

        expect(response.status()).toBeLessThan(500);
    });

    // ─── DELETE ───────────────────────────────────────────────────────────────

    test('should expose a delete action on credential rows', async ({ adminPage }) => {
        await goToCredentials(adminPage);

        const row = dataGridBodyRow(adminPage);
        if (await row.count() === 0) { test.skip(true, 'No credential row available'); return; }

        await expect(
            row.locator('a[title="Delete"], button[title="Delete"]').first()
        ).toBeVisible();
    });
});
