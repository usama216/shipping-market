<?php

namespace App\Carriers;

/**
 * CarrierErrorTranslator - Translates technical carrier API error codes
 * into operator-friendly messages with actionable guidance.
 */
class CarrierErrorTranslator
{
    /**
     * Error code patterns mapped to friendly messages.
     * Patterns are checked in order - first match wins.
     */
    private static array $errorPatterns = [
        // Service Type Errors
        'SERVICETYPE.INVALID' => 'The selected shipping service is not available for this destination. Try a different carrier or service.',
        'UPSSERVICETYPE.INVALID' => 'UPS service not available for this origin/destination combination.',
        'SERVICE.NOT.AVAILABLE' => 'The selected service is not available for this route.',
        'PRODUCT.NOT.AVAILABLE' => 'This shipping product is not available for the selected destination.',

        // Packaging Errors
        'PACKAGINGTYPE.INVALID' => 'The packaging type is incompatible with the service or weight. Check package dimensions and weight.',
        'PACKAGING.INVALID' => 'Invalid packaging type selected. Please verify package details.',
        'PIECE.TYPE.INVALID' => 'The package type is not valid for this shipment.',

        // Postal Code / ZIP Errors
        'POSTALCODEORZIP.INVALID' => 'The destination postal code is invalid. Verify the customer\'s address.',
        'POSTALCODE.INVALID' => 'Invalid postal code format for the destination country.',
        'POSTCODE.NOT.FOUND' => 'The postal code could not be found. Please verify the address.',
        'POSTAL.CODE.INVALID' => 'The postal code format is incorrect for this country.',

        // Address Validation Errors
        'RECIPIENT.ADDRESS.ERROR' => 'The recipient\'s address could not be validated. Check for missing or incorrect details.',
        'ADDRESS.VALIDATION' => 'Address validation failed. Verify all address fields are correct.',
        'ADDRESS.INVALID' => 'The address provided is invalid. Please check and correct it.',
        'ADDRESSLINE.INVALID' => 'One of the address lines is too long or contains invalid characters.',
        'DESTINATION.INVALID' => 'The destination address is invalid or incomplete.',
        'ORIGIN.INVALID' => 'The origin/shipper address is invalid or incomplete.',

        // Country Errors
        'RECIPIENT.COUNTRY.INVALID' => 'The destination country code is invalid or unsupported.',
        'COUNTRY.INVALID' => 'Invalid country code provided.',
        'COUNTRY.NOT.FOUND' => 'The country code could not be recognized.',

        // Prohibited Items / Commodities
        'COMMODITY.PROHIBITED' => 'This shipment contains items prohibited from shipping to this destination.',
        'SHIPMENT.COMMODITY.PROHIBITED' => 'One or more items are prohibited for this destination. Review shipment contents.',
        'PROHIBITED' => 'This shipment contains prohibited items.',
        'RESTRICTED' => 'This shipment contains restricted items that require special handling.',

        // Customs / Duties / Export
        'CUSTOMS' => 'Customs documentation issue. Check declared value and item descriptions.',
        'DECLARED.VALUE.INVALID' => 'The declared value is missing or exceeds the limit.',
        'DECLARED.VALUE.MISSING' => 'Declared value is required for this international shipment.',
        'CUSTOMS.VALUE' => 'Customs value must be greater than zero.',
        'COMMODITY.CODE' => 'Invalid or missing commodity/HS code.',

        // Weight / Dimension Errors
        'WEIGHT.INVALID' => 'Package weight is invalid. Verify measurements.',
        'WEIGHT.MISSING' => 'Package weight is required.',
        'WEIGHT.EXCEEDED' => 'Package weight exceeds the maximum allowed for this service.',
        'DIMENSION.INVALID' => 'Package dimensions are invalid. Verify measurements.',
        'DIMENSIONS.MISSING' => 'Package dimensions are required for this service.',

        // Tax ID Errors
        'TAXID.INVALID' => 'Customer\'s Tax ID is invalid or missing for this international shipment.',
        'TIN.INVALID' => 'Tax Identification Number is invalid.',
        'RECIPIENT.TAXID' => 'Recipient Tax ID exceeds maximum length or is invalid.',

        // Account / Shipper Errors
        'ACCOUNT.INVALID' => 'Carrier account issue. Please contact support.',
        'ACCOUNT.NOT.FOUND' => 'The shipping account number could not be verified.',
        'SHIPPER.NUMBER.INVALID' => 'The shipper account number is invalid.',
        'SHIPPER.ACCOUNT.INVALID' => 'The shipper account is not valid for this type of shipment.',

        // Authentication Errors
        'CREDENTIAL' => 'Carrier authentication failed. Please contact support.',
        'AUTHENTICATION' => 'Carrier authentication error. Please contact support.',
        'ACCESS.TOKEN' => 'Carrier access token is invalid or expired. Please retry.',
        'INVALID.CREDENTIALS' => 'Carrier credentials are invalid. Please contact support.',

        // Rate Limiting
        'RATE.LIMIT' => 'Carrier API is busy. Please retry in a few minutes.',
        'TOO.MANY.REQUESTS' => 'Too many requests to carrier. Please wait and retry.',
        'THROTTL' => 'Request rate limit exceeded. Please retry later.',

        // Network / Connectivity
        'TIMEOUT' => 'Connection to carrier timed out. Please retry.',
        'CONNECTION' => 'Unable to connect to carrier. Please check and retry.',
        'UNAVAILABLE' => 'Carrier service is temporarily unavailable. Please retry later.',
        'NETWORK' => 'Network error connecting to carrier. Please retry.',

        // Residential Delivery
        'RESIDENTIAL.DELIVERY' => 'This address requires residential delivery service.',
        'HOME.DELIVERY' => 'Home delivery service required for this address.',

        // DHL Specific
        'SV011A' => 'DHL cannot determine the service for this destination. Verify the address.',
        'SV012A' => 'DHL could not verify the shipping account. Please contact support.',
        'CONTENT.DESCRIPTION.MISSING' => 'Content description is required for international DHL shipments.',

        // UPS Specific
        '1Z' => 'UPS tracking number format is invalid.',
        'SHIP.FROM.COUNTRY' => 'The origin country must match the shipper\'s registered country.',
    ];

