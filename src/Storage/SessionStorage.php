<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Storage;

use Illuminate\Support\Facades\Session;
use ShamarKellman\FlexCart\Contracts\CartStorageInterface;

class SessionStorage implements CartStorageInterface
{
    public function get(string $key): ?array
    {
        return Session::get($this->getKey($key));
    }

    public function put(string $key, array $value): void
    {
        Session::put($this->getKey($key), $value);
    }

    public function forget(string $key): void
    {
        Session::forget($this->getKey($key));
    }

    public function has(string $key): bool
    {
        return Session::has($this->getKey($key));
    }

    protected function getKey(string $key): string
    {
        return config()->string('flex-cart.storage.session_key', 'shopping_cart').'.'.$key;
    }
}
