@include('student.partials.quiz-block', [
    'quizConfig' => $quizConfig ?? [],
    'quizKey' => $quizKey ?? 'pre',
    'session' => $session ?? null,
])
