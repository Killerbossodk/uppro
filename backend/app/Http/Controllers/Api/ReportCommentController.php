<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\ReportComment;
use Illuminate\Http\Request;

class ReportCommentController extends Controller
{
    public function index(Report $report)
    {
        $comments = $report->comments()->with('user')->latest()->get();
        return response()->json($comments);
    }

    public function store(Request $request, Report $report)
    {
        $user = $request->user();
        $validated = $request->validate([
            'content' => 'required|string'
        ]);

        $comment = $report->comments()->create([
            'user_id'   => $user->id,
            'content'   => $validated['content']
        ]);

        return response()->json($comment->load('user'), 201);
    }
}