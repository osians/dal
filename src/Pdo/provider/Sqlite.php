<?php declare(strict_types = 1);

namespace Osians\Dal\Pdo\Provider;

class SystemCoreDatabaseProviderSqlite implements DatabaseProviderInterface
{
    private $folder = '';
    private $dbName = '';
    private $dbh = null;

    public function __construct($folder = null, $dbName = null)
    {
        if ($folder != null) {
            $this->folder = $folder;
        }

        if ($dbName != null) {
            $this->dbName  = $dbName;
        }
    }

    public function setPath($value)
    {
        $this->folder = $value;
    }

    public function setName($value)
    {
        $this->dbName = $value;
    }

    public function conectar()
    {
        $dsn = "sqlite:{$this->folder}{$this->dbName}";

        try {

            $this->dbh = new \PDO($dsn);
            $this->dbh->setAttribute(
                \PDO::ATTR_ERRMODE,
                \PDO::ERRMODE_EXCEPTION
            );

            return $this->dbh;

        } catch (PDOException $e) {
            $this->error = $e->getMessage();
        }
    }
}