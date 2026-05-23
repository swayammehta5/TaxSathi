<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function current(Request $request)
    {
        return response()->json(
            (new UserResource($request->user()))->resolve()
        );
    }

    public function index()
    {
        $users = User::query()
            ->select(['id', 'name', 'email', 'role'])
            ->orderBy('name')
            ->get();

        return UserResource::collection($users);
    }
}
