@php
    $parser = app(\App\Support\Questions\QuestionParser::class);
    $pointsService = ! empty($showPoints) ? app(\App\Services\AssessmentSubmissionService::class) : null;
    $rawSaved = is_array($savedAnswers ?? null) ? $savedAnswers : [];
    $savedAnswers = [];

    foreach ($rawSaved as $id => $value) {
        if (is_array($value)) {
            $savedAnswers[(int) $id] = (string) ($value['selected_answer'] ?? $value['answer_text'] ?? '');
            continue;
        }

        $savedAnswers[(int) $id] = (string) $value;
    }
@endphp

<div class="session-quiz-list sw-quiz-frame">
    @include('courses._session_quiz_question_form_list', [
        'quizQuestions' => $quizQuestions ?? [],
        'savedAnswers' => $savedAnswers ?? [],
        'showPoints' => ! empty($showPoints),
        'answerModel' => $pointsService,
        'richTextModel' => app(\App\Support\RichContent::class),
        'answerLabels' => ['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'],
    ])
</div>
