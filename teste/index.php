<?php

// talvez eliminar a classe TextTransform
require_once __DIR__ . '/../DAL2/TextTransform.php';
require_once __DIR__ . '/../DAL2/IteratorResult.php';
require_once __DIR__ . '/../DAL2/Model.php';
require_once __DIR__ . '/../DAL2/ModelInterface.php';
require_once __DIR__ . '/../DAL2/DatabaseProviderInterface.php';
require_once __DIR__ . '/../DAL2/Pdo/PdoModel.php';
require_once __DIR__ . '/../DAL2/Pdo/provider/Mysql.php';
require_once __DIR__ . '/../DAL2/DatabaseFactory.php';
require_once __DIR__ . '/Usuario.php';

use Osians\Dal\DatabaseFactory;
use Osians\Dal\Model;

$options = [
    'driver' => 'Mysql',
    'host' => 'localhost',
    'port' => '3306',
    'user' => 'userdb',
    'pass' => '',
    'dbname' => 'projects',
    'prefix' => 'os_',
];

DatabaseFactory::create($options);

// $rs = $model->select('SELECT * FROM usuario');
// var_dump($rs);
 
 // Model facade
// $db = new \Osians\Dal\Doctrine\DoctrineModel($options);
// Osians\Dal\Model::init($db);
 
 // or for lazy loading
 // $db = [
 //     'doctrine' => function() use ($options) {
 //         new \Osians\Dal\Doctrine\DoctrineModel($options);
 //     }
 // ];
 // Osians\Dal\Model::provide($db);
 
 // And for retrieve data you have 2 possible ways
 
 // 1)
 // $usuarios = Model::table('Usuario')->all();
 // var_dump(Model::$orm);
 // die();
 // print_r($usuarios);
 
 // 2)
 $usuarios = Usuario::all();
 print_r($usuarios);
 
 // Usuario.php must extends Model class