@include('student.partials.quiz-form', [
    'quizQuestions' => $quizQuestions ?? [],
    'savedAnswers' => $savedAnswers ?? [],
    'showPoints' => $showPoints ?? false,
])
