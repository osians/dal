<?php declare(strict_types = 1);

namespace Osians\Dal\Pdo\Provider;

use \PDO;

class Mysql implements \Osians\Dal\DatabaseProviderInterface
{
    protected $host   = null;
    protected $dbport = null;
    protected $user   = null;
    protected $pass   = null;
    protected $dbname = null;
    protected $dbh    = null;
    protected $error  = null;
    protected $charset= null;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        $this->setOptions($options);
    }

    /**
     *    Determina as Configurações de acesso a Base de dados MySQL
     *
     *    @param array $options
     **/
    public function setOptions($options = [])
    {
        $options['charset'] = isset($options['charset']) ? $options['charset'] : 'utf8';
        $options['dbport']  = isset($options['dbport']) ? $options['dbport'] : '3306';

        if (isset($options['host'])) {
            $this->setHostname($options['host']);
        }

        if (isset($options['user'])) {
            $this->setDatabaseUser($options['user']);
        }

        if (isset($options['pass'])) {
            $this->setDatabaseUserPassword($options['pass']);
        }

        if (isset($options['dbname'])) {
            $this->setDatabaseName($options['dbname']);
        }

        $this->setDatabasePort($options['dbport']);
        $this->setDatabaseCharset($options['charset']);
    }

    public function setHostname($host)
    {
        $this->host = $host;
    }

    public function setDatabasePort($port)
    {
        $this->dbport = $port;
    }

    public function setDatabaseUser($user)
    {
        $this->user = $user;
    }

    public function setDatabaseUserPassword($pass)
    {
        $this->pass = $pass;
    }

    public function setDatabaseName($name)
    {
        $this->dbname = $name;
    }

    public function setDatabaseCharset($charset)
    {
        $this->charset = $charset;
    }

    /**
     *    realiza a conexão a uma Base de dados
     *
     *    @return PDO
     **/
    public function conectar()
    {
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset};";

        // Set options
        $options = array(
            \PDO::ATTR_PERSISTENT => true,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '{$this->charset}'"
        );

        try {
            return new \PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            throw new \Exception(
                $this->getExceptionByCode($e->getCode())
            );
        }
    }
    
    /**
     *    Dado um codigo numerico qualquer, tenta identificar o erro
     *    na lista dos codigos mais conhecidos.
     *
     *    @param integer $eCode
     *
     *    @return string
     **/
    public function getExceptionByCode($eCode)
    {
        switch ($eCode) {
            case 1049:
                $message = "O Banco de dados {$this->dbname} não existe";
                break;

            case 2002:
                $message = "Conexão com o servidor de banco de dados 
                {$this->host} foi recusada";
                break;

            case 1045:
                $message = "Falha ao tentar logar no servidor de banco de dados 
                    {$this->host}, com o usuário {$this->user}. Talvez a senha ou
                    o nome de usuário estejam incorretos.";
                break;

            default:
                $message = $e->getMessage();
                break;
        }

        return "{$message}. Código do Erro: {$e->getCode()}";
    }

    /**
     *    Desconecta do Banco de dados
     **/
    public function desconectar()
    {
    }
}
