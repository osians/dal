<?php

require_once __DIR__ . '/../DAL2/Model.php';
require_once __DIR__ . '/../DAL2/ModelInterface.php';
require_once __DIR__ . '/../DAL2/DatabaseProviderInterface.php';
require_once __DIR__ . '/../DAL2/Pdo/PdoModel.php';
require_once __DIR__ . '/../DAL2/Pdo/provider/Mysql.php';
require_once __DIR__ . '/../DAL2/DatabaseFactory.php';
require_once __DIR__ . '/Usuario.php';

use Osians\Dal\DatabaseFactory;

$options = [
    'driver' => 'Mysql',
    'host' => 'localhost',
    'port' => '3306',
    'user' => 'root',
    'pass' => '',
    'dbname' => 'vita',
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
 $accounts = Model::table('Usuario')->all();
 
 // 2)
 $accounts = Usuario::all();
 // Usuario.php must extends Model class