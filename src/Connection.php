<?php
namespace kodeops\LaravelMysqlDumper;

use kodeops\LaravelMysqlDumper\Exceptions\LaravelMysqlDumperException;

class Connection
{
    protected $host;
    protected $port;
    protected $database;
    protected $user;
    protected $password;

    public function __construct($host, $port, $database, $user, $password)
    {
        $this->host = $host ?? throw_if(is_null($host), LaravelMysqlDumperException::class, "Undefined host");
        $this->port = $port ?? throw_if(is_null($port), LaravelMysqlDumperException::class, "Undefined port");
        $this->database = $database ?? throw_if(is_null($database), LaravelMysqlDumperException::class, "Undefined database");
        $this->user = $user ?? throw_if(is_null($user), LaravelMysqlDumperException::class, "Undefined user");
        $this->password = $password ?? throw_if(is_null($password), LaravelMysqlDumperException::class, "Undefined password");
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function getDatabase()
    {
        return $this->database;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getPassword()
    {
        return $this->password;
    }
}
