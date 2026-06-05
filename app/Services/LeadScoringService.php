<?php

namespace App\Services;

use App\Models\Lead;

class LeadScoringService
{
    public function calculate(Lead $lead): int
    {
        $score = 0;

        if (!empty($lead->product_interest)) {
            $score += 15;
        }

        if (!empty($lead->name)) {
            $score += 10;
        }

        if (!empty($lead->email)) {
            $score += 25;
        }

        if (!empty($lead->phone)) {
            $score += 25;
        }

        if (!empty($lead->country)) {
            $score += 10;
        }

        if (!empty($lead->preferred_contact_time)) {
            $score += 15;
        }

        return min($score, 100);
    }

    public function updateScore(Lead $lead): Lead
    {
        $score = $this->calculate($lead);

        $lead->lead_score = $score;

        if ($score >= 60 && $lead->status === 'new') {
            $lead->status = 'qualified';
            $lead->qualified_at = now();
        }

        $lead->save();

        return $lead;
    }
}