<?php

namespace Systemson\ApiMaker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

trait ApiResourceTrait
{
    protected $perPage = 20;

    protected function list(string $class, Request $request)
    {
        $listable = (new $class)->getListable();

        $select = empty($listable) ? '*' : $listable;

        // Se aplica el select.
        $query = $class::select($select);

        // se aplica el/los where
        foreach ($listable as $column) {
            if ($request->has($column)) {
                if (is_array($value = $request->get($column))) {
                    $query->whereIn($column, $value);
                }

                $query->where($column, $value);
            } elseif ($request->has($column . '_not')) {
                if (is_array($value = $request->get($column . '_not'))) {
                    $query->whereNotIn($column, $value);
                }

                $query->where($column, '<>', $value);
            } elseif ($request->has($column . '_like')) {
                $query->where($column, 'LIKE', '%' . $request->get($column . '_like') . '%');
            }
        }

        // Se aplica el orderBy
        if ($request->has('order_by')
            &&
            ($order_by = $this->getOrderBy($request, $listable)) !== false
            &&
            $this->validateOrderBy($order_by, $listable)
        ) {
            $query->orderBy($order_by->column, $order_by->sort);
        }

        // Se cargan las relaciones
        $query->with($this->getEagerLoadedRelations($request, $class));

        // Se modifica la consulta antes de enviarla
        if (method_exists($this, 'alterQuery')) {
            $query = $this->alterQuery($query);
        }

        // Se aplica el per page.
        if (!is_null($request->get('per_page')) && $request->get('per_page') == 0) {
            $perPage = $query->count();
        } else {
            $perPage = $request->get('per_page') ?? $this->perPage;
        }

        $query_string = array_merge($listable, ['order_by', 'per_page', 'with']);

        return $query
            ->paginate($perPage)
            ->appends($request->only($query_string));
    }

    private function getOrderBy(Request $request)
    {
        $raw = $request->get('order_by');

        $array = explode(':', $raw);

        if (isset($array[1]) && in_array(strtoupper($array[1]), ['ASC', 'DESC'])) {
            $order = $array[1];
        } else {
            $order = 'ASC';
        }

        return (object) [
            'column' => $array[0],
            'sort' => $order,
        ];
    }

    private function validateOrderBy($order_by, array $columns = [])
    {
        if (!empty($columns)) {
            return in_array($order_by->column, $columns);
        }

        false;
    }

    private function getEagerLoadedRelations(Request $request, $model)
    {
        return collect(explode(',', $request->get('with')))->filter(
            function ($relation) use ($model) {
                return method_exists($model, $relation);
            }
        )->toArray();
    }

    protected function find(string $class, $id)
    {
        $listable = (new $class)->getListable();

        $select = empty($listable) ? '*' : $listable;

        return $class::select($select)
            ->with($this->getEagerLoadedRelations(request(), $class))
            ->findOrFail($id);
    }

    protected function new(string $class, Request $request)
    {
        $resource = new $class;

        $input = $request->only($resource->getFillable());

        $resource->fill($this->alterInput($input));

        $resource->save();

        return $resource;
    }

    protected function alterInput(array $input): array
    {
        return $input;
    }

    protected function edit(Model $resource, Request $request)
    {
        $input = $request->only($resource->getFillable());

        $resource->fill($this->alterInput($input));

        $resource->save();

        return $resource->fresh();
    }
}
