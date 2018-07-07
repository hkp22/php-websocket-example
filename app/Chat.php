<?php

namespace App;

use App\ChatEventsTrait;
use App\Events\UserLeft;
use App\Socket\SocketAbstract;
use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class Chat extends SocketAbstract implements MessageComponentInterface
{
    use ChatEventsTrait;

    protected $clients;

    protected $users;

    public function onOpen(ConnectionInterface $connection)
    {
        $this->clients[$connection->resourceId] = $connection;
    }

    public function onMessage(ConnectionInterface $connection, $message)
    {
        $payload = json_decode($message);

        if (method_exists($this, $method = 'handle' . ucfirst($payload->event))) {
            $this->{$method}($connection, $payload);
        }
    }

    public function onClose(ConnectionInterface $connection)
    {
        if (!isset($this->users[$connection->resourceId])) {
            return;
        }

        $user = $this->users[$connection->resourceId];

        $this->broadcast(new UserLeft($user))->toAll();

        unset($this->clients[$connection->resourceId], $this->users[$connection->resourceId]);
    }

    public function onError(ConnectionInterface $connection, Exception $e)
    {
        $connection->close();
    }
}