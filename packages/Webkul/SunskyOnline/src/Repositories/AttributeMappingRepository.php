<?php

namespace Webkul\SunskyOnline\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\SunskyOnline\Models\AttributeMapping;

class AttributeMappingRepository extends Repository
{
    public function model(): string
    {
        return AttributeMapping::class;
    }

    public function getMappingBySection($section)
    {
        return cache()->remember("attribute_mapping_section_{$section}", 60, function () use ($section) {
            $item = $this->model->where('section', $section)->first();
            if ($item) {
                return [
                    'mapped_value' => $item->mapped_value,
                    'fixed_value'  => $item->fixed_value,
                ];
            }

            return [];
        });
    }
}
