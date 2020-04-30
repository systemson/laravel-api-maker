<?php

namespace Systemson\ApiMaker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait ApiResourceTrait
{
	protected $perPage = 15;

    protected function list(string $class, Request $request)
    {
    	$listable = (new $class)->getListable();

    	$select = empty($listable) ? '*' : $listable;

        $query = $class::select($select)
        	->where($request->only($listable))
        ;

        if ($request->has('order_by') && ($order_by = $this->getOrderBy($request, $listable)) !== false && $this->validateOrderBy($order_by, $listable)) {
        	$query->orderBy($order_by->column, $order_by->sort);
        }

        $query_string = array_merge($listable, ['order_by', 'per_page']);

        return $query->paginate(
        	$request->get('per_page') ?? $this->perPage
        )->appends($request->only($query_string));
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

    protected function find(string $class, $id)
    {
    	return $class::findOrFail($id);
    }

    protected function new(string $class, Request $request)
    {
    	$resource =  new $class;

    	$input = $request->only($resource->getFillable());

    	$resource->fill($this->alterInput($input));

    	if ($resource->validate($request)) {
    		$resource->save();
    	} else {
    		return abort(422);
    	}

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

    	if ($resource->validate($request)) {
    		$resource->save();
    	} else {
    		return abort(422);
    	}

    	return $resource;
    }
}
