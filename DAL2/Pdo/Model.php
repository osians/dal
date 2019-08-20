<?php declare(strict_types = 1);

namespace Osians\Dal\Pdo;

use Osians\Dal\ModelInterface;

/**
 *
 * CLASSE SYS_DB RESPONSAVEL POR FAZER A INTERFACE COM O BANCO DE DADOS,
 * NO CASO, O PADRAO E' USO COM MYSQL, POREM, PODENDO SER USADO COM SQLITE.
 * OUTRAS INTERFACES PODEM SER ADICIONADAS DE FORMA SIMPLES.
 *
 *
 * [MODO DE USO]
 *
 * # exemplo 1 (Usando valores Default, definidos no arquivo de Config)
 * $database = DBFactory::create( 'MySQL' );
 *
 * # exemplo 2 (definindo valores manualmente )
 * $database = DBFactory::create( 'MySQL', array('host' => '127.0.0.1','dbport' => '3306','user' => 'wandeco','pass' => 'sans','dbname' => 'nome_banco_dados') );
 *
 * # exemplo 3 (Usando valores Default, definidos no arquivo de Config)
 * $database = DBFactory::create( 'SQLite' );
 *
 * # exemplo 4 (definindo valores manualmente )
 * $database = DBFactory::create( 'SQLite', array( 'dbpath' => '/caminho/arquivo/','dbname' => 'database.sqlite' ) );
 *
 * # Inserindo registros ...
 * $database->query('INSERT INTO mytable (FName, LName, Age, Gender) VALUES (:fname, :lname, :age, :gender)');
 *
 * $database->bind(':fname', 'John');
 * $database->bind(':lname', 'Smith');
 * $database->bind(':age', '24');
 * $database->bind(':gender', 'male');
 *
 * $database->execute();
 *
 * echo $database->lastInsertId();
 *
 * ================================= SELECT ====================
 * $database->query('SELECT FName, LName, Age, Gender FROM mytable WHERE FName = :fname');
 * $database->bind(':fname', 'Jenny');
 * $row = $database->single();
 *
 * @author Wanderlei Santana <sans.pds@gmail.com>
 * @package sys_db
 *
 */

class Model implements ModelInterface
{
    protected $pdo;
    protected $error;
    protected $stmt;
    protected $table;

    public function __construct($pdo = null)
    {
        if ($pdo instanceof \PDO) {
            $this->setPdo($pdo);
        }
    }

    public function setPdo($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     *    @see ModelInterface::setTable()
     */
    public function setTable($table)
    {
        $this->class = $table;
        $class = explode('\\', $table);
        $class = strtolower(preg_replace('/\B([A-Z])/', '_$1', end($class)));
        $this->table = isset($this->options['prefix'])?$this->options['prefix'] . TextTransform::pluralize($class):TextTransform::pluralize($class);
        $this->alias = substr($class, 0, 1);
        return $this;
    }

    /**
     *    Realiza uma consulta SQL
     * @param $sql
     * @param array $params
     * @return mixed
     */
    public function sql($sql, $params = [])
    {
        if (substr(strtolower($sql), 0, 6) !== 'select') {
            return $this->pdo->exec($sql);
        }

        $results = $this->pdo->query($sql);
        return $results->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     *    @see ModelInterface::all();
     */
    public function all()
    {
        $results = $this->pdo->query("SELECT * FROM {$this->table}");
        return new IteratorResult($results->fetchAll(PDO::FETCH_OBJ));
    }

    public function bind($param, $value, $type = null)
    {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = \PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = \PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = \PDO::PARAM_NULL;
                    break;
                default:
                    $type = \PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    //    talvez remover
    private function parseAsObject($firstOnly = false)
    {
        $objeto  = new \stdClass;
        $retorno = array();
        $result  = ($firstOnly)
                 ? $this->single()
                 : $this->resultset();

        if ($result === false) {
            return false;
        }

        # se primeira row apenas ...
        if ($firstOnly) {
            foreach ($result as $key => $value) {
                $oKey = strtolower($key);
                $objeto->$oKey = $value;
            }
            return $objeto;
        }

        foreach ($result as $row) {
            $objeto = new \stdClass;
            foreach ($row as $key => $value) {
                $oKey = strtolower($key);
                $objeto->$oKey = $value;
            }
            $retorno[] = $objeto;
        }

        return $retorno;
    }

    //    talvez remover
    public function selectFirst($sql, $bind = array())
    {
        return $this->select($sql, $bind, true, true);
    }

    public function select(
        $sql,
        $bind = array(),
        $asObject = true,
        $firstOnly = false
    ) {
        $this->stmt = $this->pdo->prepare($sql);

        if (count($bind) > 0) {
            foreach ($bind as $key => $value) {
                $this->bind($key, $value);
            }
        }

        $this->execute();

        if ($asObject) {
            return $this->parseAsObject($firstOnly);
        }
        else {
            if($firstOnly){
                return $this->single();
            }else{
                return $this->resultset();
            }
        }
    }

    /**
     * [insert description]
     * Ex. de uso : $rs = $database->insert(
     *   'INSERT INTO mytable (FName, LName, Age, Gender) VALUES (:fname, :lname, :age, :gender)',
     *   array(':fname' => 'John',':lname'=>'Smith',':age','24',':gender'=>'male'),
     *   true
     * );
     * @param  string  $sql
     * @param  array   $bind
     * @param  boolean $returnLastInsertedId
     *
     * @return int|bool
     */
    public function insert(
        $sql,
        $bind = array(),
        $returnLastInsertedId = true
    ) {
        $this->stmt = $this->pdo->prepare($sql);

        if (count($bind) > 0) {
            foreach ($bind as $key => $value) {
                $this->bind($key, $value);
            }
        }

        $this->execute();

        return ($returnLastInsertedId) ? $this->lastInsertId() : true;
    }

    /**
     * Executa um update e retorna o numero de registros alterados
     * @param string - SQL a ser executada
     * @return int
     **/
    public function update($sql)
    {
        return $this->executar($sql);
    }

    public function execute(){
        try{
            return $this->stmt->execute();
        }catch(PDOException $e){
            throw new DBException( $e->getMessage() , $e->getCode() );
        }
    }

    /**
     *
     * @doc http://php.net/manual/pt_BR/pdo.exec.php
     *
     * @param  [type] $query [description]
     * @return [type]            [description]
     */
    public function executar($query)
    {
        return $this->pdo->exec($query);
    }

    public function resultset(){
        $this->execute();
        return $this->stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function single(){
        $this->execute();
        return $this->stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function rowCount(){
        $this->execute();
        return $this->stmt->rowCount();
    }

    public function lastInsertId(){
        return $this->pdo->lastInsertId();
    }

    public function beginTransaction(){
        return $this->pdo->beginTransaction();
    }

    public function commit(){
        return $this->pdo->commit();
    }

    public function roolback(){
        return $this->pdo->rollBack();
    }

    public function debugDumpParams(){
        return $this->stmt->debugDumpParams();
    }
}