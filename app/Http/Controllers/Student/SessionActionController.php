<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\StudentSessionActionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SessionActionController extends Controller
{
    public function __construct(private StudentSessionActionService $actions)
    {
    }

    public function join(Request $request, string $code): RedirectResponse
    {
        return $this->actions->join($code, (int) $request->user()->id);
    }

    public function submitPreQuiz(Request $request, string $code): RedirectResponse
    {
        return $this->actions->submitPreQuiz($code, (int) $request->user()->id, $request->input('answers'));
    }

    public function autosavePreQuiz(Request $request, string $code): JsonResponse
    {
        return $this->actions->autosavePreQuiz($code, (int) $request->user()->id, $request);
    }

    public function resetPreQuiz(Request $request, string $code): RedirectResponse
    {
        return $this->actions->enterPrePractice($code, (int) $request->user()->id);
    }

    public function exitPreQuizPractice(Request $request, string $code): RedirectResponse
    {
        return $this->actions->exitPrePractice($code, (int) $request->user()->id);
    }

    public function submitPostQuiz(Request $request, string $code): RedirectResponse
    {
        return $this->actions->submitPostQuiz($code, (int) $request->user()->id, $request->input('answers'));
    }

    public function autosavePostQuiz(Request $request, string $code): JsonResponse
    {
        return $this->actions->autosavePostQuiz($code, (int) $request->user()->id, $request);
    }

    public function resetPostQuiz(Request $request, string $code): RedirectResponse
    {
        return $this->actions->enterPostPractice($code, (int) $request->user()->id);
    }

    public function exitPostQuizPractice(Request $request, string $code): RedirectResponse
    {
        return $this->actions->exitPostPractice($code, (int) $request->user()->id);
    }

    public function startKnowledgeReview(Request $request, string $code): RedirectResponse
    {
        return $this->actions->startKnowledgeReview($code, (int) $request->user()->id);
    }

    public function submitKnowledgeReview(Request $request, string $code): RedirectResponse
    {
        return $this->actions->submitKnowledgeReview(
            $code,
            (int) $request->user()->id,
            $request->input('answers'),
            $request->input('timed_out') === '1'
        );
    }

    public function autosaveKnowledgeReview(Request $request, string $code): JsonResponse
    {
        return $this->actions->autosaveKnowledgeReview($code, (int) $request->user()->id, $request);
    }

    public function resetKnowledgeReview(Request $request, string $code): RedirectResponse
    {
        return $this->actions->enterKnowledgeReviewPractice($code, (int) $request->user()->id);
    }

    public function exitKnowledgeReviewPractice(Request $request, string $code): RedirectResponse
    {
        return $this->actions->exitKnowledgeReviewPractice($code, (int) $request->user()->id);
    }

    public function saveReflection(Request $request, string $code): RedirectResponse
    {
        return $this->actions->saveReflection(
            $code,
            (int) $request->user()->id,
            (string) $request->input('student_comment', ''),
            $request->input('session_rating')
        );
    }
}
