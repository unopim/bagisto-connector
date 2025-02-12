<?php

namespace Webkul\BagistoPlugin\Enums\Services;

enum ContentType: string
{
    case JSON = 'application/json';

    case MULTIPART = 'multipart/form-data';
}
