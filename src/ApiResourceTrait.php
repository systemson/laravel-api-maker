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
        $model = (new $class);

        $listable = $model->getListable();

        $select = empty($listable) ? '*' : $listable;

        $select = !is_array($select) ? $select : array_map(function ($column) use ($model) {
            return "{$model->getTable()}.{$column}";
        }, $select);

        // Se aplica el select.
        $query = $model->select($select);

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
            $this->validateOrderBy($order_by, $model, $listable)
        ) {
            $query = $this->applyOrderBy($query, $order_by);
        }

        // Se cargan las relaciones
        $query->with($this->getEagerLoadedRelations($request, $model));

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

        return (object) [
            'column' => $array[0],
            'order' => $array[1] ?? 'ASC',
        ];
    }

    private function validateOrderBy($order_by, $model, array $columns = [])
    {
        if (empty($columns) || !in_array(strtoupper($order_by->order), ['ASC', 'DESC'])) {
            return false;
        }

        if (in_array($order_by->column, $columns)) {
            return true;
        } elseif (count($name_array = explode('.', $order_by->column)) == 2) {
            return method_exists($model, $name_array[0]);
        }

        return false;
    }

    private function applyOrderBy($query, $order_by)
    {
        if (count($columnaArray = explode('.', $order_by->column)) == 2) {
            $model = $query->getModel();
            $relation = $model->{$columnaArray[0]}();

            return $query->join(
                $table = $relation->getRelated()->getTable(),
                $relation->getQualifiedForeignKeyName(),
                $relation->getQualifiedOwnerKeyName()
            )->orderBy("{$table}.{$columnaArray[1]}", $order_by->order);
        }
        return $query->orderBy($order_by->column, $order_by->order);
    }

    private function getEagerLoadedRelations(Request $request, $model)
    {
        return collect(explode(',', $request->get('with')))->filter(
            function ($relation) use ($model) {
                return method_exists($model, $relation);
            }
        )->toArray();
    }

    protected function find(string $class, $id, $pk = 'id')
    {
        $model = (new $class);

        $listable = $model->getListable();

        if ($model->getKeyName() != $pk) {
            $model->setKeyName($pk);
        }

        $select = empty($listable) ? '*' : $listable;

        return $model->select($select)
            ->with($this->getEagerLoadedRelations(request(), $class))
            ->findOrFail($id);
    }

    protected function new(string $class, Request $request)
    {
        $resource = new $class;

        $input = $request->only($resource->getFillable());

        $resource->fill($this->alterInput($input));

        $resource->save();

        return $resource->refresh();
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
