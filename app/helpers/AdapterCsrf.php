<?php

declare(strict_types=1);

class AdapterCsrf
{
    public static function token($session): string
    {
        $token = $session->get('adapter_csrf');
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            $session->set('adapter_csrf', $token);
        }
        return $token;
    }

    public static function valid($session, ?string $token): bool
    {
        $expected = (string)$session->get('adapter_csrf');
        return $expected !== '' && is_string($token) && hash_equals($expected, $token);
    }
}
