<?php

declare(strict_types=1);

namespace App\Support;

final class SubmissionLabels
{
    public static function categoryLabel(string $category): string
    {
        return match ($category) {
            'general_enquiry' => 'General enquiry',
            'feature_request' => 'Feature request',
            'partnership' => 'Partnership / collaboration',
            'bug_report' => 'Bug Report',
            default => $category,
        };
    }

    public static function statusLabel(bool $ignored, ?string $followUpSentAt): string
    {
        if ($ignored) {
            return 'Ignored';
        }
        if ($followUpSentAt !== null && $followUpSentAt !== '') {
            return 'Replied';
        }

        return 'New';
    }
}
