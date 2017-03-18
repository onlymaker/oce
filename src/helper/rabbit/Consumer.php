<?php
namespace helper\rabbit;

class Consumer extends Rabbit
{
    private $running = false;

    function add($callback)
    {
        $this->channel->queue_declare('', false, false, false, false);

        $this->channel->queue_bind('', $this->exchange, $this->routeKey);

        $this->channel->basic_consume('', '', false, false, false, false, function ($message) use ($callback) {
            if (is_callable($callback)) {
                $callback($message);
            } else if (is_array($callback)) {
                foreach ($callback as $func) {
                    if (is_callable($func)) {
                        $func($message);
                    }
                }
            }
        });
    }

    function start()
    {
        if (!$this->running) {
            while (count($this->channel->callbacks)) {
                $this->channel->wait();
            }
            $this->running = true;
        }
    }
}
