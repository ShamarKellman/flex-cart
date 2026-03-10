<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Contracts;

interface CartStorageInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function get(string $key): ?array;

    /**
     * @param  array<string, mixed>  $value
     */
    public function put(string $key, array $value): void;

    public function forget(string $key): void;

    public function has(string $key): bool;
}
