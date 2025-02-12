<?php

use Webkul\Core\Models\Locale;

it('should not display the locale list if does not have permission', function () {
    $this->loginWithPermissions();

    $this->get(route('admin.settings.locales.index'))
        ->assertSeeText('Unauthorized');
});

it('should display the locale list if have permission', function () {
    $this->loginWithPermissions(permissions: ['settings', 'settings.locales']);

    $this->get(route('admin.settings.locales.index'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.settings.locales.index.title'));
});

it('should not create the locale if does not have permission', function () {
    $this->loginWithPermissions();

    $this->post(route('admin.settings.locales.store'))
        ->assertSeeText('Unauthorized');
});

it('should create the locale if has permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard', 'settings.locales.create']);

    $response = $this->post(route('admin.settings.locales.store', [
        'code'   => 'zh_Hans_CN',
        'status' => 1,
    ]));

    $response->assertStatus(200);

    $this->assertDatabaseHas($this->getFullTableName(Locale::class), [
        'code'   => 'zh_Hans_CN',
        'status' => 1,
    ]);
});

it('should not return the locale json for edit if does not have permission', function () {
    $this->loginWithPermissions();
    $locale = Locale::first();

    $this->get(route('admin.settings.locales.edit', ['id' => $locale->id]))
        ->assertSeeText('Unauthorized');
});

it('should return the locale json for edit if have permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard', 'settings.locales.edit']);
    $locale = Locale::factory()->create();

    $this->get(route('admin.settings.locales.edit', $locale->id))
        ->assertOk()
        ->assertJsonFragment($locale->toArray());
});

it('should not be able to delete locale if does not have permission', function () {
    $this->loginWithPermissions();
    $locale = Locale::first();

    $this->delete(route('admin.settings.locales.delete', ['id' => $locale->id]))
        ->assertSeeText('Unauthorized');

    $this->assertDatabaseHas($this->getFullTableName(Locale::class),
        ['id' => $locale->id]
    );
});

it('should be able to delete locale if has permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard', 'settings.locales.delete']);

    $locale = Locale::factory()->create([
        'code'   => 'zh_Hans_CN',
        'status' => 0,
    ]);

    $response = $this->delete(route('admin.settings.locales.delete', ['id' => $locale->id]));

    $response->assertStatus(200);

    $this->assertDatabaseMissing('locales', [
        'id'     => $locale->id,
        'code'   => 'zh_Hans_CN',
        'status' => 0,
    ]);
});
