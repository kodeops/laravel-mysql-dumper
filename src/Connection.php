<?php
namespace kodeops\LaravelMysqlDumper;

class Connection
{
    protected $host;
    protected $port;
    protected $database;
    protected $user;
    protected $password;

    public function __construct($host, $port, $database, $user, $password)
    {
        $this->host = $host;
        $this->port = $port;
        $this->database = $database;
        $this->user = $user;
        $this->password = $password;
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
