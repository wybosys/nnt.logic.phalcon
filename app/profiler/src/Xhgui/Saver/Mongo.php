<?php

class Xhgui_Saver_Mongo implements Xhgui_Saver_Interface
{
    /**
     * @var MongoCollection
     */
    private $_db;
    private $_scheme;

    /**
     * @var MongoId lastProfilingId
     */
    private static $lastProfilingId;

    public function __construct(\MongoDB\Driver\Manager $db, string $scheme)
    {
        $this->_db = $db;
        $this->_scheme = $scheme;
    }

    public function save(array $data)
    {
        $data['_id'] = self::getLastProfilingId();
        try {
            $this->_db->executeCommand($this->_scheme, new \MongoDB\Driver\Command([
                "insert" => "phalcon",
                "documents" => [$data],
                "writeConcern" => ['w' => 0]
            ]));
        } catch (\Throwable $err) {
            echo $err->getMessage();
        }
    }

    /**
     * Return profiling ID
     * @return MongoId lastProfilingId
     */
    public static function getLastProfilingId()
    {
        if (!self::$lastProfilingId) {
            self::$lastProfilingId = new \MongoDB\BSON\ObjectId();
        }
        return self::$lastProfilingId;
    }
}
