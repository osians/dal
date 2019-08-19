<?php

namespace Osians\Dal;


/**
 * Interface DbConstructorInterface
 * @package Osians\Dal
 */
interface DbConstructorInterface {

    /**
     * @param array $options
     */
    public function __construct($options = []);

    /**
     * @param $name
     * @return mixed
     */
    public function setDb($name);

} 