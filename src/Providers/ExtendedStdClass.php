<?php

namespace Core\Providers;

use stdClass;

class ExtendedStdClass extends stdClass
{
    /**
     * @return array
     */
    public function toArray()
    {
        $copy = clone $this;
        unset($copy->___technical_data);
        return json_decode(json_encode($copy), true);
    }
}