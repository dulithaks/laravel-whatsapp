<?php

namespace Duli\WhatsApp\Support;

/**
 * Shared helpers used by the webhook controller and queued webhook jobs.
 */
trait WebhookHelpers
{
    /**
     * Status hierarchy for preventing downgrades.
     * Higher value = higher priority.
     */
    protected const STATUS_HIERARCHY = [
        'pending'   => 0,
        'sent'      => 1,
        'delivered' => 2,
        'read'      => 3,
        'failed'    => 4,
    ];

    /**
     * Validate phone number format (E.164 — digits only, 1–15 chars).
     */
    protected function isValidPhoneNumber(string $phoneNumber): bool
    {
        return preg_match('/^\d{1,15}$/', $phoneNumber) === 1;
    }

    /**
     * Sanitize user-supplied text: strip null bytes and trim whitespace.
     */
    protected function sanitizeInput(string $input): string
    {
        return trim(str_replace("\0", '', $input));
    }

    /**
     * Determine whether the new status should replace the current one.
     * Prevents late-arriving lower-priority webhooks from downgrading a record.
     */
    protected function statusShouldUpdate(string $currentStatus, string $newStatus): bool
    {
        $currentPriority = self::STATUS_HIERARCHY[$currentStatus] ?? -1;
        $newPriority     = self::STATUS_HIERARCHY[$newStatus]     ?? -1;

        return $newPriority >= $currentPriority;
    }
}
