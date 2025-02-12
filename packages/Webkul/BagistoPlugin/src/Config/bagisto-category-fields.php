<?php

return [
    [
        'code'     => 'slug',
        'name'     => 'bagisto_plugin::app.bagisto-plugin.bagisto-category-fields.field-name.slug',
        'type'     => 'text',
        'unique'   => true,
        'required' => true,
        'title'    => 'bagisto_plugin::app.bagisto-plugin.bagisto-category-fields.title.slug',
    ], [
        'code'     => 'name',
        'name'     => 'bagisto_plugin::app.bagisto-plugin.bagisto-category-fields.field-name.name',
        'type'     => 'text',
        'required' => true,
        'title'    => 'bagisto_plugin::app.bagisto-plugin.bagisto-category-fields.title.name',
    ], [
        'code'     => 'status',
        'name'     => 'bagisto_plugin::app.bagisto-plugin.bagisto-category-fields.field-name.status',
        'type'     => 'boolean',
        'required' => false,
        'title'    => 'bagisto_plugin::app.bagisto-plugin.bagisto-category-fields.title.status',
    ], [
        'code'     => 'description',
        'name'     => 'bagisto_plugin::app.bagisto-plugin.bagisto-category-fields.field-name.description',
        'type'     => 'textarea',
        'required' => true,
        'title'    => 'bagisto_plugin::app.bagisto-plugin.bagisto-category-fields.title.description',
    ], [
        'code'     => 'meta_title',
        'name'     => 'bagisto_plugin::app.bagisto-plugin.bagisto-category-fields.field-name.meta_title',
        'type'     => 'textarea',
        'required' => false,
        'title'    => 'bagisto_plugin::app.bagisto-plugin.bagisto-category-fields.title.meta_title',
    ], [
        'code'     => 'meta_keywords',
        'name'     => 'bagisto_plugin::app.bagisto-plugin.bagisto-category-fields.field-name.meta_keywords',
        'type'     => 'textarea',
        'required' => false,
        'title'    => 'bagisto_plugin::app.bagisto-plugin.bagisto-category-fields.title.meta_keywords',
    ], [
        'code'     => 'meta_description',
        'name'     => 'bagisto_plugin::app.bagisto-plugin.bagisto-category-fields.field-name.meta_description',
        'type'     => 'textarea',
        'required' => false,
        'title'    => 'bagisto_plugin::app.bagisto-plugin.bagisto-category-fields.title.meta_description',
    ], [
        'code'     => 'position',
        'name'     => 'bagisto_plugin::app.bagisto-plugin.bagisto-category-fields.field-name.position',
        'type'     => 'text',
        'required' => true,
        'title'    => 'bagisto_plugin::app.bagisto-plugin.bagisto-category-fields.title.position',
    ], [
        'code'     => 'display_mode',
        'name'     => 'bagisto_plugin::app.bagisto-plugin.bagisto-category-fields.field-name.display_mode',
        'type'     => 'select',
        'required' => true,
        'title'    => 'bagisto_plugin::app.bagisto-plugin.bagisto-category-fields.title.display_mode',
    ], [
        'code'     => 'logo_path',
        'name'     => 'bagisto_plugin::app.bagisto-plugin.bagisto-category-fields.field-name.logo_path',
        'type'     => 'image',
        'required' => false,
        'title'    => 'bagisto_plugin::app.bagisto-plugin.bagisto-category-fields.title.logo_path',
    ], [
        'code'     => 'banner_path',
        'name'     => 'bagisto_plugin::app.bagisto-plugin.bagisto-category-fields.field-name.banner_path',
        'type'     => 'image',
        'required' => false,
        'title'    => 'bagisto_plugin::app.bagisto-plugin.bagisto-category-fields.title.banner_path',
    ],
];
