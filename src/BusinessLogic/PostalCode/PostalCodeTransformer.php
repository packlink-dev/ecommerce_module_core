<?php

namespace Packlink\BusinessLogic\PostalCode;

/**
 * Class PostalCodeTransformer
 *
 * @package Packlink\BusinessLogic\PostalCode
 */
class PostalCodeTransformer
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;
    /**
     * The map of the country codes and their supported formats.
     *
     * The formats in this map adhere to the following rules:
     *  - "*" marks a character (a letter or a number) in the postal code.
     *  - Everything else (spaces and dashes) represent actual characters that should be in that position.
     *
     * @var array
     */
    private $map = array(
        'UK' => array(
            '** ***',
            '*** ***',
            '**** ***',
        ),
        'NL' => array(
            '**** **',
            '****',
        ),
        'PT' => array(
            '****-***',
            '****',
        ),
        'US' => array(
            '*****-****',
            '*****',
        ),
    );

    /**
     * Transforms the postal code into one that matches the postal code format for the country
     * identified by the provided country code. If there are no defined formats for the given
     * country, no transformation will be performed.
     *
     * @param string $countryCode
     * @param string $postalCode
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function transform($countryCode, $postalCode)
    {
        if (!array_key_exists($countryCode, $this->map)) {
            return $postalCode;
        }

        $supportedFormats = $this->map[$countryCode];

        foreach ($supportedFormats as $supportedFormat) {
            if (preg_match_all('/[a-zA-Z\d]/', $postalCode) === substr_count($supportedFormat, '*')) {
                // Special case for US postal codes.
                if ($countryCode === 'US') {
                    return substr($postalCode, 0, 5);
                }

                return $this->transformToFormat($postalCode, $supportedFormat);
            }
        }

        throw new \InvalidArgumentException('Invalid postal code provided');
    }

    /**
     * Transforms the postal code to match the provided format.
     * If the postal code is already in that format, no transformation is performed.
     *
     * @param string $postalCode
     * @param string $format
     *
     * @return string
     */
    private function transformToFormat($postalCode, $format)
    {
        if (preg_replace('/[a-zA-Z\d]/', '*', $postalCode) === $format) {
            return $postalCode;
        }

        $trimmedPostalCode = preg_replace('/[^a-zA-Z\d]/', '', $postalCode);
        $currentCharacter = 0;
        $transformedPostalCode = '';
        for ($i = 0, $iMax = strlen($format); $i < $iMax; $i++) {
            $transformedPostalCode .= $format[$i] === '*' ? $trimmedPostalCode[$currentCharacter++] : $format[$i];
        }

        return $transformedPostalCode;
    }
}
