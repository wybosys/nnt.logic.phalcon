<?php

namespace App\Controller;

class Service
{
    static function Call(string $idr, array $args, array $files = null): object
    {
        $ch = curl_init();
        //$host = $_SERVER['HTTP_ORIGIN'];
        $host = 'http://develop.91egame.com';

        $url = $host . '/' . $idr . '?';
        curl_setopt($ch, CURLOPT_URL, $url . http_build_query($args));

        if (count($files)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $files);
        }

        return curl_exec($ch);
    }
}
