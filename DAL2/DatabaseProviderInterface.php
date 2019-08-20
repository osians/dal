<?php declare(strict_types = 1);

namespace Osians\Dal;

interface DatabaseProviderInterface
{
    /**
     *    @param array $options
     */
    public function __construct($options = []);
    public function conectar();
    public function desconectar();
}
