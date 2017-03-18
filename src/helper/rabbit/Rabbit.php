<?php
namespace helper\rabbit;

use PhpAmqpLib\Connection\AMQPStreamConnection;

abstract class Rabbit
{
    protected $connection;
    protected $exchange;
    protected $routeKey;
    protected $channel;

    function __construct($host, $port, $user, $pwd, $exchange, $routeKey)
    {
        $this->connection = new AMQPStreamConnection($host, $port, $user, $pwd);
        $this->exchange = $exchange;
        $this->routeKey = $routeKey;
        $this->channel = $this->connection->channel();
    }

    function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }
}
