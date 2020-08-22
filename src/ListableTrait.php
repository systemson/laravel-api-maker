<?php

namespace Systemson\ApiMaker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait ListableTrait
{
    /**
     * Returns the listable attributes.
     *
     * @return array
     */
    public function getListable(): array
    {
        return $this->listable ?? [];
    }

    /**
     * Sets the listable attributes.
     *
     * @param array $listable
     *
     * @return static
     */
    public function setListable(array $listable): self
    {
        $this->listable = $listable;

        return $this;
    }

    /**
     * Adds new item to the listable attributes.
     *
     * @param mixed      $value
     * @param mixed|null $alias
     *
     * @return static
     */
    public function addListable($value, $alias = null): self
    {
        if (is_null($alias)) {
            $this->listable[] = $value;
        }

        $this->listable[$value] = $alias;

        return $this;
    }

    /**
     * Returns the selectable attributes.
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
