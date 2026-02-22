<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Test;
use App\Models\TestAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Get user profile
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Stats
        $stats = $user->stats ?? [
            'tests_created' => 0,
            'tests_completed' => 0,
            'tests_passed' => 0,
            'average_score' => 0,
            'perfect_scores' => 0,
            'current_streak' => 0,
        ];

        // Created tests
        $createdTests = Test::where('user_id', $user->id)
            ->where('is_public', true)
            ->where('status', 'published')
            ->withCount('questions')
            ->withCount(['attempts as completed_attempts_count' => function ($q) {
                $q->where('is_completed', true);
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        // Completed tests
        $completedTests = TestAttempt::where('user_id', $user->id)
            ->where('is_completed', true)
            ->whereHas('test', function ($q) {
                $q->where('is_public', true);
            })
            ->with(['test:id,title', 'test.user:id,name'])
            ->orderBy('completed_at', 'desc')
            ->limit(20)
            ->get();

        // Recent activity
        $createdActivity = Test::where('user_id', $user->id)
            ->where('is_public', true)
            ->where('status', 'published')
            ->select('id', 'title', 'created_at as date')
            ->get()
            ->map(fn($t) => ['type' => 'test', 'title' => $t->title, 'date' => $t->date, 'id' => $t->id]);

        $attemptActivity = TestAttempt::where('user_id', $user->id)
            ->where('is_completed', true)
            ->whereHas('test', fn($q) => $q->where('is_public', true))
            ->with('test:id,title')
            ->select('id', 'test_id', 'completed_at as date')
            ->get()
            ->map(fn($a) => ['type' => 'attempt', 'title' => $a->test->title, 'date' => $a->date, 'id' => $a->id]);

        $recentActivity = $createdActivity->concat($attemptActivity)
            ->sortByDesc('date')
            ->take(30)
            ->values();

        $isOwnProfile = auth('sanctum')->check() && auth('sanctum')->id() === $user->id;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $isOwnProfile ? $user->email : null,
                'avatar' => $user->avatar,
                'bio' => $user->bio,
                'role' => $user->role,
                'created_at' => $user->created_at,
                'last_login' => $user->last_login,
            ],
            'stats' => $stats,
            'created_tests' => $createdTests,
            'completed_tests' => $completedTests,
            'recent_activity' => $recentActivity,
            'is_own_profile' => $isOwnProfile,
        ]);
    }
}
