<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\ReportAnnotation;
use Illuminate\Http\Request;

class ReportAnnotationController extends Controller
{
    public function index(Report $report)
    {
        $annotations = $report->annotations()->with('user')->get();
        return response()->json($annotations);
    }

    public function store(Request $request, Report $report)
    {
        $user = $request->user();

        $validated = $request->validate([
            'page' => 'required|integer|min:1',
            'x' => 'required|numeric',
            'y' => 'required|numeric',
            'content' => 'required|string|max:1000',
        ]);

        $annotation = $report->annotations()->create([
            'user_id' => $user->id,
            'page' => $validated['page'],
            'x' => $validated['x'],
            'y' => $validated['y'],
            'content' => $validated['content'],
        ]);

        return response()->json($annotation->load('user'), 201);
    }

    public function destroy(ReportAnnotation $annotation)
    {
        $user = request()->user();
        if ($annotation->user_id !== $user->id && !in_array($user->role, ['rup_projet', 'rup_specialite', 'professeur'])) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $annotation->delete();
        return response()->json(['message' => 'Annotation supprimée']);
    }
}