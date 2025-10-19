<?php

declare(strict_types=1);

namespace App\Tests\Helpers;

/**
 * Factory for generating test data
 */
class DataFactory
{
    private static int $sequence = 1;

    /**
     * Generate a unique serial number
     */
    public static function serialNumber(string $prefix = 'SN'): string
    {
        return sprintf('%s-%04d-%s', $prefix, self::$sequence++, strtoupper(substr(md5((string)time()), 0, 6)));
    }

    /**
     * Generate a unique asset tag
     */
    public static function assetTag(string $prefix = 'ASSET'): string
    {
        return sprintf('%s-%06d', $prefix, self::$sequence++);
    }

    /**
     * Generate rack label
     */
    public static function rackLabel(string $prefix = 'RCK'): string
    {
        return sprintf('%s-%03d', $prefix, self::$sequence++);
    }

    /**
     * Generate item name
     */
    public static function itemName(string $type = 'Server'): string
    {
        return sprintf('%s-%03d', $type, self::$sequence++);
    }

    /**
     * Generate email address
     */
    public static function email(string $name = 'user'): string
    {
        return sprintf('%s%d@example.com', $name, self::$sequence++);
    }

    /**
     * Generate random price
     */
    public static function price(float $min = 100, float $max = 10000): float
    {
        return round($min + mt_rand() / mt_getrandmax() * ($max - $min), 2);
    }

    /**
     * Generate random date in range
     */
    public static function dateInRange(int $daysBack = 365, int $daysForward = 0): int
    {
        $start = time() - ($daysBack * 86400);
        $end = time() + ($daysForward * 86400);
        return mt_rand($start, $end);
    }

    /**
     * Generate past date
     */
    public static function pastDate(int $daysBack = 365): int
    {
        return time() - (mt_rand(1, $daysBack) * 86400);
    }

    /**
     * Generate future date
     */
    public static function futureDate(int $daysForward = 365): int
    {
        return time() + (mt_rand(1, $daysForward) * 86400);
    }

    /**
     * Generate phone number
     */
    public static function phoneNumber(): string
    {
        return sprintf('+1-%03d-%03d-%04d', mt_rand(200, 999), mt_rand(200, 999), mt_rand(1000, 9999));
    }

    /**
     * Generate IP address
     */
    public static function ipAddress(): string
    {
        return sprintf('%d.%d.%d.%d', mt_rand(1, 254), mt_rand(0, 255), mt_rand(0, 255), mt_rand(1, 254));
    }

    /**
     * Generate MAC address
     */
    public static function macAddress(): string
    {
        return sprintf(
            '%02X:%02X:%02X:%02X:%02X:%02X',
            mt_rand(0, 255),
            mt_rand(0, 255),
            mt_rand(0, 255),
            mt_rand(0, 255),
            mt_rand(0, 255),
            mt_rand(0, 255)
        );
    }

    /**
     * Generate UUID
     */
    public static function uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * Generate lorem ipsum text
     */
    public static function loremIpsum(int $words = 10): string
    {
        $lorem = 'Lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod tempor incididunt ut labore et dolore magna aliqua Ut enim ad minim veniam quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat';
        $words = array_slice(explode(' ', $lorem), 0, $words);
        return implode(' ', $words) . '.';
    }

    /**
     * Pick random element from array
     */
    public static function randomElement(array $array)
    {
        return $array[array_rand($array)];
    }

    /**
     * Pick multiple random elements from array
     */
    public static function randomElements(array $array, int $count): array
    {
        shuffle($array);
        return array_slice($array, 0, min($count, count($array)));
    }

    /**
     * Generate random boolean
     */
    public static function boolean(int $truePercentage = 50): bool
    {
        return mt_rand(1, 100) <= $truePercentage;
    }

    /**
     * Reset sequence counter (useful between tests)
     */
    public static function resetSequence(): void
    {
        self::$sequence = 1;
    }

    /**
     * Create complete item data array
     */
    public static function itemData(array $overrides = []): array
    {
        $defaults = [
            'name' => self::itemName('Server'),
            'serial_number' => self::serialNumber(),
            'asset_tag' => self::assetTag(),
            'item_type_id' => 1,
            'manufacturer_id' => 1,
            'model' => 'PowerEdge R740',
            'status_id' => 1,
            'location_id' => 1,
            'rack_units' => mt_rand(1, 4),
            'purchase_date' => self::pastDate(365),
            'warranty_expiry' => self::futureDate(1095), // 3 years
            'price' => self::price(1000, 10000),
            'comments' => self::loremIpsum(15),
            'created_at' => time(),
            'updated_at' => time(),
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * Create complete rack data array
     */
    public static function rackData(array $overrides = []): array
    {
        $defaults = [
            'label' => self::rackLabel(),
            'model' => 'Dell PowerEdge 4220',
            'size_units' => self::randomElement([4, 6, 9, 12, 18, 24, 42, 45, 48]),
            'depth_mm' => self::randomElement([600, 800, 900, 1000, 1200]),
            'location_id' => 1,
            'reverse_numbering' => self::boolean(20) ? 1 : 0,
            'comments' => self::loremIpsum(10),
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * Create complete software data array
     */
    public static function softwareData(array $overrides = []): array
    {
        $defaults = [
            'title' => 'Microsoft Office',
            'version' => '2021',
            'license_key' => strtoupper(bin2hex(random_bytes(10))),
            'licqty' => mt_rand(1, 50),
            'license_type' => mt_rand(0, 3),
            'manufacturer_id' => 4,
            'purchdate' => self::pastDate(730),
            'comments' => self::loremIpsum(12),
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * Create complete invoice data array
     */
    public static function invoiceData(array $overrides = []): array
    {
        $defaults = [
            'invoice_date' => self::pastDate(365),
            'vendor_id' => 5,
            'buyer_id' => 1,
            'comments' => 'Invoice ' . self::assetTag('INV'),
            'total_cost' => self::price(1000, 50000),
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * Create complete agent data array
     */
    public static function agentData(array $overrides = []): array
    {
        $companies = ['TechCorp', 'DataSystems', 'NetworkPro', 'CloudVendor', 'SoftwarePlus'];
        $name = self::randomElement($companies);

        $defaults = [
            'name' => $name . ' ' . self::$sequence++,
            'address' => mt_rand(100, 9999) . ' Main Street',
            'phone' => self::phoneNumber(),
            'email' => strtolower($name) . '@example.com',
            'website' => 'https://www.' . strtolower($name) . '.com',
            'comments' => self::loremIpsum(8),
            'agent_type' => mt_rand(0, 2),
        ];

        return array_merge($defaults, $overrides);
    }
}
