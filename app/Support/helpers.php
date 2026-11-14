<?php

use App\Support\Questions\QuestionParser;

if (! function_exists('esc')) {
    function esc(mixed $value, string $context = 'html'): string
    {
        return e((string) $value);
    }
}

if (! function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        return url($path);
    }
}

if (! function_exists('media_url')) {
    function media_url(string $path = ''): string
    {
        return \App\Support\MediaUrl::to($path);
    }
}

if (! function_exists('csrf_hash')) {
    function csrf_hash(): string
    {
        return csrf_token();
    }
}

if (! function_exists('model')) {
    function model(string $class): object
    {
        if (str_contains($class, 'StudyProgramSessionQuestionModel')) {
            return app(QuestionParser::class);
        }

        if (str_contains($class, 'QuestionBankItemModel')) {
            return app(\App\Support\Questions\QuestionBankItemModel::class);
        }

        if (str_contains($class, 'CourseMonthlyEvaluationModel')) {
            return app(\App\Support\RichContent::class);
        }

        if (str_contains($class, 'CourseAssessmentAnswerModel')) {
            return app(\App\Services\AssessmentSubmissionService::class);
        }

        return app($class);
    }
}
