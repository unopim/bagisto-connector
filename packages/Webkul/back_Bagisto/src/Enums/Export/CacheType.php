<?php

namespace Webkul\Bagisto\Enums\Export;

enum CacheType: string
{
    case CREDENTIAL = 'bagisto_credential';

    case CATEGORY_FIELD_MAPPING = 'bagisto_category_field_mapping';

    case UNOPIM_CATEGORY_FIELDS = 'bagisto_unopim_category_fields';

    case ATTRIBUTE_MAPPING = 'bagisto_attribute_mapping';

    case BAGISTO_API_HTTP = 'bagisto_API_HTTP';

    case JOB_FILTERS = 'job_filters';
}
