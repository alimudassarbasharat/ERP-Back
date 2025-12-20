<?php

namespace App\Http\Controllers\Department;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $departments = DB::table('departments')
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => true,
            'result' => $departments,
        ]);
    }


    public function store(Request $request)
    {
        // dd($request->all());
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:departments,name',
            'code' => 'required|string|max:50|unique:departments,code',
        ]);


    $department = Department::create($validatedData);

    return response()->json([
        'status' => true,
        'message' => 'Department created successfully.',
        'result' => $department
    ], 201);
    }

    public function edit($id)
    {
        $department = DB::table('departments')
            ->select('id', 'name', 'code')
            ->where('id', $id)
            ->first();

        if (!$department) {
            return response()->json([
                'status' => false,
                'message' => 'Department not found.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'result' => $department,
        ]);
    }
}


