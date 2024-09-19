<?php

namespace Src\Encoder;

class UrlOpenSslEncoder
{
    /**
     * @var string
     */
    private $key;
    /**
     * @var string
     */
    private $algorithm;

    public function __construct(string $key, string $algorithm)
    {
        $this->key = md5($key);
        $this->algorithm = $algorithm;
    }

    public function encode(string $value): string
    {
        $ivSize = openssl_cipher_iv_length($this->algorithm);
        $iv = openssl_random_pseudo_bytes($ivSize);
        if (false === $ivSize || false === $iv) {
            throw new \RuntimeException('IV generation failed');
        }

        $encrypted = openssl_encrypt($value, $this->algorithm, $this->key, OPENSSL_RAW_DATA, $iv);
        return rtrim(strtr(base64_encode($iv . $encrypted), '+/', '-_'), '=');
    }

    public function getHash(string $tail): string
    {
        return md5($tail . $this->key);
    }

    public function decode(string $value): string
    {
        $value = base64_decode(strtr($value, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($value)) % 4));
        $ivSize = openssl_cipher_iv_length($this->algorithm);
        $iv = substr($value, 0, $ivSize);

        return openssl_decrypt(substr($value, $ivSize), $this->algorithm, $this->key, OPENSSL_RAW_DATA, $iv);

    }

    /**
     * @return mixed
     */
    public function key()
    {
        return $this->key;
    }
}