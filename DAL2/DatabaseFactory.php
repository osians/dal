<?php declare(strict_types = 1);

namespace Osians\Dal;

class DatabaseFactory
{
    /**
     *    Constroi uma conexao com um Banco de dados
     *
     *    @param string  - Tipo de banco de dados a ser criado conexao
     *
     *    @return object - Osians\Dal\Pdo\Model
     **/
    public static function create($options = array())
    {
        $this->isValid($options);

        $class = "\Osians\Dal\Pdo\Provider\\{$options['driver']}";
        $database = new $class($options);
        $conn = $database->conectar();

        return new \Osians\Dal\Pdo\Model($conn);
    }

    /**
     *    Verifica se parametros contemplam requisitos minimos
     *
     *    @param  array  $options
     *
     *    @return boolean
     */
    protected function isValid($options)
    {
        if (!isset($options['driver'])) {
            throw new \Exception('Missing driver name');
        }

        if (!isset($options) || empty($options)) {
            throw new \Exception('Missing arguments for PDO constructor');
        }

        return true;
    }
}