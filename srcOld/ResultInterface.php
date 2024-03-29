<?php

namespace Osians\Dal;

/**
 * Interface ResultInterface
 * @package Osians\Dal
 */
interface ResultInterface {

    /**
     * @return mixed
     */
    public function save();

    /**
     * @return mixed
     */
    public function delete();

    /**
     * @param $name
     * @param $value
     * @return mixed
     */
    public function __set($name,$value);

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name);

    /**
     * @param $name
     * @param $args
     * @return mixed
     * @method mixed
     */
    public function __call($name,$args);

} 
