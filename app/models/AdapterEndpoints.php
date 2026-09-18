<?php

declare(strict_types=1);

use Phalcon\Mvc\Model;

class AdapterEndpoints extends Model
{
    public function initialize(): void
    {
        $this->setSource('adapter_endpoints');
    }
}
