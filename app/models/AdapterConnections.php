<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;

class AdapterConnections extends Model
{
    public function initialize(): void
    {
        $this->setSource('adapter_connections');
    }
}
