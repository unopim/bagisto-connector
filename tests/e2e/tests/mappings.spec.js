const { test, expect } = require('../utils/fixtures');

const CREDENTIAL_ID = 1;

const URLS = {
    attributeMapping:    `admin/bagisto/attributes-mapping/${CREDENTIAL_ID}`,
    categoryMapping:     `admin/bagisto/category-fields-mapping/${CREDENTIAL_ID}`,
    channelMapping:      `admin/bagisto/channel-mapping/${CREDENTIAL_ID}`,
    localeMapping:       `admin/bagisto/locale-mapping/${CREDENTIAL_ID}`,
};

async function goToPage(adminPage, url) {
    await adminPage.goto(url);
    await adminPage.waitForLoadState('networkidle');
}

async function pageNotFound(adminPage) {
    return (await adminPage.getByText(/not found|404|no credential/i).count()) > 0;
}

// ─── ATTRIBUTE MAPPINGS ───────────────────────────────────────────────────────

test.describe('Bagisto Attribute Mappings', () => {
    test.slow();

    test('should load the Attribute Mappings page heading', async ({ adminPage }) => {
        await goToPage(adminPage, URLS.attributeMapping);
        if (await pageNotFound(adminPage)) { test.skip(); return; }

        // Use the top-level page heading (exact, not the section sub-headings)
        await expect(
            adminPage.locator('p.text-xl, h1').filter({ hasText: 'Attribute Mappings' }).first()
        ).toBeVisible();
    });

    test('should display the Standard Attribute Mappings section', async ({ adminPage }) => {
        await goToPage(adminPage, URLS.attributeMapping);
        if (await pageNotFound(adminPage)) { test.skip(); return; }

        await expect(
            adminPage.locator('p.text-base, h2, h3').filter({ hasText: 'Standard Attribute Mappings' }).first()
        ).toBeVisible();
    });

    test('should display the Additional Attribute Mappings section', async ({ adminPage }) => {
        await goToPage(adminPage, URLS.attributeMapping);
        if (await pageNotFound(adminPage)) { test.skip(); return; }

        await expect(
            adminPage.locator('p.text-base, h2, h3').filter({ hasText: 'Additional Attribute Mappings' }).first()
        ).toBeVisible();
    });

    test('should display the Configurable Attribute Mappings section', async ({ adminPage }) => {
        await goToPage(adminPage, URLS.attributeMapping);
        if (await pageNotFound(adminPage)) { test.skip(); return; }

        await expect(
            adminPage.locator('p.text-base, h2, h3').filter({ hasText: 'Configurable Attribute Mappings' }).first()
        ).toBeVisible();
    });

    test('should show the Save button on the Attribute Mappings page', async ({ adminPage }) => {
        await goToPage(adminPage, URLS.attributeMapping);
        if (await pageNotFound(adminPage)) { test.skip(); return; }

        await expect(
            adminPage.getByRole('button', { name: /Save/i }).first()
        ).toBeVisible();
    });

    test('should save Attribute Mapping and show success message', async ({ adminPage }) => {
        await goToPage(adminPage, URLS.attributeMapping);
        if (await pageNotFound(adminPage)) { test.skip(); return; }

        await adminPage.getByRole('button', { name: /Save/i }).first().click();

        await expect(
            adminPage.getByText(/saved successfully/i).first()
        ).toBeVisible({ timeout: 20000 });
    });
});

// ─── CATEGORY FIELDS MAPPING ──────────────────────────────────────────────────

test.describe('Bagisto Category Fields Mapping', () => {
    test.slow();

    test('should load the Category Fields Mapping page', async ({ adminPage }) => {
        await goToPage(adminPage, URLS.categoryMapping);
        if (await pageNotFound(adminPage)) { test.skip(); return; }

        await expect(
            adminPage.locator('p.text-xl, h1').filter({ hasText: /Category Fields/i }).first()
        ).toBeVisible();
    });

    test('should show the Save button on Category Fields Mapping page', async ({ adminPage }) => {
        await goToPage(adminPage, URLS.categoryMapping);
        if (await pageNotFound(adminPage)) { test.skip(); return; }

        await expect(
            adminPage.getByRole('button', { name: /Save/i }).first()
        ).toBeVisible();
    });

    test('should save Category Fields Mapping and show success message', async ({ adminPage }) => {
        await goToPage(adminPage, URLS.categoryMapping);
        if (await pageNotFound(adminPage)) { test.skip(); return; }

        await adminPage.getByRole('button', { name: /Save/i }).first().click();

        await expect(
            adminPage.getByText(/saved successfully/i).first()
        ).toBeVisible({ timeout: 20000 });
    });
});

// ─── CHANNEL MAPPING ─────────────────────────────────────────────────────────

test.describe('Bagisto Channel Mapping', () => {
    test.slow();

    test('should load the Channel Mapping page', async ({ adminPage }) => {
        await goToPage(adminPage, URLS.channelMapping);
        if (await pageNotFound(adminPage)) { test.skip(); return; }

        await expect(
            adminPage.locator('p.text-xl, h1').filter({ hasText: /Channel/i }).first()
        ).toBeVisible();
    });

    test('should show at least one select/dropdown on Channel Mapping page', async ({ adminPage }) => {
        await goToPage(adminPage, URLS.channelMapping);
        if (await pageNotFound(adminPage)) { test.skip(); return; }

        await expect(
            adminPage.locator('select, [role="listbox"]').first()
        ).toBeVisible({ timeout: 10000 });
    });

    test('should save Channel Mapping and show success message', async ({ adminPage }) => {
        await goToPage(adminPage, URLS.channelMapping);
        if (await pageNotFound(adminPage)) { test.skip(); return; }

        await adminPage.getByRole('button', { name: /Save/i }).first().click();

        await expect(
            adminPage.getByText(/saved successfully/i).first()
        ).toBeVisible({ timeout: 20000 });
    });
});

// ─── LOCALE MAPPING ──────────────────────────────────────────────────────────

test.describe('Bagisto Locale Mapping', () => {
    test.slow();

    test('should load the Locale Mapping page', async ({ adminPage }) => {
        await goToPage(adminPage, URLS.localeMapping);
        if (await pageNotFound(adminPage)) { test.skip(); return; }

        await expect(
            adminPage.locator('p.text-xl, h1').filter({ hasText: /Locale/i }).first()
        ).toBeVisible();
    });

    test('should show at least one select/dropdown on Locale Mapping page', async ({ adminPage }) => {
        await goToPage(adminPage, URLS.localeMapping);
        if (await pageNotFound(adminPage)) { test.skip(); return; }

        await expect(
            adminPage.locator('select, [role="listbox"]').first()
        ).toBeVisible({ timeout: 10000 });
    });

    test('should save Locale Mapping and show success message', async ({ adminPage }) => {
        await goToPage(adminPage, URLS.localeMapping);
        if (await pageNotFound(adminPage)) { test.skip(); return; }

        await adminPage.getByRole('button', { name: /Save/i }).first().click();

        await expect(
            adminPage.getByText(/saved successfully/i).first()
        ).toBeVisible({ timeout: 20000 });
    });
});
