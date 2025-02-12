<?php

namespace Webkul\BagistoPlugin\Enums\Services;

enum MethodType: string
{
    case GET = 'get';

    case POST = 'post';

    case PUT = 'put';

    case DELETE = 'delete';
}
