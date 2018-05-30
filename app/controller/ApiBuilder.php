<?php

namespace App\Controller;

use Phalcon\Mvc\Controller;

class ApiBuilder
{
    /**
     * @param t controller中的this对象
     */
    public static function export(Controller $t)
    {
        $cn = $t->router->getControllerName();
        $actions = Doc::Actions($t);

        $apinames = [];
        $file = fopen("tmp/$cn" . "Api.js", "w");
        fwrite($file, "import {ApiBase, ApiOutBase} from './ApiBase'\n");
        foreach ($actions as $v) {
            $an = $v['name'];
            $args = $v['params'];
            $out = [];
            $in = [];
            foreach ($args as $arg) {
                $argn = $arg->name;

                if ($arg->output)
                    $out[] = $argn;
                if ($arg->input || $arg->optional)
                    $in[] = $argn;

            }
            // 进
            $ina = ucfirst($cn . $an . "In");
            $ona = ucfirst($cn . $an . "Out");
            fwrite($file, "export class " . $ina . " extends ApiBase {\n" .
                "   constructor (){
        super()
        this.controller = '$cn'
        this.action = '$an'
        this.returnData = new $ona()
    }\n"
            );
            for ($i = 0; $i < count($in); $i++) {

                fwrite($file, "    _" . $in[$i] . "\n");

                fwrite($file,
                    "   get " . $in[$i] . " () {
        return this._$in[$i]
    }\n"
                );

                fwrite($file,
                    "   set " . $in[$i] . " (val) {
        this._$in[$i] = val
        this.params['$in[$i]'] = val
    }\n"
                );
            }
            fwrite($file, "}\n");

            // 出
            $ona = ucfirst($cn . $an . "Out");
            fwrite($file,
                "export class " . $ona . " extends ApiOutBase {
    constructor (){
        super()
        this.controller = '$cn'
        this.action = '$an'
    }\n"
            );
            for ($i = 0; $i < count($out); $i++) {

                fwrite($file, "    " . $out[$i] . "\n");
            }
            fwrite($file, "}\n");
            $apinames[] = $ina;
            $apinames[] = $ona;


        }
        //默认
        $t = implode(",", $apinames);
        $ana = $cn . "Apis";
        fwrite($file,
            "   let $ana = {
        $t
    }
    export default $ana"
        );
        fclose($file);

        $filename = "tmp/" . "$cn" . "Api.js";
        $outname = "$cn" . "Api.js";
        header("Content-type:application/x-javascript");
        header('Content-Disposition: attachment; filename="' . $outname . '"'); //指定下载文件的描述
        header('Content-Length:' . filesize($filename)); //指定下载文件的大小

//将文件内容读取出来并直接输出，以便下载
        readfile($filename);
    }
}