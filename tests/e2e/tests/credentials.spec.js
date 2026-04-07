const { test, expect } = require('../utils/fixtures');

const CREDENTIAL_URL   = 'admin/bagisto/credentials';
const BAGISTO_SHOP_URL = 'https://rohit.bagisto.com';
const BAGISTO_EMAIL    = 'admin@example.com';
const BAGISTO_PASSWORD = 'admin@123';

/** Navigate to the Bagisto credentials listing page. */
async function goToCredentials(adminPage) {
    await adminPage.goto(CREDENTIAL_URL);
    await adminPage.waitForLoadState('networkidle');
}

/** Locate a DataGrid row whose text contains `keyword`. */
function credentialRow(adminPage, keyword) {
    return adminPage.locator('tr', { hasText: keyword }).first();
}

test.describe('Bagisto Credentials', () => {
    test.slow(); // doubles the timeout for every test in this group

    // ─── INDEX ────────────────────────────────────────────────────────────────

    test('should load and display the credentials listing page', async ({ adminPage }) => {
        await goToCredentials(adminPage);

        await expect(
            adminPage.locator('h1, p.text-xl').filter({ hasText: 'Bagisto Credentials' }).first()
        ).toBeVisible();
    });

    test('should show a "Create Credential" button', async ({ adminPage }) => {
        await goToCredentials(adminPage);

        await expect(
            adminPage.getByRole('link', { name: /Create Credential/i })
                .or(adminPage.getByRole('button', { name: /Create Credential/i }))
                .first()
        ).toBeVisible();
    });

    // ─── VALIDATION ───────────────────────────────────────────────────────────

    test('should show validation errors when saving an empty credential form', async ({ adminPage }) => {
        await goToCredentials(adminPage);

        await adminPage.getByRole('link', { name: /Create Credential/i })
            .or(adminPage.getByRole('button', { name: /Create Credential/i }))
            .first()
            .click();
        await adminPage.waitForLoadState('load');

        // Submit with empty fields
        await adminPage.getByRole('button', { name: /Save/i }).first().click();

        // At least one error message should appear
        await expect(
            adminPage.locator('[class*="error"], .text-red-600, [class*="invalid"], .help-block').first()
        ).toBeVisible({ timeout: 8000 });
    });

    // ─── CREATE ───────────────────────────────────────────────────────────────

    test('should create a new Bagisto credential', async ({ adminPage }) => {
        await goToCredentials(adminPage);

        await adminPage.getByRole('link', { name: /Create Credential/i })
            .or(adminPage.getByRole('button', { name: /Create Credential/i }))
            .first()
            .click();
        await adminPage.waitForLoadState('load');

        await adminPage.locator('input[name="shopUrl"], input[name="shop_url"], input[placeholder*="shop" i]')
            .first()
            .fill(BAGISTO_SHOP_URL);

        await adminPage.locator('input[name="email"], input[type="email"]')
            .first()
            .fill(BAGISTO_EMAIL);

        await adminPage.locator('input[name="password"], input[type="password"]')
            .first()
            .fill(BAGISTO_PASSWORD);

        await adminPage.getByRole('button', { name: /Save/i }).first().click();

        await expect(
            adminPage.getByText(/created successfully/i)
                .or(adminPage.getByText(/saved successfully/i))
        ).toBeVisible({ timeout: 20000 });
    });

    // ─── EDIT ─────────────────────────────────────────────────────────────────

    test('should navigate to the edit page of an existing credential', async ({ adminPage }) => {
        await goToCredentials(adminPage);

        const row = credentialRow(adminPage, 'bagisto');
        if (await row.count() === 0) { test.skip(); return; }

        await row.locator('a[title="Edit"], button[title="Edit"]')
            .or(row.getByRole('link', { name: /edit/i }))
            .first()
            .click();
        await adminPage.waitForLoadState('load');

        await expect(
            adminPage.locator('h1, p.text-xl').filter({ hasText: /Edit Credential/i }).first()
        ).toBeVisible();
    });

    test('should update a credential successfully', async ({ adminPage }) => {
        await goToCredentials(adminPage);

        const row = credentialRow(adminPage, 'bagisto');
        if (await row.count() === 0) { test.skip(); return; }

        await row.locator('a[title="Edit"], button[title="Edit"]')
            .or(row.getByRole('link', { name: /edit/i }))
            .first()
            .click();
        await adminPage.waitForLoadState('load');

        // Re-submit the existing form to test the UPDATE path
        await adminPage.getByRole('button', { name: /Save/i }).first().click();

        await expect(
            adminPage.getByText(/updated successfully/i)
                .or(adminPage.getByText(/saved successfully/i))
        ).toBeVisible({ timeout: 20000 });
    });

    // ─── DELETE ───────────────────────────────────────────────────────────────

    test('should delete a credential and confirm its removal', async ({ adminPage }) => {
        await goToCredentials(adminPage);

        const row = credentialRow(adminPage, 'bagisto');
        if (await row.count() === 0) { test.skip(); return; }

        await row.locator('a[title="Delete"], button[title="Delete"]')
            .or(row.getByRole('button', { name: /delete/i }))
            .first()
            .click();

        // Confirm the deletion dialog if it appears
        const confirmBtn = adminPage.getByRole('button', { name: /Confirm|Yes|Delete/i }).last();
        if (await confirmBtn.isVisible({ timeout: 4000 }).catch(() => false)) {
            await confirmBtn.click();
        }

        await expect(
            adminPage.getByText(/deleted successfully/i)
        ).toBeVisible({ timeout: 20000 });
    });
});
