<?php

namespace Systemson\ApiMaker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        if (!isset($this->listable)) {
            return '*';
        }

        foreach ($this->listable as $key => $value) {
            if (is_numeric($key)) {
                $return[] = $value;
            } else {
                $return[] = DB::raw("{$key} as {$value}");
            }
        }

        return $return ?? [];
    }
}
