<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::query()->with('roles:id,name');


        if ($request->filled('q')) {
            $searchTerm = '%' . $request->q . '%';
            $query->where(function ($subQuery) use ($searchTerm) {
                $subQuery->where('name', 'like', $searchTerm)
                    ->orWhere('email', 'like', $searchTerm);
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', function ($roleQuery) use ($request) {
                $roleQuery->where('name', $request->role);
            });
        }
        if ($request->filled('sortBy')) {
            $direction = $request->input('orderBy', 'asc'); // Default to 'asc'
            $sortableColumns = ['name', 'email', 'created_at'];
            if (in_array($request->sortBy, $sortableColumns)) {
                if ($request->sortBy === 'role') {
                } elseif (in_array($request->sortBy, ['name', 'email', 'created_at'])) {
                    $query->orderBy($request->sortBy, $direction);
                }
            }
        } else {
            $query->orderBy('created_at', 'desc'); // Default sort
        }


        $perPage = $request->input('itemsPerPage', 10);
        if ($perPage == -1) { // Handle "All"
            $usersCollection = $query->get();
            return response()->json([
                'users' => $usersCollection,
                'totalUsers' => $usersCollection->count(),
            ]);
        }

        $users = $query->paginate($perPage);

        return response()->json([
            'data' => [
                'users' => $users->items(),
                'totalUsers' => $users->total(),
                // Optionally include pagination info
                //'currentPage' => $users->currentPage(),
                // 'lastPage' => $users->lastPage(),
                // 'perPage' => $users->perPage(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
