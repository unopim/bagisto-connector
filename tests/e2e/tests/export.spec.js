const { test, expect } = require('../utils/fixtures');

const EXPORTS_URL = 'admin/exports';

async function goTo(adminPage, url) {
    await adminPage.goto(url);
    await adminPage.waitForLoadState('networkidle');
}

test.describe('Bagisto Export – Listing', () => {
    test.slow();

    test('should load the Exports listing page', async ({ adminPage }) => {
        await goTo(adminPage, EXPORTS_URL);

        // The package contributes Bagisto exporter types to UnoPim's export config;
        // the canonical proof is that /admin/exports renders for an admin without redirecting.
        await expect(adminPage).toHaveURL(/\/admin\/exports/);

        await expect(
            adminPage.locator('main, #app, [class*="page-content"], [class*="content"]').first()
        ).toBeVisible({ timeout: 10_000 });
    });
});
