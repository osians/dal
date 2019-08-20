<?php

require_once __DIR__ . '/../DAL2/ModelInterface.php';
require_once __DIR__ . '/../DAL2/DatabaseProviderInterface.php';
require_once __DIR__ . '/../DAL2/Pdo/Model.php';
require_once __DIR__ . '/../DAL2/Pdo/provider/Mysql.php';
require_once __DIR__ . '/../DAL2/DatabaseFactory.php';

use Osians\Dal\DatabaseFactory;

$params = [
	'host' => 'localhost',
	'port' => '3306',
	'user' => 'root',
	'pass' => '',
	'dbname' => 'vita',
];

$model = DatabaseFactory::create('MySQL', $params);

$rs = $model->select('SELECT * FROM usuario');

var_dump($rs);

