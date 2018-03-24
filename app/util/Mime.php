<?php
namespace App\Util;

class Mime
{

    const TYPES = [
        "pdf" => "application/pdf",
        "exe" => "application/octet-stream",
        "zip" => "application/zip",
        "docx" => "application/msword",
        "doc" => "application/msword",
        "xls" => "application/vnd.ms-excel",
        "ppt" => "application/vnd.ms-powerpoint",
        "gif" => "image/gif",
        "png" => "image/png",
        "jpeg" => "image/jpg",
        "jpg" => "image/jpg",
        "mp3" => "audio/mpeg",
        "wav" => "audio/x-wav",
        "mpeg" => "video/mpeg",
        "mpg" => "video/mpeg",
        "mpe" => "video/mpeg",
        "mov" => "video/quicktime",
        "avi" => "video/x-msvideo",
        "3gp" => "video/3gpp",
        "css" => "text/css",
        "jsc" => "application/javascript",
        "js" => "application/javascript",
        "php" => "text/html",
        "htm" => "text/html",
        "html" => "text/html"
    ];

    /**
     * 通过文件名获得mime信息
     *
     * @param string $path
     * @return string
     */
    static public function FromPath(string $path)
    {
        $comps = explode('.', $path);
        $ext = strtolower(end($comps));
        return self::TYPES[$ext];
    }
}