    /**
     * Translate a raw error message into a friendly operator message.
     */
    public static function translate(string $rawMessage): string
    {
        $upperMessage = strtoupper($rawMessage);

        // Check if message contains DHL validation errors (422 errors with additionalDetails)
        if (str_contains($rawMessage, 'Validation Errors:') || str_contains($rawMessage, 'additionalDetails')) {
            // Return the message as-is since it already contains validation details
            return $rawMessage;
        }

        // Check each pattern
        foreach (self::$errorPatterns as $pattern => $friendlyMessage) {
            if (str_contains($upperMessage, strtoupper($pattern))) {
                return $friendlyMessage;
            }
        }

        // Try to extract error code from JSON response
        $extracted = self::extractErrorFromJson($rawMessage);
        if ($extracted) {
            return $extracted;
        }

        // Check for DHL validation error format
        if (str_contains($upperMessage, 'VALIDATION') && str_contains($upperMessage, 'ADDITIONAL DETAILS')) {
            return 'DHL validation error: Please check the shipment details. See error logs for specific field errors.';
        }

        // Default fallback
        return 'An error occurred with the carrier. Please review shipment details or contact support.';
    }

    /**
     * Try to extract and translate error codes from JSON in the message.
     */
    private static function extractErrorFromJson(string $rawMessage): ?string
    {
        // Look for JSON in the message
        if (preg_match('/\{.*"errors".*\}/s', $rawMessage, $matches)) {
            try {
                $json = json_decode($matches[0], true);
                if (isset($json['errors']) && is_array($json['errors'])) {
                    foreach ($json['errors'] as $error) {
                        $code = $error['code'] ?? '';
                        if ($code) {
                            // Try to match the extracted code
                            $upperCode = strtoupper($code);
                            foreach (self::$errorPatterns as $pattern => $friendlyMessage) {
                                if (str_contains($upperCode, strtoupper($pattern))) {
                                    return $friendlyMessage;
                                }
                            }
                        }
                    }

                    // If we found errors but couldn't match, use the first error message
                    $firstError = $json['errors'][0] ?? null;
                    if ($firstError && isset($firstError['message'])) {
                        return self::sanitizeApiMessage($firstError['message']);
                    }
                }
            } catch (\Exception $e) {
                // JSON parsing failed, continue to fallback
            }
        }

        return null;
    }

    /**
     * Sanitize API error messages to be more readable.
     */
    private static function sanitizeApiMessage(string $message): string
    {
        // Remove technical prefixes
        $message = preg_replace('/^We (are|cannot|could not)\s+/i', '', $message);

        // Don't truncate - we need full error messages for debugging
        // Only ensure it ends with a period if it's a complete sentence
        if (!empty($message) && !str_ends_with($message, '.') && !str_ends_with($message, '...')) {
            $message .= '.';
        }

        return $message;
    }

    /**
     * Get a categorized error type for UI display.
     */
    public static function getErrorCategory(string $rawMessage): string
    {
        $upperMessage = strtoupper($rawMessage);

        $categories = [
            'address' => ['ADDRESS', 'POSTAL', 'POSTCODE', 'COUNTRY', 'DESTINATION', 'RECIPIENT', 'ORIGIN'],
            'service' => ['SERVICE', 'PRODUCT', 'PACKAGING', 'PACKAGINGTYPE'],
            'customs' => ['CUSTOMS', 'COMMODITY', 'PROHIBITED', 'DECLARED', 'TAXID', 'TIN'],
            'package' => ['WEIGHT', 'DIMENSION', 'PIECE'],
            'account' => ['ACCOUNT', 'SHIPPER', 'CREDENTIAL', 'AUTH'],
            'network' => ['TIMEOUT', 'CONNECTION', 'UNAVAILABLE', 'NETWORK', 'RATE.LIMIT'],
        ];

        foreach ($categories as $category => $patterns) {
            foreach ($patterns as $pattern) {
                if (str_contains($upperMessage, $pattern)) {
                    return $category;
                }
            }
        }

        return 'general';
    }
}
