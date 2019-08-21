<?php

namespace Osians\Dal;

/**
 * Class Model
 * @package Osians\Dal
 * @method static|object all()
 * @method static|object find($id)
 * @method static|object select()
 * @method static|object where($key, $operator = null, $value = null, $boolean = "AND")
 * @method static|object orWhere($key, $operator = null, $value = null)
 * @method static|object whereRaw($sql, $value = null)
 * @method static|object get($single = false)
 * @method static|object take($limit,$first = null,$single = false)
 * @method static|object orderBy($value, $order = 'ASC')
 * @method static|object count()
 * @method static|object update($id, $contents = null)
 * @method static|object with($contents)
 * @method static|object set($contents)
 * @method static|object create($contents = null)
 * @method static|object delete()
 * @method static|object destroy()
 * @method static|object getOrm()
 * // Doctrine methods
 * @method static|\Doctrine\ORM\EntityManager em()
 * @method static|\Doctrine\ORM\QueryBuilder queryBuilder()
 * @method static|object save()
 * @method static|object watch()
 * @method static|object watchAndSave()
 * // RedBean methods
 * @method static remove()
 */
class Model
{
    /**
     * @var \Osians\Dal\Pdo\PdoModel|\Osians\Dal\Doctrine\DoctrineModel|\Osians\Dal\RedBean\RedBeanModel
     */
    public static $orm;

    /**
     * @var
     */
    public static $allOrm;
    
    /**
     * @var
     */
    public static $database;

    /**
     * @var
     */
    public static $default = [];

    /**
     * @var array
     */
    public static $provider = [];

    /**
     * @var
     */
    public static $class;

    /**
     * @var null
     */
    public static $instance = null;

    /**
     * @param ModelInterface $orm
     */
    public static function init(ModelInterface $orm)
    {
        self::$orm = $orm;
    }

    /**
     * @param $class
     * @return Object|Model|null
     */
    public static function getInstance($class)
    {
        if (is_null(self::$instance) || self::$class !== $class) {
            self::$class = $class;
            self::$instance = new self::$class;
        }
        return self::$instance;
    }

    /**
     * @param array $provider
     * @param array $default
     * @param bool $keepLast
     */
    public static function provide($provider = [], $default = [])
    {
        self::$provider = $provider;
        reset($provider);

        self::$default['orm'] = (isset($default['orm']))
            ? $default['orm'] : key($provider);

        self::$default['db']  = (isset($default['db']))
            ? $default['db'] : 'default';
    }

    /**
     * @param $name
     * @return Object|Model|null
     */
    public static function orm($name)
    {
        if (!isset(self::$allOrm[$name])) {
            self::$allOrm[$name] = call_user_func(self::$provider[$name]);
        }

        self::$orm = self::$allOrm[$name];
        return self::getInstance(get_called_class());
    }

    /**
     * @param string $name
     * @return Object|Model|null
     */
    public static function setDatabase($name)
    {
        if (is_null(self::$orm)) {
            self::orm(self::$default['orm']);
        }
        // @var $orm - PdoModel
        self::$orm->setDatabase($name);
        self::$database = $name;

        return self::getInstance(get_called_class());
    }

    /**
     *    Retorna a instancia de uma Classe representando
     *    uma tabela do banco de dados.
     *
     *    @param string $table - Nome de uma Classe Model
     *
     *    @return Object|Model|null
     */
    public static function table($table)
    {
        if (is_null(self::$orm)) {
            throw new \Exception("ORM is not defined");
        }
        
        if (is_null(self::$database)) {
            throw new \Exception("Database is not defined");
        }
        
        return self::getInstance($table);
    }

    /**
     * @return Object|Model|null
     */
    public static function repo(){
        if(is_null(self::$class) || self::$class != get_called_class())
            self::$class = get_called_class();
        self::table(self::$class);
        self::$orm->setTable(self::$class);
        $repo = self::$orm->repo();
        return is_null($repo)?self::getInstance(get_called_class()):$repo;
    }

    /**
     *    Metodo chamado em casos como Usuario::all();
     *
     *    @param $name
     *    @param $args
     *    @return mixed
     */
    public static function __callStatic($name, $args)
    {
        return self::call($name, $args);
    }

    /**
     * @param $name
     * @param $args
     * @return mixed
     */
    public function __call($name, $args)
    {
        return self::call($name, $args);
    }

    /**
     * @param $name
     * @param $args
     * @return mixed
     */
    private static function call($name, $args)
    {
        if (is_null(self::$class) ||
            self::$class != get_called_class()) {
            self::$class = get_called_class();
        }

        self::$orm->setTable(self::$class);
        $call = self::$orm->callStatic($name, $args);

        return $call;
    }
}
