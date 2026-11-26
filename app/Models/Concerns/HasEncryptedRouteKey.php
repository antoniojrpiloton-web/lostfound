<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

trait HasEncryptedRouteKey
{
    public function getRouteKey(): mixed
    {
        $key = $this->getKey();
        if ($key === null) {
            return null;
        }

        $appKey = $this->routeEncryptionKey();
        $payload = $this->getTable().':'.$key;
        $iv = substr(hash_hmac('sha256', 'iv:'.$payload, $appKey, true), 0, 16);
        $ciphertext = openssl_encrypt($payload, 'AES-256-CBC', $appKey, OPENSSL_RAW_DATA, $iv);

        if ($ciphertext === false) {
            return (string) $key;
        }

        $mac = substr(hash_hmac('sha256', $this->getTable().'|'.$iv.$ciphertext, $appKey, true), 0, 16);

        return rtrim(strtr(base64_encode($iv.$mac.$ciphertext), '+/', '-_'), '=');
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        if ($field !== null) {
            return parent::resolveRouteBinding($value, $field);
        }

        if (! is_string($value) || $value === '' || ctype_digit($value)) {
            return null;
        }

        $padded = strtr($value, '-_', '+/');
        $mod = strlen($padded) % 4;
        if ($mod !== 0) {
            $padded .= str_repeat('=', 4 - $mod);
        }

        $raw = base64_decode($padded, true);
        if ($raw === false || strlen($raw) <= 32) {
            return null;
        }

        $iv = substr($raw, 0, 16);
        $mac = substr($raw, 16, 16);
        $ciphertext = substr($raw, 32);

        $appKey = $this->routeEncryptionKey();
        $expectedMac = substr(hash_hmac('sha256', $this->getTable().'|'.$iv.$ciphertext, $appKey, true), 0, 16);

        if (! hash_equals($expectedMac, $mac)) {
            return null;
        }

        $decrypted = openssl_decrypt($ciphertext, 'AES-256-CBC', $appKey, OPENSSL_RAW_DATA, $iv);
        if ($decrypted === false) {
            return null;
        }

        $prefix = $this->getTable().':';
        if (! str_starts_with($decrypted, $prefix)) {
            return null;
        }

        $id = substr($decrypted, strlen($prefix));
        if ($id === '' || ! ctype_digit($id)) {
            return null;
        }

        return $this->whereKey((int) $id)->first();
    }

    protected function routeEncryptionKey(): string
    {
        $configured = (string) config('app.key', 'boracay-lf-default-key');

        if (str_starts_with($configured, 'base64:')) {
            $decoded = base64_decode(substr($configured, 7), true);
            if ($decoded !== false) {
                $configured = $decoded;
            }
        }

        return hash('sha256', 'blf-route-key:'.$configured, true);
    }
}
