<?php

namespace Nnt\Core;

class Rsa
{
    const PADDING_LENGTH = [
        OPENSSL_PKCS1_OAEP_PADDING => 41,
        OPENSSL_PKCS1_PADDING => 11,
        OPENSSL_NO_PADDING => 0
    ];

    const DEFAULT_PADDING = OPENSSL_PKCS1_PADDING;
    const DEFAULT_PADDING_LENGTH = self::PADDING_LENGTH[self::DEFAULT_PADDING];

    static function GetPaddingLength($pad): int
    {
        return self::PADDING_LENGTH[$pad];
    }

    static function PackPubKey(string $pubkey): string
    {
        return "-----BEGIN PUBLIC KEY-----\n{$pubkey}\n-----END PUBLIC KEY-----\n";
    }

    static function PackPrvKey(string $prvkey): string
    {
        return "-----BEGIN PRIVATE KEY-----\n{$prvkey}\n-----END PRIVATE KEY-----\n";
    }

    static function EncryptWithPubKey(string $source, string $packedkey, $len = 128)
    {
        $pkey = openssl_get_publickey($packedkey);
        if (!$pkey)
            return null;
        $output = '';
        $len -= self::DEFAULT_PADDING_LENGTH;
        foreach (str_split($source, $len) as $seg) {
            $ok = openssl_public_encrypt($seg, $encrypted, $pkey, self::DEFAULT_PADDING);
            if (!$ok) {
                $output = null;
                break;
            }
            $output .= $encrypted;
        }
        openssl_free_key($pkey);
        return $output;
    }

    static function DecryptWithPubKey(string $source, string $packedkey, $len = 128)
    {
        $pkey = openssl_get_publickey($packedkey);
        if (!$pkey)
            return null;
        $output = '';
        foreach (str_split($source, $len) as $seg) {
            $ok = openssl_public_decrypt($seg, $out, $pkey);
            if (!$ok) {
                $output = null;
                break;
            }
            $output .= $out;
        }
        return $output;
    }

    static function EncryptWithPrvKey(string $source, string $packedkey, $len = 128)
    {
        $pkey = openssl_get_privatekey($packedkey);
        if (!$pkey)
            return null;
        $output = '';
        $len -= self::DEFAULT_PADDING_LENGTH;
        foreach (str_split($source, $len) as $seg) {
            $ok = openssl_private_encrypt($seg, $encrypted, $pkey, self::DEFAULT_PADDING);
            if (!$ok) {
                $output = null;
                break;
            }
            $output .= $encrypted;
        }
        openssl_free_key($pkey);
        return $output;
    }

    static function DecryptWithPrvKey(string $source, string $packedkey, $len = 128)
    {
        $pkey = openssl_get_privatekey($packedkey);
        if (!$pkey)
            return null;
        $output = '';
        foreach (str_split($source, $len) as $seg) {
            $ok = openssl_private_decrypt($seg, $out, $pkey, self::DEFAULT_PADDING);
            if (!$ok) {
                $output = null;
                break;
            }
            $output .= $out;
        }
        return $output;
    }

    static function SignWithPrvKey(string $data, string $packedkey)
    {
        $pkey = openssl_get_privatekey($packedkey);
        if (!$pkey)
            return null;
        openssl_sign($data, $res, $pkey);
        openssl_free_key($pkey);;
        return $res;
    }
}
