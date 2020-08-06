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
    public function getSelectable()
    {
        if (!isset($this->listable) || empty($this->listable)) {
            return '*';
        }

        foreach ($this->listable as $key => $value) {
            if (is_numeric($key)) {
                if (count(explode('.', $value)) == 1) {
                    $return[] = "{$this->getTable()}.{$value}";
                } else {
                    $return[] = $value;
                }
            } else {
                $return[] = DB::raw("{$key} as {$value}");
            }
        }

        return $return;
    }
}
