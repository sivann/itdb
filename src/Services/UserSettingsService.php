<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SettingsModel;
use App\Models\UserModel;

/**
 * User Settings Service - resolves effective user settings (system defaults vs user overrides)
 */
class UserSettingsService
{
    private SettingsModel $settingsModel;
    private UserModel $userModel;

    public function __construct(SettingsModel $settingsModel, UserModel $userModel)
    {
        $this->settingsModel = $settingsModel;
        $this->userModel = $userModel;
    }

    /**
     * Get effective settings for a user (system defaults or user overrides)
     */
    public function getEffectiveSettings(int $userId): array
    {
        // Get user record
        $user = $this->userModel->find($userId);
        if (!$user) {
            return $this->getSystemDefaults();
        }

        // If user uses system defaults, return system settings
        if ($user['use_system_defaults']) {
            return array_merge($this->getSystemDefaults(), [
                'use_system_defaults' => true
            ]);
        }

        // Return user's custom settings
        return [
            'date_format' => $user['date_format'] ?? $this->settingsModel->get('dateformat', 'dmy'),
            'timezone' => $user['timezone'] ?? $this->settingsModel->get('timezone', 'UTC'),
            'language' => $user['language'] ?? $this->settingsModel->get('lang', 'en'),
            'use_system_defaults' => false
        ];
    }

    /**
     * Get system default settings
     */
    public function getSystemDefaults(): array
    {
        return [
            'date_format' => $this->settingsModel->get('dateformat', 'dmy'),
            'timezone' => $this->settingsModel->get('timezone', 'UTC'),
            'language' => $this->settingsModel->get('lang', 'en'),
            'use_system_defaults' => true
        ];
    }

    /**
     * Format a timestamp according to user's date format preference
     */
    public function formatDate(int $userId, int $timestamp): string
    {
        $settings = $this->getEffectiveSettings($userId);
        $format = $this->getPhpDateFormat($settings['date_format']);

        // Set timezone
        $oldTimezone = date_default_timezone_get();
        date_default_timezone_set($settings['timezone']);

        $formatted = date($format, $timestamp);

        // Restore timezone
        date_default_timezone_set($oldTimezone);

        return $formatted;
    }

    /**
     * Convert our date format setting to PHP date() format
     */
    private function getPhpDateFormat(string $format): string
    {
        return match($format) {
            'dmy' => 'd/m/Y',
            'mdy' => 'm/d/Y',
            'ymd' => 'Y-m-d',
            'iso' => 'Y-m-d',
            default => 'd/m/Y'
        };
    }

    /**
     * Get date format for HTML5 date inputs (always YYYY-MM-DD)
     */
    public function getHtmlDateFormat(): string
    {
        return 'Y-m-d';
    }

    /**
     * Update user settings
     */
    public function updateUserSettings(int $userId, array $settings): bool
    {
        $allowedFields = ['date_format', 'timezone', 'language', 'use_system_defaults'];
        $updateData = array_intersect_key($settings, array_flip($allowedFields));

        if (empty($updateData)) {
            return false;
        }

        return $this->userModel->update($userId, $updateData);
    }
}
