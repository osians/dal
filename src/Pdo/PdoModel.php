<?php declare(strict_types = 1);

namespace Osians\Dal\Pdo;


use Osians\Dal\IteratorResult;
use Osians\Dal\ModelInterface;
use Osians\Dal\TextTransform;
use PDO;

/**
 * Class PdoModel
 * @package Osians\Dal\Pdo
 */
class PdoModel implements ModelInterface
{
    protected $pdo;

    /**
     * @var array
     */
    private $db = [];

    private $allDb = [];

    /**
     * @var
     */
    public $class;
    
    /**
     * @var
     */
    public $table;
    
    /**
     * @var
     */
    public $alias;
    
    /**
     * @var
     */
    public $sql;
    
    /**
     * @var array
     */
    public $params = [];
    
    /**
     * @var
     */
    public $instance;

    /**
     * @param array $db
     * @param array $params
     * @throws \Exception
     */
    public function __construct($db = [], $params = [])
    {
        $this->setOrm($db);

        // $this->db = $db;
        // foreach ($this->db as $key => $db) {
        //     if (!isset($db['driver']) || !isset($db['user']) || !isset($db['pass']) || !isset($db['host']) || !isset($db['db'])) {
        //         throw new \Exception('Missing arguments for PDO constructor');
        //     }

        //     $this->allDb[$key] = function() use($db) {
        //         return new PDO($db['driver'] . ':host=' . $db['host'] . ';dbname=' . $db['db'], $db['user'], $db['pass']);
        //     };
        // }
    }

    public function setOrm($pdo)
    {
        if (!($pdo instanceof \PDO)) {
            return false;
        }

        $this->pdo = $pdo;
        return $this;
    }

    /**
     *    Retorna a ORM
     *    @return PDO
     */
    public function getOrm()
    {
        return $this->pdo;
    }

    /**
     * @param $table
     * @return $this
     */
    public function setTable($table)
    {
        $this->setClass($table);

        $class = explode('\\', $table);
        $class = strtolower(preg_replace('/\B([A-Z])/', '_$1', end($class)));

        $this->table = isset($this->options['prefix'])
                     ? $this->options['prefix'] . $class : $class;

        $this->alias = substr($class, 0, 1);

        return $this;
    }

    public function setClass($class)
    {
        $this->class = $class;
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
            foreach ($this->options['repositories'] as $repo) {
                
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
     *    @see ModelInterface::all()
     */
    public function all()
    {
        $results = $this->pdo->query(
            "SELECT * FROM {$this->table}"
        );

        return new IteratorResult($results->fetchAll(PDO::FETCH_OBJ));
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
     * @return int
     */
    public function clear(){
        return $this->pdo->exec('TRUNCATE TABLE '.$this->table);
    }

    /**
     *    Chama um metodo desta classe estaticamente
     *
     *    @param string $name
     *    @param array  $args
     *    @return mixed
     */
    public function callStatic($name, $args)
    {
        if (method_exists($this, $name)) {
            return call_user_func_array([$this, $name], $args);
        }

        return call_user_func_array([$this->getOrm(), $name], $args);
    }

    /**
     * @param $contents
     * @return bool
     */
    public function add($contents){
        $sql = '';
        foreach($contents as $key => $value)
            $sql .= $key.' = :'.$key.',';
        $result = $this->pdo->prepare('UPDATE '.$this->table.' SET '.substr($sql, 0, -1).' '.$this->sql);
        foreach($contents as $key => $value)
            $result->bindValue($key,$value);
        $result->bindValue('id',$this->params['id'],PDO::PARAM_INT);
        $this->sql = '';
        $this->params = [];
        return $result->execute();
    }

    /**
     * @param $contents
     * @return bool
     */
    public function insert($contents){
        $values = '';
        foreach($contents as $key => $value) {
            $this->sql .= $key . ',';
            $values .= ':'.$key.',';
        }
        $result = $this->pdo->prepare('INSERT INTO '.$this->table.'('.substr($this->sql, 0, -1).') VALUES ('.substr($values,0,-1).')');
        foreach($contents as $key => $value)
            $result->bindValue($key,$value);
        return $result->execute();
    }

    /**
     * @param $sql
     * @param array $params
     * @return \PDOStatement
     */
    private function execQuery($sql,$params = []){
        $query = $this->pdo->prepare($sql);
        foreach($params as $key => $value) {
            if(is_object($value))
                $this->objectToValue($query,$key,$value);
            else {
                if (is_int($value))
                    $query->bindValue($key, $value, PDO::PARAM_INT);
                elseif (is_string($value))
                    $query->bindValue($key, $value, PDO::PARAM_STR);
                else
                    $query->bindValue($key, $value);
            }
        }
        $query->execute();
        return $query;
    }

    /**
     * @param \PDOStatement $query
     * @param $key
     * @param $value
     */
    private function objectToValue(&$query,$key,$value){
        if($value instanceof \DateTime)
            $query->bindValue($key,$value->format('Y-m-d H:i:s'));
    }

    /**
     * @param $name
     * @return $this
     * @throws \Exception
     */
    public function setDatabase($name)
    {
        $this->options = $this->db[$name];
        
        if (is_callable($this->allDb[$name])) {
            $this->allDb[$name] = call_user_func($this->allDb[$name]);
        }

        $this->pdo = $this->allDb[$name];
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $name;
    }
}
