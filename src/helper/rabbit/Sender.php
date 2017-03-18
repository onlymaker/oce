<?php
namespace helper\rabbit;

use PhpAmqpLib\Message\AMQPMessage;

class Sender extends Rabbit
{
    protected $connection;
    protected $channel;
    protected $exchange;
    protected $routeKey;

    function send($message)
    {
        $this->channel->exchange_declare($this->exchange, 'topic', false, false, false);

        $message = new AMQPMessage($message);

        $this->channel->basic_publish($message, $this->exchange, $this->routeKey);
    }
}
