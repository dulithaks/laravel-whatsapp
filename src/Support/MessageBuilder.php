<?php

namespace Duli\WhatsApp\Support;

/**
 * Helper class for building WhatsApp message payloads
 */
class MessageBuilder
{
    /**
     * Build a list section
     * 
     * @param string $title Section title
     * @param array $rows Array of rows [['id' => '', 'title' => '', 'description' => ''], ...]
     * @return array
     */
    public static function buildListSection(string $title, array $rows): array
    {
        return [
            'title' => $title,
            'rows' => collect($rows)->map(function ($row) {
                return [
                    'id' => $row['id'],
                    'title' => $row['title'],
                    'description' => $row['description'] ?? '',
                ];
            })->toArray(),
        ];
    }

    /**
     * Build buttons for interactive message
     * 
     * @param array $buttons Array of buttons [['id' => '', 'title' => ''], ...]
     * @param int $max Maximum buttons (default: 3)
     * @return array
     */
    public static function buildButtons(array $buttons, int $max = 3): array
    {
        return collect($buttons)->take($max)->map(function ($button) {
            return [
                'id' => $button['id'],
                'title' => substr($button['title'], 0, 20), // Max 20 chars
            ];
        })->toArray();
    }

    /**
     * Format phone number to international format
     * 
     * @param string $phone Phone number
     * @param string|null $countryCode Country code (e.g., '1' for US)
     * @return string
     */
    public static function formatPhoneNumber(string $phone, ?string $countryCode = null): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Remove leading zeros
        $phone = ltrim($phone, '0');

        // Add country code if provided and not already present
        if ($countryCode && !str_starts_with($phone, $countryCode)) {
            $phone = $countryCode . $phone;
        }

        return $phone;
    }

    /**
     * Validate phone number format
     * 
     * @param string $phone Phone number
     * @return bool
     */
    public static function isValidPhoneNumber(string $phone): bool
    {
        // Basic validation: only numbers, 10-15 digits
        return preg_match('/^[0-9]{10,15}$/', $phone) === 1;
    }

    /**
     * Truncate text to maximum length
     * 
     * @param string $text Text to truncate
     * @param int $maxLength Maximum length
     * @param string $suffix Suffix to add if truncated
     * @return string
     */
    public static function truncateText(string $text, int $maxLength, string $suffix = '...'): string
    {
        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }

        return mb_substr($text, 0, $maxLength - mb_strlen($suffix)) . $suffix;
    }
}
