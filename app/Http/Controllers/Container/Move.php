<?php

namespace App\Http\Controllers\Container;

use App\Http\Controllers\BaseController;
use App\Models\Container;
use Illuminate\Http\Request;

class Move extends BaseController
{
    public function __invoke(Request $request, $id)
    {
        $container = Container::findOrFail($id);
        $container->paychecks()->get()->each(function ($paycheck) use ($request) {
            $paycheck->project_id = $request->input('target_project_id');
            $paycheck->save();
        });

        $container->project_id = $request->input('target_project_id');
        $container->save();

        return $this->successResponse([
            'message' => 'Container moved successfully',
        ]);
    }
}