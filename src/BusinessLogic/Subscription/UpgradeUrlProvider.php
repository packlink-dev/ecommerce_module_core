<?php

namespace Packlink\BusinessLogic\Subscription;

/**
 * Class UpgradeUrlProvider. Builds the Packlink PRO plan upgrade URL for the merchant's market.
 *
 * @package Packlink\BusinessLogic\Subscription
 */
class UpgradeUrlProvider
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Country-to-Packlink-domain mapping for the upgrade URL.
     *
     * @var array
     */
    private static $platformDomains = array(
        'ES' => 'es',
        'FR' => 'fr',
        'DE' => 'de',
        'IT' => 'it',
    );

    /**
     * Returns the upgrade URL for a market; unknown markets use the international domain.
     *
     * @param string $country Two-letter uppercase country code, or an empty string.
     *
     * @return string
     */
    public static function getUpgradeUrl($country)
    {
        $domain = isset(self::$platformDomains[$country]) ? self::$platformDomains[$country] : 'com';

        return 'https://pro.packlink.' . $domain . '/private/subscriptions';
    }
}
