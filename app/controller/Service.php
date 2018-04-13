<?php

namespace App\Controller;

use Phalcon\Http\Request\File;

class Service
{
    /**
     * @throws \Exception
     */
    static function Call(string $idr, array $args, array $files = null)
    {
        $ch = curl_init();
        //$host = $_SERVER['HTTP_ORIGIN'];
        $host = 'http://develop.91egame.com';

        $url = $host . '/' . $idr . '/?' . http_build_query($args);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if (count($files)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: multipart/form-data"
            ]);
            $data = [];
            foreach ($files as $key => $file) {
                if ($file instanceof File) {
                    $data[$key] = curl_file_create($file->getTempName(), $file->getType(), $file->getName());
                }
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        $msg = curl_exec($ch);
        curl_close($ch);

        $ret = json_decode($msg);
        if ($ret->code !== 0)
            throw new \Exception("执行失败", $ret->code);
        return $ret;
    }
}
