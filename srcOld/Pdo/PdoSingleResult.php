<?php

namespace Osians\Dal\Pdo;

use ArrayAccess;
use Osians\Dal\ResultInterface;

/**
 * Class PdoSingleResult
 * @package Osians\Dal\Pdo
 */
class PdoSingleResult implements ResultInterface,ArrayAccess {

    /**
     * @var
     */
    private $table;
    /**
     * @var null
     */
    private $orm;

    /**
     * @var string
     */
    private $type;

    /**
     * @param $table
     * @param null $orm
     */
    public function __construct($table,$orm = null){
        $this->table = $table;
        if(isset($this->table->id))$this->type = 'read';
        if(!is_null($orm)) $this->orm = $orm;
    }

    /**
     * @return mixed|null
     */
    private function _getOrm(){
        if(is_callable($this->orm))
            $this->orm = call_user_func($this->orm);
        return $this->orm;
    }

    /**
     * @return mixed
     */
    public function _getTable(){
        return $this->table;
    }

    /**
     * @return mixed
     */
    public function save(){
        $orm = $this->_getOrm();
        if($this->type == 'read'){
            $orm->sql = 'WHERE id = :id';
            $orm->params['id'] = $this->table->id;
            return $orm->add($orm->params);
        }
        return $orm->insert($orm->params);
    }

    /**
     * @return mixed
     */
    public function delete(){
        return $this->_getOrm()->destroy($this->table->id);
    }

    /**
     * @param $offset
     * @param $value
     * @return mixed|void
     */
    public function __set($offset,$value){
        $this->_getOrm()->params[$offset] = $value;
    }

    /**
     * @param $offset
     * @return mixed
     */
    public function __get($offset){
        return $this->table->$offset;
    }

    /**
     * @param $offset
     * @param $args
     * @return null
     */
    public function __call($offset,$args){
        if(substr( $offset, 0, 3 ) == 'get') {
            $offset = strtolower(preg_replace('/\B([A-Z])/', '_$1', str_replace('get', '', $offset)));
            return $this->table->$offset;
        }elseif(substr( $offset, 0, 3 ) == 'set') {
            $offset = strtolower(preg_replace('/\B([A-Z])/', '_$1', str_replace('set', '', $offset)));
            $this->_getOrm()->params[$offset] = $args[0];
        }
        return null;
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return !is_null($this->table->$offset);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->table->$offset;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->_getOrm()->params[$offset] = $value;
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        $this->_getOrm()->params[$offset] = NULL;
    }
}
