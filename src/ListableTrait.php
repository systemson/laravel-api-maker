<?php

namespace Systemson\ApiMaker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait ListableTrait
{
    /**
     * Returns the listable attributes
     *
     * @return array
     */
    public function getListable(): array
    {
        return $this->listable ?? [];
    }

    /**
     * Returns the selectable attributes
     *
     * @return array
     */
    public function getSelectable(): array
    {
        return $this->listable ?? '*';
    }
}
