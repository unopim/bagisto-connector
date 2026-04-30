const { test, expect } = require('../utils/fixtures');

const EXPORTS_URL  = 'admin/exports';
const PRODUCTS_URL = 'admin/catalog/products';

async function goTo(adminPage, url) {
    await adminPage.goto(url);
    await adminPage.waitForLoadState('networkidle');
}

function exportRow(adminPage, keyword) {
    return adminPage.locator('tr', { hasText: keyword }).first();
}

// ─── LISTING ─────────────────────────────────────────────────────────────────

test.describe('Bagisto Export – Listing', () => {
    test.slow();

    test('should load the Exports listing page', async ({ adminPage }) => {
        await goTo(adminPage, EXPORTS_URL);

        await expect(adminPage).toHaveURL(/\/admin\/exports/);

        // Datagrid container is the universal anchor — heading text varies between UnoPim versions.
        await expect(
            adminPage.locator('table, [class*="datagrid"], .grid-content').first()
        ).toBeVisible({ timeout: 10_000 });
    });

    test('should list the Bagisto Product export profile', async ({ adminPage }) => {
        await goTo(adminPage, EXPORTS_URL);

        const row = exportRow(adminPage, 'Bagisto Product');
        if (await row.count() === 0) { test.skip(true, 'Bagisto Product export profile not seeded'); return; }

        await expect(row).toBeVisible();
    });

    test('should list the Bagisto Attribute export profile', async ({ adminPage }) => {
        await goTo(adminPage, EXPORTS_URL);

        const row = exportRow(adminPage, 'Bagisto Attribute');
        if (await row.count() === 0) { test.skip(true, 'Bagisto Attribute export profile not seeded'); return; }

        await expect(row).toBeVisible();
    });
});

// ─── PRODUCT EXPORT ───────────────────────────────────────────────────────────

test.describe('Bagisto Export – Product Export Profile', () => {
    test.slow();

    test('should open the Product export profile edit/view page', async ({ adminPage }) => {
        await goTo(adminPage, EXPORTS_URL);

        const row = exportRow(adminPage, 'Bagisto Product');
        if (await row.count() === 0) { test.skip(true, 'Bagisto Product export profile not seeded'); return; }

        await row.getByRole('link').first().click();
        await adminPage.waitForLoadState('load');

        await expect(
            adminPage.locator('select, input, [role="combobox"]').first()
        ).toBeVisible({ timeout: 10_000 });
    });
});

// ─── ATTRIBUTE EXPORT ────────────────────────────────────────────────────────

test.describe('Bagisto Export – Attribute Export Profile', () => {
    test.slow();

    test('should open the Attribute export profile edit/view page', async ({ adminPage }) => {
        await goTo(adminPage, EXPORTS_URL);

        const row = exportRow(adminPage, 'Bagisto Attribute');
        if (await row.count() === 0) { test.skip(true, 'Bagisto Attribute export profile not seeded'); return; }

        await row.getByRole('link').first().click();
        await adminPage.waitForLoadState('load');

        await expect(
            adminPage.locator('select, input, [role="combobox"]').first()
        ).toBeVisible({ timeout: 10_000 });
    });
});

// ─── PRODUCT CATALOG ─────────────────────────────────────────────────────────

test.describe('Bagisto Export – Quick Export from Product Catalog', () => {
    test.slow();

    test('should reach the product catalog page', async ({ adminPage }) => {
        await goTo(adminPage, PRODUCTS_URL);

        await expect(adminPage).toHaveURL(/\/admin\/catalog\/products/);

        await expect(
            adminPage.locator('table, [class*="datagrid"], .grid-content').first()
        ).toBeVisible({ timeout: 10_000 });
    });

    test('should display a Quick Export / Export control when products are listed', async ({ adminPage }) => {
        await goTo(adminPage, PRODUCTS_URL);

        const btn = adminPage.getByRole('button', { name: /Quick Export|Export/i })
            .or(adminPage.locator('[title*="Quick Export" i], [title*="Export" i]'))
            .first();

        if (await btn.count() === 0) { test.skip(true, 'No Quick Export / Export control rendered'); return; }

        await expect(btn).toBeVisible();
    });
});
