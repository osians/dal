<?php declare(strict_types = 1);

namespace Osians\Dal\Pdo;

use Osians\Dal\ModelInterface;


class PdoModel implements ModelInterface
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
     * @return mixed
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @return null
     */
    public function repo()
    {
        if (isset($this->options['repositories']) 
        && is_array($this->options['repositories'])) {
            foreach($this->options['repositories'] as $repo) {
                
                $class = explode('\\', $this->class);
                $class = end($class);

                if (is_file(rtrim($repo['path'], '/') . '/' . $class . 'Repository.php')) {
                    $class = $repo['namespace']  . $class . 'Repository';
                    return new $class();
                }
            }
        }

        return null;
    }

    /**
     * @return mixed
     */
    public function getOrm()
    {
        return $this->pdo;
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

    /**
     *    @see ModelInterface::all()
     */
    public function all()
    {
        $results = $this->pdo->query(
            "SELECT * FROM {$this->table}"
        );

        return new IteratorResult(
            $results->fetchAll(PDO::FETCH_OBJ));
    }

    /**
     *    @see ModelInterface::find()
     */
    public function find($id)
    {
        $result = $this->pdo->prepare(
            "SELECT * FROM {$this->table} WHERE id = :id"
        );

        $result->bindValue('id', $id, PDO::PARAM_INT);
        $result->execute();

        return new PdoSingleResult(
            $result->fetch(PDO::FETCH_OBJ),
            function(){
                return $this;
            }
        );
    }
    
    /**
     *    Recebe N Strings, sendo estas nomes de colunas
     *    @see ModelInterface::select()
     **/
    public function select()
    {
        $args = func_get_args();

        $this->sql = "SELECT";
        $this->sql.= (count($args) == 0)
                   ? ' * ' : implode(', ', $args);
        $this->sql.= " FROM {$this->table}";

        return $this;
    }

    /**
     *    @see ModelInterface::where()
     */
    public function where(
        $key, 
        $operator = null, 
        $value = null, 
        $boolean = "AND"
    ) {
        
        if (!empty($this->sql) 
        && substr($this->sql, 0, 6) == 'SELECT' 
        && strpos($this->sql, 'WHERE') === false) {
            $this->sql .= ' WHERE';
        }

        if (empty($this->sql)) {
            $this->sql = ' WHERE';
        }

        if (is_null($value) || $boolean == 'OR') {
            list($key, $operator, $value) = array($key, '=', $operator);
        }

        $param = $key;
        if (strpos($this->sql, ':' . $key) !== false) {
            $key = $param . '_' . uniqid();
        }

        $sql_key = ($operator == 'IN' || $operator == 'NOT IN')
        ? '(:'.$key.')' : ':'.$key;

        $this->sql .= (substr($this->sql, -6) == ' WHERE')
            ? ' ' . "$param $operator $sql_key"
            : ' ' . $boolean . ' ' . "$param $operator $sql_key";

        $this->params[$key] = $value;

        return $this;
    }

    /**
     *    @see ModelInterface::orwhere()
     */
    public function orWhere($key, $operator = null, $value = null)
    {
        return $this->where($key, $operator, $value, 'OR');
    }

    /**
     * @param $sql
     * @param null $value
     * @return mixed
     */
    public function whereRaw($sql, $value = null)
    {
        if (!empty($this->sql) && substr($this->sql, 0, 6) == 'SELECT') $this->sql .= ' WHERE ';
        if (empty($this->sql)) $this->sql = ' WHERE ';
        $this->sql .= $sql;
        if (!is_null($value)) $this->params = array_merge($this->params,(array)$value );
        return $this;
    }

    /**
     * @param $value
     * @param string $order
     * @return mixed
     */
    public function orderBy($value, $order = 'ASC')
    {
        $this->sql .= ' ORDER BY ' . $value . ' ' . $order;
        return $this;
    }

    /**
     * @param $limit
     * @param null $first
     * @param bool $single
     * @internal param bool $array
     * @return mixed
     */
    public function take($limit,$first = null, $single = false)
    {
        if(is_null($this->sql))
            $this->sql = 'SELECT * FROM ' . $this->table . ' '. $this->alias;
        $this->sql .= ' LIMIT '.$limit;
        if(!is_null($first))$this->sql .= ' OFFSET '.$first;
        $result = $this->execQuery($this->sql,$this->params);
        $result = $result->fetchAll(PDO::FETCH_OBJ);
        $this->sql = null;
        $this->params = [];
        return ($single && count($result) == 1)
            ? new PdoSingleResult($result[0],function(){return $this;})
            : new IteratorResult($result);
    }

    /**
     * @param bool $single
     * @return mixed
     */
    public function get($single = false)
    {
        if(is_null($this->sql))return new PdoSingleResult($this->getInstance(),function(){return $this;});
        $this->sql = (substr($this->sql, 0, 6) !== 'SELECT') ? 'SELECT * FROM ' . $this->table . ' ' . $this->alias .$this->sql : $this->sql;
        $result = $this->execQuery($this->sql,$this->params);
        $this->sql = null;$this->params = [];
        $result = $result->fetchAll(PDO::FETCH_OBJ);
        return ($single && count($result) == 1) ? new PdoSingleResult($result[0],function(){return $this;}) : new IteratorResult($result);
    }

    /**
     * @return mixed
     */
    public function count()
    {
        $this->sql = 'SbELECT COUNT(*) FROM ' . $this->table . ' ' .$this->alias. $this->sql;
        $result = $this->execQuery($this->sql,$this->params);
        $this->sql = null;$this->params = [];
        return $result->fetch()[0];
    }

    /**
     * @param int|string $id
     * @param null $contents
     * @return mixed
     */
    public function update($id, $contents = null)
    {
        $this->sql = 'WHERE id = :id';
        $this->params['id'] = $id;
        return (is_null($contents))?$this:$this->add($contents);
    }

    /**
     * @param $contents
     * @return mixed
     */
    public function with($contents)
    {
        return (substr($this->sql, 0, -5) == 'WHERE')
            ? $this->add($contents)
            : $this->insert($contents);
    }

    /**
     * @param $contents
     * @return mixed
     */
    public function set($contents)
    {
        $sql = '';
        foreach($contents as $key => $value)
            $sql .= $key.' = :'.$key.',';
        $result = $this->pdo->prepare('UPDATE '.$this->table.' SET '.substr($sql, 0, -1).$this->sql);
        $this->params = array_merge($contents,$this->params);
        foreach($this->params as $key => $value)
            $result->bindValue($key,$value);
        $this->sql = null;
        $this->params = [];
        return $result->execute();
    }

    /**
     * @param null $contents
     * @return mixed
     */
    public function create($contents = null)
    {
        return is_null($contents)?$this:$this->insert($contents);
    }

    /**
     * @return mixed
     */
    public function delete()
    {
        $this->sql = 'DELETE FROM' . $this->table . ' '. $this->sql;
        $query = $this->execQuery($this->sql, $this->params);
        $this->sql = null;
        $this->params = [];
        return $query;
    }

    /**
     * @return mixed
     */
    public function destroy()
    {
        $ids = func_get_args();
        $sql = 'DELETE FROM '.$this->table.' WHERE id IN (';
        $params = [];
        foreach ($ids as $key => $id) {
            $sql .= '?,';
            $params[] = $id;
        }
        $result = $this->pdo->prepare(substr($sql,0,-1).')');
        foreach($params as $key => $value)
            $result->bindValue($key,$value,PDO::PARAM_INT);
        return $result->execute();
    }

    /**
     * @param $name
     * @param $args
     * @return mixed
     */
    public function callStatic($name, $args)
    {
        if (method_exists($this, $name))
            return call_user_func_array([$this, $name], $args);
        return call_user_func_array([$this->getOrm(), $name], $args);
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