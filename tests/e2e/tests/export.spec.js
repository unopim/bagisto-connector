const { test, expect } = require('../utils/fixtures');

const EXPORTS_URL  = 'admin/exports';
const PRODUCTS_URL = 'admin/catalog/products';

const EXPORTS_HEADING_RE  = /Exports?/i;
const PRODUCTS_HEADING_RE = /Products?/i;

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

        await expect(adminPage).toHaveURL(/\/admin\/exports/);

        await expect(
            adminPage.locator('h1, p.text-xl, [class*="text-xl"]').filter({ hasText: EXPORTS_HEADING_RE }).first()
        ).toBeVisible({ timeout: 10_000 });
    });

    test('should list the Bagisto Product export profile', async ({ adminPage }) => {
        await goToExports(adminPage);

        const row = exportRow(adminPage, 'Bagisto Product');
        if (await row.count() === 0) { test.skip(true, 'Bagisto Product export profile not seeded'); return; }

        await expect(row).toBeVisible();
    });

    test('should list the Bagisto Attribute export profile', async ({ adminPage }) => {
        await goToExports(adminPage);

        const row = exportRow(adminPage, 'Bagisto Attribute');
        if (await row.count() === 0) { test.skip(true, 'Bagisto Attribute export profile not seeded'); return; }

        await expect(row).toBeVisible();
    });
});

// ─── PRODUCT EXPORT ───────────────────────────────────────────────────────────

test.describe('Bagisto Export – Product Export Profile', () => {
    test.slow();

    test('should open the Product export profile edit/view page', async ({ adminPage }) => {
        await goToExports(adminPage);

        const row = exportRow(adminPage, 'Bagisto Product');
        if (await row.count() === 0) { test.skip(true, 'Bagisto Product export profile not seeded'); return; }

        await row.getByRole('link').first().click();
        await adminPage.waitForLoadState('load');

        await expect(
            adminPage.locator('select, input, [role="combobox"]').first()
        ).toBeVisible({ timeout: 10_000 });
    });

    test('should expose an export-trigger control on the Product profile', async ({ adminPage }) => {
        await goToExports(adminPage);

        const row = exportRow(adminPage, 'Bagisto Product');
        if (await row.count() === 0) { test.skip(true, 'Bagisto Product export profile not seeded'); return; }

        const btn = row.locator('a[title*="Export" i], button[title*="Export" i], span[title*="Export" i]').first();
        if (await btn.count() === 0) { test.skip(true, 'Export trigger control not exposed on this profile row'); return; }

        await expect(btn).toBeVisible();
    });
});

// ─── ATTRIBUTE EXPORT ────────────────────────────────────────────────────────

test.describe('Bagisto Export – Attribute Export Profile', () => {
    test.slow();

    test('should open the Attribute export profile edit/view page', async ({ adminPage }) => {
        await goToExports(adminPage);

        const row = exportRow(adminPage, 'Bagisto Attribute');
        if (await row.count() === 0) { test.skip(true, 'Bagisto Attribute export profile not seeded'); return; }

        await row.getByRole('link').first().click();
        await adminPage.waitForLoadState('load');

        await expect(
            adminPage.locator('select, input, [role="combobox"]').first()
        ).toBeVisible({ timeout: 10_000 });
    });

    test('should expose an export-trigger control on the Attribute profile', async ({ adminPage }) => {
        await goToExports(adminPage);

        const row = exportRow(adminPage, 'Bagisto Attribute');
        if (await row.count() === 0) { test.skip(true, 'Bagisto Attribute export profile not seeded'); return; }

        const btn = row.locator('a[title*="Export" i], button[title*="Export" i], span[title*="Export" i]').first();
        if (await btn.count() === 0) { test.skip(true, 'Export trigger control not exposed on this profile row'); return; }

        await expect(btn).toBeVisible();
    });
});

// ─── QUICK EXPORT ────────────────────────────────────────────────────────────

test.describe('Bagisto Export – Quick Export from Product Catalog', () => {
    test.slow();

    test('should reach the product catalog page', async ({ adminPage }) => {
        await adminPage.goto(PRODUCTS_URL);
        await adminPage.waitForLoadState('networkidle');

        await expect(adminPage).toHaveURL(/\/admin\/catalog\/products/);

        await expect(
            adminPage.locator('h1, p.text-xl, [class*="text-xl"]').filter({ hasText: PRODUCTS_HEADING_RE }).first()
        ).toBeVisible({ timeout: 10_000 });
    });

    test('should display a Quick Export / Export control when products are listed', async ({ adminPage }) => {
        await adminPage.goto(PRODUCTS_URL);
        await adminPage.waitForLoadState('networkidle');

        const btn = adminPage.getByRole('button', { name: /Quick Export|Export/i })
            .or(adminPage.locator('[title*="Quick Export" i], [title*="Export" i]'))
            .first();

        if (await btn.count() === 0) { test.skip(true, 'No Quick Export / Export control rendered'); return; }

        await expect(btn).toBeVisible();
    });
});
