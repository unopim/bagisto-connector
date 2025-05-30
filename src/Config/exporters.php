<?php

return [
    'bagisto_categories' => [
        'title'    => 'bagisto::app.exporters.bagisto.category',
        'exporter' => 'Webkul\Bagisto\Helpers\Exporters\Category\Exporter',
        'source'   => 'Webkul\Category\Repositories\CategoryRepository',
        'filters'  => [
            'fields' => [
                [
                    'name'       => 'credentials',
                    'title'      => 'bagisto::app.exporters.bagisto.credentials',
                    'required'   => true,
                    'validation' => 'required',
                    'type'       => 'select',
                    'async'      => true,
                    'track_by'   => 'id',
                    'label_by'   => 'shop_url',
                    'list_route' => 'bagisto.credential.fetch-all',
                ], [
                    'name'       => 'channel',
                    'title'      => 'bagisto::app.exporters.bagisto.channel',
                    'required'   => true,
                    'validation' => 'required',
                    'type'       => 'select',
                    'async'      => true,
                    'track_by'   => 'code',
                    'label_by'   => 'name',
                    'list_route' => 'bagisto.channel.fetch-all',
                ], [
                    'name'       => 'locale',
                    'title'      => 'bagisto::app.exporters.bagisto.locale',
                    'required'   => true,
                    'type'       => 'multiselect',
                    'validation' => 'required',
                    'async'      => true,
                    'track_by'   => 'code',
                    'label_by'   => 'name',
                    'list_route' => 'bagisto.locale.fetch-all',
                ], [
                    'name'     => 'code',
                    'title'    => 'bagisto::app.exporters.bagisto.code',
                    'required' => false,
                    'type'     => 'textarea',
                ],
            ],
        ],
    ],

    'bagisto_attribute' => [
        'title'    => 'bagisto::app.exporters.bagisto.attribute',
        'exporter' => 'Webkul\Bagisto\Helpers\Exporters\Attribute\Exporter',
        'source'   => 'Webkul\Attribute\Repositories\AttributeRepository',
        'filters'  => [
            'fields' => [
                [
                    'name'       => 'credentials',
                    'title'      => 'bagisto::app.exporters.bagisto.credentials',
                    'required'   => true,
                    'validation' => 'required',
                    'type'       => 'select',
                    'async'      => true,
                    'track_by'   => 'id',
                    'label_by'   => 'shop_url',
                    'list_route' => 'bagisto.credential.fetch-all',
                ], [
                    'name'       => 'channel',
                    'title'      => 'bagisto::app.exporters.bagisto.channel',
                    'required'   => true,
                    'validation' => 'required',
                    'type'       => 'select',
                    'async'      => true,
                    'track_by'   => 'code',
                    'label_by'   => 'name',
                    'list_route' => 'bagisto.channel.fetch-all',
                ], [
                    'name'       => 'locale',
                    'title'      => 'bagisto::app.exporters.bagisto.locale',
                    'required'   => true,
                    'type'       => 'multiselect',
                    'validation' => 'required',
                    'async'      => true,
                    'track_by'   => 'code',
                    'label_by'   => 'name',
                    'list_route' => 'bagisto.locale.fetch-all',
                ], [
                    'name'     => 'code',
                    'title'    => 'bagisto::app.exporters.bagisto.code',
                    'required' => false,
                    'type'     => 'textarea',
                ],
            ],
        ],
    ],

    'bagisto_attribute_families' => [
        'title'    => 'bagisto::app.exporters.bagisto.attribute-families',
        'exporter' => 'Webkul\Bagisto\Helpers\Exporters\AttributeFamily\Exporter',
        'source'   => 'Webkul\Attribute\Repositories\AttributeFamilyRepository',
        'filters'  => [
            'fields' => [
                [
                    'name'       => 'credentials',
                    'title'      => 'bagisto::app.exporters.bagisto.credentials',
                    'required'   => true,
                    'validation' => 'required',
                    'type'       => 'select',
                    'async'      => true,
                    'track_by'   => 'id',
                    'label_by'   => 'shop_url',
                    'list_route' => 'bagisto.credential.fetch-all',
                ], [
                    'name'       => 'channel',
                    'title'      => 'bagisto::app.exporters.bagisto.channel',
                    'required'   => true,
                    'validation' => 'required',
                    'type'       => 'select',
                    'async'      => true,
                    'track_by'   => 'code',
                    'label_by'   => 'name',
                    'list_route' => 'bagisto.channel.fetch-all',
                ], [
                    'name'       => 'locale',
                    'title'      => 'bagisto::app.exporters.bagisto.locale',
                    'required'   => true,
                    'type'       => 'multiselect',
                    'validation' => 'required',
                    'async'      => true,
                    'track_by'   => 'code',
                    'label_by'   => 'name',
                    'list_route' => 'bagisto.locale.fetch-all',
                ], [
                    'name'     => 'code',
                    'title'    => 'bagisto::app.exporters.bagisto.code',
                    'required' => false,
                    'type'     => 'textarea',
                ],
            ],
        ],
    ],

    'bagisto_product' => [
        'title'    => 'bagisto::app.exporters.bagisto.product',
        'exporter' => 'Webkul\Bagisto\Helpers\Exporters\Product\Exporter',
        'source'   => 'Webkul\Product\Repositories\ProductRepository',
        'filters'  => [
            'fields' => [
                [
                    'name'       => 'credentials',
                    'title'      => 'bagisto::app.exporters.bagisto.credentials',
                    'required'   => true,
                    'validation' => 'required',
                    'type'       => 'select',
                    'async'      => true,
                    'track_by'   => 'id',
                    'label_by'   => 'shop_url',
                    'list_route' => 'bagisto.credential.fetch-all',
                ], [
                    'name'       => 'channel',
                    'title'      => 'bagisto::app.exporters.bagisto.channel',
                    'required'   => true,
                    'validation' => 'required',
                    'type'       => 'multiselect',
                    'async'      => true,
                    'track_by'   => 'code',
                    'label_by'   => 'name',
                    'list_route' => 'bagisto.channel.fetch-all',
                ], [
                    'name'       => 'locale',
                    'title'      => 'bagisto::app.exporters.bagisto.locale',
                    'required'   => true,
                    'type'       => 'multiselect',
                    'validation' => 'required',
                    'async'      => true,
                    'track_by'   => 'code',
                    'label_by'   => 'name',
                    'list_route' => 'bagisto.locale.fetch-all',
                ], [
                    'name'       => 'family',
                    'title'      => 'bagisto::app.exporters.bagisto.family',
                    'required'   => false,
                    'type'       => 'multiselect',
                    'async'      => true,
                    'track_by'   => 'code',
                    'label_by'   => 'name',
                    'list_route' => 'bagisto.family.fetch-all',
                ], [
                    'name'       => 'type',
                    'title'      => 'bagisto::app.exporters.bagisto.type',
                    'required'   => false,
                    'type'       => 'multiselect',
                    'async'      => true,
                    'track_by'   => 'id',
                    'label_by'   => 'label',
                    'list_route' => 'bagisto.type.fetch-all',
                ], [
                    'name'       => 'status',
                    'title'      => 'bagisto::app.exporters.bagisto.status',
                    'required'   => false,
                    'type'       => 'select',
                    'options'    => [
                        [
                            'value' => 'all',
                            'label' => 'bagisto::app.exporters.bagisto.all',
                        ], [
                            'value' => 't',
                            'label' => 'bagisto::app.exporters.bagisto.true',
                        ], [
                            'value' => 'f',
                            'label' => 'bagisto::app.exporters.bagisto.false',
                        ],
                    ],
                ], [
                    'name'     => 'with_media',
                    'title'    => 'bagisto::app.exporters.bagisto.with_media',
                    'required' => false,
                    'type'     => 'boolean',
                ], [
                    'name'     => 'sku',
                    'title'    => 'bagisto::app.exporters.bagisto.sku',
                    'required' => false,
                    'type'     => 'textarea',
                ],
            ],
        ],
    ],
];
