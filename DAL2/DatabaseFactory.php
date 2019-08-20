<?php declare(strict_types = 1);

namespace Osians\Dal;

class DatabaseFactory
{
    /**
     *    Constroi uma conexao com um Banco de dados
     *
     *    @param string  - Tipo de banco de dados a ser criado conexao
     *
     *    @return object - Osians\Dal\Pdo\PdoModel
     **/
    public static function create($options = array())
    {
        $this->isValid($options);

        $class = "\Osians\Dal\Pdo\Provider\\{$options['driver']}";
        $database = new $class($options);
        $pdo = $database->conectar();

        $pdoModel = new \Osians\Dal\Pdo\PdoModel($pdo);
        Osians\Dal\Model::init($pdoModel);
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