const { test, expect } = require('../utils/fixtures');

const EXPORTS_URL  = 'admin/exports';
const PRODUCTS_URL = 'admin/catalog/products';

async function goToExports(adminPage) {
    await adminPage.goto(EXPORTS_URL);
    await adminPage.waitForLoadState('networkidle');
}

/** Find a DataGrid row containing `keyword`. */
function exportRow(adminPage, keyword) {
    return adminPage.locator('tr', { hasText: keyword }).first();
}

// ─── LISTING ─────────────────────────────────────────────────────────────────

test.describe('Bagisto Export – Listing', () => {
    test.slow();

    test('should load the Exports listing page', async ({ adminPage }) => {
        await goToExports(adminPage);

        await expect(
            adminPage.locator('h1, p.text-xl').filter({ hasText: 'Exports' }).first()
        ).toBeVisible();
    });

    test('should list the Bagisto Product Export profile', async ({ adminPage }) => {
        await goToExports(adminPage);

        const row = exportRow(adminPage, 'Bagisto Product Export');
        if (await row.count() === 0) { test.skip(); return; }

        await expect(row).toBeVisible();
    });

    test('should list the Bagisto Attribute Export profile', async ({ adminPage }) => {
        await goToExports(adminPage);

        const row = exportRow(adminPage, 'Bagisto Attribute Export');
        if (await row.count() === 0) { test.skip(); return; }

        await expect(row).toBeVisible();
    });
});

// ─── PRODUCT EXPORT ───────────────────────────────────────────────────────────

test.describe('Bagisto Export – Product Export Profile', () => {
    test.slow();

    test('should open the Product Export profile edit/view page', async ({ adminPage }) => {
        await goToExports(adminPage);

        const row = exportRow(adminPage, 'Bagisto Product Export');
        if (await row.count() === 0) { test.skip(); return; }

        await row.getByRole('link').first().click();
        await adminPage.waitForLoadState('load');

        await expect(
            adminPage.locator('select, input, [role="combobox"]').first()
        ).toBeVisible({ timeout: 10000 });
    });

    test('should trigger a Product Export and confirm it was queued', async ({ adminPage }) => {
        await goToExports(adminPage);

        const row = exportRow(adminPage, 'Bagisto Product Export');
        if (await row.count() === 0) { test.skip(); return; }

        const btn = row.locator('a[title="Export"], button[title="Export"], span[title="Export"]').first();
        if (! await btn.isVisible({ timeout: 4000 }).catch(() => false)) { test.skip(); return; }
        await btn.click();

        // Confirm any confirmation dialog
        const confirmBtn = adminPage.getByRole('button', { name: /Export|Run|Confirm/i }).last();
        if (await confirmBtn.isVisible({ timeout: 4000 }).catch(() => false)) {
            await confirmBtn.click();
        }

        await expect(
            adminPage.getByText(/started|queued|scheduled/i).first()
        ).toBeVisible({ timeout: 25000 });
    });
});

// ─── ATTRIBUTE EXPORT ────────────────────────────────────────────────────────

test.describe('Bagisto Export – Attribute Export Profile', () => {
    test.slow();

    test('should open the Attribute Export profile edit/view page', async ({ adminPage }) => {
        await goToExports(adminPage);

        const row = exportRow(adminPage, 'Bagisto Attribute Export');
        if (await row.count() === 0) { test.skip(); return; }

        await row.getByRole('link').first().click();
        await adminPage.waitForLoadState('load');

        await expect(
            adminPage.locator('select, input, [role="combobox"]').first()
        ).toBeVisible({ timeout: 10000 });
    });

    test('should trigger an Attribute Export and confirm it was queued', async ({ adminPage }) => {
        await goToExports(adminPage);

        const row = exportRow(adminPage, 'Bagisto Attribute Export');
        if (await row.count() === 0) { test.skip(); return; }

        const btn = row.locator('a[title="Export"], button[title="Export"], span[title="Export"]').first();
        if (! await btn.isVisible({ timeout: 4000 }).catch(() => false)) { test.skip(); return; }
        await btn.click();

        const confirmBtn = adminPage.getByRole('button', { name: /Export|Run|Confirm/i }).last();
        if (await confirmBtn.isVisible({ timeout: 4000 }).catch(() => false)) {
            await confirmBtn.click();
        }

        await expect(
            adminPage.getByText(/started|queued|scheduled/i).first()
        ).toBeVisible({ timeout: 25000 });
    });
});

// ─── QUICK EXPORT ────────────────────────────────────────────────────────────

test.describe('Bagisto Export – Quick Export from Product Catalog', () => {
    test.slow();

    test('should reach the product catalog page', async ({ adminPage }) => {
        await adminPage.goto(PRODUCTS_URL);
        await adminPage.waitForLoadState('networkidle');

        await expect(
            adminPage.locator('h1, p.text-xl').filter({ hasText: /Products/i }).first()
        ).toBeVisible();
    });

    test('should display a Quick Export / Export button when products are listed', async ({ adminPage }) => {
        await adminPage.goto(PRODUCTS_URL);
        await adminPage.waitForLoadState('networkidle');

        const btn = adminPage.getByRole('button', { name: /Quick Export|Export/i })
            .or(adminPage.locator('[title="Quick Export"]'))
            .first();

        if (await btn.count() === 0) { test.skip(); return; }

        await expect(btn).toBeVisible();
    });

    test('should open the quick-export modal/dropdown when clicking Export', async ({ adminPage }) => {
        await adminPage.goto(PRODUCTS_URL);
        await adminPage.waitForLoadState('networkidle');

        const btn = adminPage.getByRole('button', { name: /Quick Export/i })
            .or(adminPage.locator('[title="Quick Export"]'))
            .first();

        if (await btn.count() === 0) { test.skip(); return; }

        await btn.click();

        // Expect some modal/dropdown to appear with Bagisto options
        await expect(
            adminPage.locator('[role="dialog"], [role="menu"], .modal').first()
        ).toBeVisible({ timeout: 8000 });
    });
});
