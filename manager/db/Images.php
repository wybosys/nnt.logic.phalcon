<?php
namespace Manager\Db;

class Images extends \Phalcon\Mvc\Model
{

    /**
     *
     * @var string
     * @Primary
     * @Column(column="id", type="string", length=12, nullable=false)
     */
    public $id;

    /**
     *
     * @var string
     * @Column(column="repo", type="string", length=255, nullable=true)
     */
    public $repo;

    /**
     *
     * @var string
     * @Column(column="tag", type="string", length=32, nullable=true)
     */
    public $tag;

    /**
     *
     * @var string
     * @Column(column="name", type="string", length=32, nullable=true)
     */
    public $name;

    /**
     *
     * @var string
     * @Column(column="code", type="string", length=255, nullable=true)
     */
    public $code;

    /**
     *
     * @var string
     * @Column(column="volume", type="string", length=255, nullable=true)
     */
    public $volume;

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->setSchema("devops");
        $this->setSource("images");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'images';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return Images[]|Images|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return Images|\Phalcon\Mvc\Model\ResultInterface
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

}
