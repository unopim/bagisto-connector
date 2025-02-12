<?php

namespace Webkul\BagistoPlugin\Services;

final class Headers
{
    /**
     * Creates a new Headers value object.
     *
     * @param  array<string, string>  $headers
     */
    private function __construct(private readonly array $headers) {}

    /**
     * Creates a new Headers value object
     */
    public static function create(): self
    {
        return new self([]);
    }

    /**
     * Creates a new Headers value object with the given API token.
     */
    public static function withAuthorization($token): self
    {
        return new self([
            'Authorization' => "Bearer {$token}",
        ]);
    }

    /**
     * Creates a new Headers value object, with the given content type, and the existing headers.
     */
    public function withContentType(): self
    {
        return new self([
            ...$this->headers,
            'accept' => 'application/json',
        ]);
    }

    /**
     * @return array<string, string> $headers
     */
    public function toArray(): array
    {
        return $this->headers;
    }
}
