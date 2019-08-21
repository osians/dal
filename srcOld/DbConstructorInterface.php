<?php

namespace Osians\Dal;

/**
 *    Database Constructor Interface
 *
 *    @package Osians\Dal
 */
interface DbConstructorInterface {

    /**
     *    @param array $options
     */
    public function __construct($options = []);

    /**
     *   Informa nome do Banco de Dados a ser usado
     *
     *   @param string $name
     *
     *   @return $this
     */
    public function setDb($name);
}
