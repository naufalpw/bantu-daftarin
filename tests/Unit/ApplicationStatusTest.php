<?php

namespace Tests\Unit;

use App\Enums\ApplicationStatus;
use PHPUnit\Framework\TestCase;

class ApplicationStatusTest extends TestCase
{
    public function test_only_declared_transitions_are_allowed(): void
    {
        $this->assertTrue(ApplicationStatus::DRAFT->canTransitionTo(ApplicationStatus::AWAITING_DOCUMENTS));
        $this->assertFalse(ApplicationStatus::DRAFT->canTransitionTo(ApplicationStatus::COMPLETED));
        $this->assertTrue(ApplicationStatus::UNDER_REVIEW->canTransitionTo(ApplicationStatus::REVISION_REQUIRED));
        $this->assertTrue(ApplicationStatus::RESULT_REVIEW->canTransitionTo(ApplicationStatus::COMPLETED));
        $this->assertFalse(ApplicationStatus::COMPLETED->canTransitionTo(ApplicationStatus::IN_PROGRESS));
        $this->assertTrue(ApplicationStatus::AWAITING_PAYMENT->canTransitionTo(ApplicationStatus::CANCELLED));
        $this->assertFalse(ApplicationStatus::CANCELLED->canTransitionTo(ApplicationStatus::DRAFT));
    }

    public function test_declared_lifecycle_transition_matrix_is_exact(): void
    {
        $expected = [
            ApplicationStatus::DRAFT->value => [ApplicationStatus::AWAITING_DOCUMENTS->value, ApplicationStatus::CANCELLED->value],
            ApplicationStatus::AWAITING_DOCUMENTS->value => [ApplicationStatus::DOCUMENTS_READY_FOR_PAYMENT->value, ApplicationStatus::CANCELLED->value],
            ApplicationStatus::DOCUMENTS_READY_FOR_PAYMENT->value => [ApplicationStatus::AWAITING_PAYMENT->value, ApplicationStatus::CANCELLED->value],
            ApplicationStatus::AWAITING_PAYMENT->value => [ApplicationStatus::PAYMENT_CONFIRMED->value, ApplicationStatus::CANCELLED->value],
            ApplicationStatus::PAYMENT_CONFIRMED->value => [ApplicationStatus::DOCUMENTS_SUBMITTED->value],
            ApplicationStatus::DOCUMENTS_SUBMITTED->value => [ApplicationStatus::UNDER_REVIEW->value],
            ApplicationStatus::UNDER_REVIEW->value => [ApplicationStatus::DOCUMENTS_ACCEPTED->value, ApplicationStatus::REVISION_REQUIRED->value],
            ApplicationStatus::DOCUMENTS_ACCEPTED->value => [ApplicationStatus::ESTIMATE_PENDING->value],
            ApplicationStatus::ESTIMATE_PENDING->value => [ApplicationStatus::IN_PROGRESS->value],
            ApplicationStatus::IN_PROGRESS->value => [ApplicationStatus::WAITING_EXTERNAL_PROCESS->value],
            ApplicationStatus::WAITING_EXTERNAL_PROCESS->value => [ApplicationStatus::RESULT_UPLOADED->value],
            ApplicationStatus::RESULT_UPLOADED->value => [ApplicationStatus::RESULT_REVIEW->value],
            ApplicationStatus::RESULT_REVIEW->value => [ApplicationStatus::COMPLETED->value],
            ApplicationStatus::COMPLETED->value => [ApplicationStatus::ARCHIVED->value],
            ApplicationStatus::REVISION_REQUIRED->value => [ApplicationStatus::REVISION_SUBMITTED->value],
            ApplicationStatus::REVISION_SUBMITTED->value => [ApplicationStatus::UNDER_REVIEW->value],
            ApplicationStatus::ARCHIVED->value => [],
            ApplicationStatus::CANCELLED->value => [],
        ];

        foreach (ApplicationStatus::cases() as $current) {
            $actual = [];
            foreach (ApplicationStatus::cases() as $target) {
                if ($current->canTransitionTo($target)) {
                    $actual[] = $target->value;
                }
            }

            $this->assertSame($expected[$current->value], $actual, $current->value);
        }
    }
}
