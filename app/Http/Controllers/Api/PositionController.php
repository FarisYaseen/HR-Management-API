<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Position;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PositionController extends Controller
{
    public function index(): JsonResponse
    {
        $positions = Position::latest('id')->get();

        return response()->json($positions);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:positions,name'],
            'description' => ['nullable', 'string'],
        ]);

        $position = Position::create($data);

        return response()->json([
            'message' => 'Position created successfully.',
            'data' => $position,
        ], 201);
    }

    public function show(Position $position): JsonResponse
    {
        return response()->json($position);
    }

    public function update(Request $request, Position $position): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('positions', 'name')->ignore($position->id)],
            'description' => ['nullable', 'string'],
        ]);

        $position->update($data);

        return response()->json([
            'message' => 'Position updated successfully.',
            'data' => $position->fresh(),
        ]);
    }

    public function destroy(Position $position): JsonResponse
    {
        $position->delete();

        return response()->json([
            'message' => 'Position deleted successfully.',
        ]);
    }
}
