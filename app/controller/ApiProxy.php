<?php

namespace App\Controller;

class ApiProxy extends Api
{
    /**
     * @var string 转向的服务器地址
     */
    protected $passto;

    function __call($name, $arguments)
    {
        $request = $this->request;

        $posts = $request->getPost();
        $gets = $request->getQuery();
        $pods = array_merge($gets, $posts);

        $files = [];
        foreach ($request->getUploadedFiles() as $file) {
            $files[$file->getKey()] = $file;
        }

        echo Service::RawCall($this->passto, $pods, $files);
    }
}