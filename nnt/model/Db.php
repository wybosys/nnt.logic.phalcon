<?php

namespace Nnt\Model;

class Db extends \Phalcon\Mvc\Model
{

    /**
     * @throws \Exception
     */
    public function update($data = null, $whiteList = null)
    {
        try {
            parent::update($data, $whiteList);
        } catch (\Throwable $exc) {
            throw new \Exception($exc->getMessage(), Code::DB_ERROR);
        }
    }

    /**
     * @throws \Exception
     */
    public function save($data = null, $whiteList = null)
    {
        try {
            parent::save($data, $whiteList);
        } catch (\Throwable $exc) {
            throw new \Exception($exc->getMessage(), Code::DB_ERROR);
        }
    }

    /**
     * @throws \Exception
     */
    public function delete()
    {
        try {
            parent::delete();
        } catch (\Throwable $exc) {
            throw new \Exception($exc->getMessage(), Code::DB_ERROR);
        }
    }
}