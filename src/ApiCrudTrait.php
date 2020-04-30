<?php

namespace Systemson\ApiMaker;

use Illuminate\Http\Request;

trait ApiCrudTrait
{
    public function index(Request $request)
    {
        return response()->json(
            $this->list(
                $this->class,
                $request
            )
        );
    }

    public function store(Request $request)
    {
        $resource = $this->new(
            $this->class,
            $request
        );

        return response($resource, 201);
    }

    public function show($id)
    {
        return response()->json(
            $this->find(
                $this->class,
                $id
            )
        );
    }

    public function update($id, Request $request)
    {
        $resource = $this->edit(
            $this->class::findOrFail($id),
            $request
        );

        return response($resource);
    }

    public function destroy($id)
    {
        $resource = $this->class::findOrFail($id);

        $resource->delete();

        return response([
            'status' => 'deleted',
            'message' => 'The resource was successfully deleted',
        ]);
    }
}
