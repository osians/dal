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
    public static $_default = [];

    /**
     * @var array
     */
    public static $provider = [];

    /**
     * @var
     */
    public static $_class;

    /**
     * @var null
     */
    public static $_instance = null;

    /**
     * @var
     */
    private static $_keepLast;

    /**
     * @param ModelInterface $orm
     */
    public static function init(ModelInterface $orm)
    {
        self::$orm = $orm;
    }

    /**
     * @param array $provider
     * @param array $_default
     * @param bool $_keepLast
     */
    public static function provide(
        $provider = [],
        $_default = [],
        $_keepLast = false
    ) {
        self::$provider = $provider;
        reset($provider);

        self::$_default['orm'] = (isset($_default['orm']))
            ? $_default['orm'] : key($provider);

        self::$_default['db']  = (isset($_default['db']))
            ? $_default['db'] : 'default';

        self::$_keepLast = $_keepLast;
    }

    /**
     * @param $class
     * @return Object|Model|null
     */
    public static function getInstance($class)
    {
        if (is_null(self::$_instance) || self::$class !== $class) {
            self::$class = $class;
            self::$_instance = new self::$class;
        }
        return self::$_instance;
    }

    /**
     * @param $name
     * @return Object|Model|null
     */
    public static function orm($name)
    {
        if (!isset(self::$allOrm[$name]))
            self::$allOrm[$name] = call_user_func(self::$provider[$name]);
        self::$orm = self::$allOrm[$name];
        return self::getInstance(get_called_class());
    }

    /**
     * @param string $name
     * @return Object|Model|null
     */
    public static function db($name)
    {
        if (is_null(self::$orm)) {
            self::orm(self::$_default['orm']);
        }
        
        self::$orm->setDb($name);
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
            self::orm(self::$_default['orm']);
        }
        
        if (is_null(self::$database)) {
            self::db(self::$_default['db']);
        }
        
        return self::getInstance($table);
    }

    /**
     * @return Object|Model|null
     */
    public static function repo(){
        if(is_null(self::$_class) || self::$_class != get_called_class())
            self::$_class = get_called_class();
        self::table(self::$_class);
        self::$orm->setTable(self::$_class);
        $repo = self::$orm->repo();
        if(!self::$_keepLast) {
            self::$orm = null;
            self::$database = null;
        }
        return is_null($repo)?self::getInstance(get_called_class()):$repo;
    }

    /**
     * @param $name
     * @param $args
     * @return mixed
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
    private static function call($name,$args){
        if(is_null(self::$_class) || self::$_class != get_called_class())
            self::$_class = get_called_class();
        self::table(self::$_class);
        self::$orm->setTable(self::$_class);
        $call = self::$orm->callStatic($name, $args);
        if(!self::$_keepLast) {
            self::$orm = null;
            self::$database = null;
        }
        return $call;
    }

} 
