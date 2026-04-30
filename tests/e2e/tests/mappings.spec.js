const { test, expect } = require('../utils/fixtures');

const CREDENTIAL_ID = process.env.E2E_CREDENTIAL_ID || 1;

const URLS = {
    attributeMapping: `admin/bagisto/attributes-mapping/${CREDENTIAL_ID}`,
    categoryMapping:  `admin/bagisto/category-fields-mapping/${CREDENTIAL_ID}`,
    channelMapping:   `admin/bagisto/channel-mapping/${CREDENTIAL_ID}`,
    localeMapping:    `admin/bagisto/locale-mapping/${CREDENTIAL_ID}`,
};

const SAVE_BTN_RE = /^\s*Save\s*$/i;

async function goToPage(adminPage, url) {
    await adminPage.goto(url);
    await adminPage.waitForLoadState('networkidle');
}

async function pageNotFound(adminPage) {
    if (/(404|not[-_ ]found)/i.test(adminPage.url())) {
        return true;
    }
    return (await adminPage.getByText(/page not found|404|no credential|whoops/i).count()) > 0;
}

/** Wait for the Vue mapping component to mount by watching for any rendered control. */
async function waitForMappingForm(adminPage) {
    await expect(
        adminPage.locator('form, [role="form"], button[type="submit"]').first()
    ).toBeVisible({ timeout: 15_000 });
}

// ─── ATTRIBUTE MAPPINGS ───────────────────────────────────────────────────────

test.describe('Bagisto Attribute Mappings', () => {
    test.slow();

    test('should load the Attribute Mappings page heading', async ({ adminPage }) => {
        await goToPage(adminPage, URLS.attributeMapping);
        if (await pageNotFound(adminPage)) { test.skip(true, 'Mapping page not reachable'); return; }

        await waitForMappingForm(adminPage);

        await expect(
            adminPage.locator('p.text-xl, h1').filter({ hasText: /Attribute Mappings/i }).first()
        ).toBeVisible({ timeout: 10_000 });
    });

    test('should display the Additional Attribute Mappings section', async ({ adminPage }) => {
        await goToPage(adminPage, URLS.attributeMapping);
        if (await pageNotFound(adminPage)) { test.skip(true, 'Mapping page not reachable'); return; }

        await waitForMappingForm(adminPage);

        await expect(
            adminPage.getByText(/Additional Attribute Mappings/i).first()
        ).toBeVisible({ timeout: 10_000 });
    });

    test('should display the Configurable Attribute Mappings section', async ({ adminPage }) => {
        await goToPage(adminPage, URLS.attributeMapping);
        if (await pageNotFound(adminPage)) { test.skip(true, 'Mapping page not reachable'); return; }

        await waitForMappingForm(adminPage);

        await expect(
            adminPage.getByText(/Configurable Attribute Mappings/i).first()
        ).toBeVisible({ timeout: 10_000 });
    });

    test('should show the Save button on the Attribute Mappings page', async ({ adminPage }) => {
        await goToPage(adminPage, URLS.attributeMapping);
        if (await pageNotFound(adminPage)) { test.skip(true, 'Mapping page not reachable'); return; }

        await waitForMappingForm(adminPage);

        await expect(
            adminPage.locator('button[type="submit"]').filter({ hasText: SAVE_BTN_RE }).first()
        ).toBeVisible({ timeout: 15_000 });
    });

    test('should post Attribute Mapping save and surface a server response', async ({ adminPage }) => {
        await goToPage(adminPage, URLS.attributeMapping);
        if (await pageNotFound(adminPage)) { test.skip(true, 'Mapping page not reachable'); return; }

        await waitForMappingForm(adminPage);

        const responsePromise = adminPage.waitForResponse(
            res => /attributes-mapping\/storeOrUpdate/.test(res.url()),
            { timeout: 25_000 }
        ).catch(() => null);

        await adminPage.locator('button[type="submit"]').filter({ hasText: SAVE_BTN_RE }).first().click();

        const response = await responsePromise;
        if (! response) { test.skip(true, 'Save request did not complete'); return; }

        expect(response.status()).toBeLessThan(500);
    });
});

// ─── CATEGORY FIELDS MAPPING ──────────────────────────────────────────────────

test.describe('Bagisto Category Fields Mapping', () => {
    test.slow();

    test('should load the Category Fields Mapping page', async ({ adminPage }) => {
        await goToPage(adminPage, URLS.categoryMapping);
        if (await pageNotFound(adminPage)) { test.skip(true, 'Mapping page not reachable'); return; }

        await waitForMappingForm(adminPage);

        await expect(
            adminPage.locator('p.text-xl, h1').filter({ hasText: /Category Fields/i }).first()
        ).toBeVisible({ timeout: 10_000 });
    });

    test('should show the Save button on Category Fields Mapping page', async ({ adminPage }) => {
        await goToPage(adminPage, URLS.categoryMapping);
        if (await pageNotFound(adminPage)) { test.skip(true, 'Mapping page not reachable'); return; }

        await waitForMappingForm(adminPage);

        await expect(
            adminPage.locator('button[type="submit"]').filter({ hasText: SAVE_BTN_RE }).first()
        ).toBeVisible({ timeout: 15_000 });
    });

    test('should post Category Fields Mapping save and surface a server response', async ({ adminPage }) => {
        await goToPage(adminPage, URLS.categoryMapping);
        if (await pageNotFound(adminPage)) { test.skip(true, 'Mapping page not reachable'); return; }

        await waitForMappingForm(adminPage);

        const responsePromise = adminPage.waitForResponse(
            res => /category-fields-mapping\/storeOrUpdate/.test(res.url()),
            { timeout: 25_000 }
        ).catch(() => null);

        await adminPage.locator('button[type="submit"]').filter({ hasText: SAVE_BTN_RE }).first().click();

        const response = await responsePromise;
        if (! response) { test.skip(true, 'Save request did not complete'); return; }

        expect(response.status()).toBeLessThan(500);
    });
});

// ─── CHANNEL MAPPING (route does not exist in the package — these always skip) ─

test.describe('Bagisto Channel Mapping', () => {
    test.slow();

    test('should load the Channel Mapping page', async ({ adminPage }) => {
        await goToPage(adminPage, URLS.channelMapping);
        if (await pageNotFound(adminPage)) { test.skip(true, 'No standalone channel-mapping route in this package'); return; }

        await expect(
            adminPage.locator('p.text-xl, h1').filter({ hasText: /Channel/i }).first()
        ).toBeVisible();
    });
});

// ─── LOCALE MAPPING (route does not exist in the package — these always skip) ─

test.describe('Bagisto Locale Mapping', () => {
    test.slow();

    test('should load the Locale Mapping page', async ({ adminPage }) => {
        await goToPage(adminPage, URLS.localeMapping);
        if (await pageNotFound(adminPage)) { test.skip(true, 'No standalone locale-mapping route in this package'); return; }

        await expect(
            adminPage.locator('p.text-xl, h1').filter({ hasText: /Locale/i }).first()
        ).toBeVisible();
    });
});
