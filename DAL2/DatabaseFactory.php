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
    public static function create($database, $options = array())
    {
        if (!isset($options) || empty($options)) {
            throw new \Exception('Missing arguments for PDO constructor');
        }

        $class = "\Osians\Dal\Pdo\Provider\\$database";
        $database = new $class($options);
        $conn = $database->conectar();

        return new \Osians\Dal\Pdo\Model($conn);
    }
}