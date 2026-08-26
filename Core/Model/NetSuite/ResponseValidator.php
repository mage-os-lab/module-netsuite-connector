<?php
declare(strict_types=1);

namespace MageOS\NetSuiteConnector\Core\Model\NetSuite;

use MageOS\NetSuiteConnector\Core\Exception\DataIntegrityException;
use MageOS\NetSuiteConnector\Core\Exception\NetSuiteRuntimeException;

class ResponseValidator
{
    /**
     * All Response classes have the same structure to check if request is Successful
     * $response->xxxResponse->status->isSuccess
     *
     *
     * There are two Response Structures when it comes to statusDetail
     * - xxxResponse
     * $response->xxxResponse->status->statusDetail
     * - xxxResponseList
     * $response->xxxResponse->xxxResponseList->xxxResponse[0]->status->statusDetail
     *
     * So the array here uses the second array to determine which Structure to use. As this array gets extended, new
     * Structures might show up, in that case, refactor of the logic will be needed.
     *
     */
    private const SUPPORTED_RESPONSES = [
        'writeResponse' => [],
        'readResponse' => [],
        'readResponseList' => [['readResponse', 0]],
        'getServerTimeResult' => [],
        'searchResult' => []
    ];
    /**
     * @param $response
     * @throws DataIntegrityException
     * @throws NetSuiteRuntimeException
     */
    public static function validate($response): void // phpcs:ignore
    {
        if (($response === null) || !is_object($response)) {
            throw new NetSuiteRuntimeException('NetSuite responded with NULL');
        }

        $className = get_class($response);
        if (strpos($className, 'Response') === false) {
            throw new NetSuiteRuntimeException('Unsupported $response class - ' . get_class($response));
        }

        foreach (static::SUPPORTED_RESPONSES as $responseType => $responseData) {
            if (!isset($response->$responseType)) {
                // We skip the lines where the $responseType is not matched!
                continue;
            }

            if ($response->$responseType->status->isSuccess == false) {
                throw new DataIntegrityException(self::getStatusDetail($response, $responseType, $responseData));
            }
        }
    }

    // phpcs:ignore
    private static function getStatusDetail($response, $responseType, $responseData): string
    {
        if (count($responseData) == 0) {
            $statusDetail = $response->$responseType->status->statusDetail;
        } else {
            [$responseLevel, $key] = array_shift($responseData);
            $statusDetail = $response->$responseType->{$responseLevel}[$key]->status->statusDetail;
        }

        if (is_array($statusDetail)) {
            $statusDetail = reset($statusDetail);
        }

        if (is_object($statusDetail)) {
            return isset($statusDetail->message) ? (string)$statusDetail->message : '';
        }

        return is_scalar($statusDetail) ? (string)$statusDetail : '';
    }
}
