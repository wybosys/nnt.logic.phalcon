<?php
namespace Manager\Db;

class Containers extends \Phalcon\Mvc\Model
{

    /**
     *
     * @var string
     * @Primary
     * @Column(column="id", type="string", length=128, nullable=false)
     */
    public $id;

    /**
     *
     * @var string
     * @Column(column="image", type="string", length=255, nullable=true)
     */
    public $image;

    /**
     *
     * @var string
     * @Column(column="names", type="string", length=128, nullable=true)
     */
    public $names;

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->setSchema("devops");
        $this->setSource("containers");
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'containers';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return Containers[]|Containers|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return Containers|\Phalcon\Mvc\Model\ResultInterface
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

}
