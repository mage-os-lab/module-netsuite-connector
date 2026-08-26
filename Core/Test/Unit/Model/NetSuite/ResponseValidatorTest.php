<?php declare(strict_types=1);
/**
 * RocketWeb
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category  RocketWeb
 * @package   MageOS_NetSuiteConnector
 * @copyright Copyright (c) 2026 RocketWeb (http://rocketweb.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author    Rocket Web Inc.
 */

namespace MageOS\NetSuiteConnector\Core\Test\Unit\Model\NetSuite;

use MageOS\NetSuiteConnector\Core\Exception\DataIntegrityException;
use MageOS\NetSuiteConnector\Core\Exception\NetSuiteRuntimeException;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\ResponseValidator;
use NetSuite\Classes\AddResponse;
use NetSuite\Classes\GetListResponse;
use NetSuite\Classes\GetResponse;
use NetSuite\Classes\GetServerTimeResponse;
use NetSuite\Classes\GetServerTimeResult;
use NetSuite\Classes\ReadResponse;
use NetSuite\Classes\ReadResponseList;
use NetSuite\Classes\SearchResponse;
use NetSuite\Classes\SearchResult;
use NetSuite\Classes\Status;
use NetSuite\Classes\StatusDetail;
use NetSuite\Classes\WriteResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The validator guards every SOAP call in the connector. Callers treat the two
 * exception types differently, a NetSuiteRuntimeException means the transport or the
 * response shape is wrong, a DataIntegrityException means NetSuite answered but
 * rejected the record, so each branch has to raise the exact class.
 */
class ResponseValidatorTest extends TestCase
{
    /**
     * A null response is reported as a transport problem
     */
    public function testItRejectsANullResponse(): void
    {
        $this->expectException(NetSuiteRuntimeException::class);
        $this->expectExceptionMessage('NetSuite responded with NULL');
        ResponseValidator::validate(null);
    }

    /**
     * Anything that is not an object is reported as a transport problem
     */
    #[DataProvider('nonObjectResponseProvider')]
    public function testItRejectsANonObjectResponse(mixed $response): void
    {
        $this->expectException(NetSuiteRuntimeException::class);
        $this->expectExceptionMessage('NetSuite responded with NULL');
        ResponseValidator::validate($response);
    }

    /**
     * Values a broken SOAP transport can hand back instead of a response object
     *
     * @return array<string, array{0: mixed}>
     */
    public static function nonObjectResponseProvider(): array
    {
        return [
            'raw xml string' => ['<soap:Envelope/>'],
            'empty string' => [''],
            'integer' => [500],
            'float' => [1.5],
            'empty array' => [[]],
            'populated array' => [['writeResponse' => 'anything']],
            'boolean false' => [false],
            'boolean true' => [true],
        ];
    }

    /**
     * An object whose class name does not contain Response is rejected as unsupported,
     * which is the case when the SOAP classmap fails and a stdClass comes back
     */
    #[DataProvider('unsupportedResponseClassProvider')]
    public function testItRejectsAnObjectThatIsNotAResponseClass(object $response, string $expectedClass): void
    {
        $this->expectException(NetSuiteRuntimeException::class);
        $this->expectExceptionMessage('Unsupported $response class - ' . $expectedClass);
        ResponseValidator::validate($response);
    }

    /**
     * Objects whose class name carries no Response marker
     *
     * @return array<string, array{0: object, 1: string}>
     */
    public static function unsupportedResponseClassProvider(): array
    {
        return [
            'unmapped soap payload' => [new \stdClass(), 'stdClass'],
            'inner netsuite type' => [new Status(), Status::class],
            'status detail' => [new StatusDetail(), StatusDetail::class],
        ];
    }

    /**
     * A successful write response passes without raising anything
     */
    public function testItAcceptsASuccessfulWriteResponse(): void
    {
        $this->expectNotToPerformAssertions();
        ResponseValidator::validate($this->buildAddResponse(true, []));
    }

    /**
     * A failed write response raises a data integrity error carrying the NetSuite detail
     */
    public function testItThrowsDataIntegrityForAFailedWriteResponse(): void
    {
        $this->expectException(DataIntegrityException::class);
        $this->expectExceptionMessage('INVALID_KEY_OR_REF');
        ResponseValidator::validate(
            $this->buildAddResponse(false, [$this->buildStatusDetail('INVALID_KEY_OR_REF')])
        );
    }

    /**
     * A failed read response raises a data integrity error carrying the NetSuite detail
     */
    public function testItThrowsDataIntegrityForAFailedReadResponse(): void
    {
        $response = new GetResponse();
        $response->readResponse = new ReadResponse();
        $response->readResponse->status = $this->buildStatus(
            false,
            [$this->buildStatusDetail('That record does not exist.')]
        );

        $this->expectException(DataIntegrityException::class);
        $this->expectExceptionMessage('That record does not exist.');
        ResponseValidator::validate($response);
    }

    /**
     * A failed search result raises a data integrity error carrying the NetSuite detail
     */
    public function testItThrowsDataIntegrityForAFailedSearchResult(): void
    {
        $response = new SearchResponse();
        $response->searchResult = new SearchResult();
        $response->searchResult->status = $this->buildStatus(
            false,
            [$this->buildStatusDetail('Search error occurred: Invalid search type.')]
        );

        $this->expectException(DataIntegrityException::class);
        $this->expectExceptionMessage('Search error occurred: Invalid search type.');
        ResponseValidator::validate($response);
    }

    /**
     * A failed server time result raises a data integrity error carrying the NetSuite detail
     */
    public function testItThrowsDataIntegrityForAFailedServerTimeResult(): void
    {
        $response = new GetServerTimeResponse();
        $response->getServerTimeResult = new GetServerTimeResult();
        $response->getServerTimeResult->status = $this->buildStatus(
            false,
            [$this->buildStatusDetail('Session timed out.')]
        );

        $this->expectException(DataIntegrityException::class);
        $this->expectExceptionMessage('Session timed out.');
        ResponseValidator::validate($response);
    }

    /**
     * A failed list response is checked at the list level but reports the detail of the
     * first child response, so the list level detail never reaches the caller
     */
    public function testItReportsTheFirstChildDetailForAFailedReadResponseList(): void
    {
        $child = new ReadResponse();
        $child->status = $this->buildStatus(false, [$this->buildStatusDetail('First child rejected')]);

        $secondChild = new ReadResponse();
        $secondChild->status = $this->buildStatus(false, [$this->buildStatusDetail('Second child rejected')]);

        $response = new GetListResponse();
        $response->readResponseList = new ReadResponseList();
        $response->readResponseList->status = $this->buildStatus(
            false,
            [$this->buildStatusDetail('List level detail')]
        );
        $response->readResponseList->readResponse = [$child, $secondChild];

        $this->expectException(DataIntegrityException::class);
        $this->expectExceptionMessage('First child rejected');
        ResponseValidator::validate($response);
    }

    /**
     * When NetSuite returns several status details only the first one is reported
     */
    public function testItReportsOnlyTheFirstStatusDetail(): void
    {
        $this->expectException(DataIntegrityException::class);
        $this->expectExceptionMessage('USER_ERROR');
        ResponseValidator::validate(
            $this->buildAddResponse(
                false,
                [
                    $this->buildStatusDetail('USER_ERROR'),
                    $this->buildStatusDetail('SECOND_ERROR'),
                ]
            )
        );
    }

    /**
     * A status detail delivered as a plain string is reported verbatim
     */
    public function testItReportsAScalarStatusDetailVerbatim(): void
    {
        $this->expectException(DataIntegrityException::class);
        $this->expectExceptionMessage('Invalid login attempt.');
        ResponseValidator::validate($this->buildAddResponse(false, 'Invalid login attempt.'));
    }

    /**
     * A status detail object without a message produces an exception with an empty message,
     * so the failure reaches the caller with no explanation attached
     */
    public function testItReportsAnEmptyMessageWhenTheDetailHasNone(): void
    {
        $detail = new StatusDetail();
        $detail->code = 'UNEXPECTED_ERROR';

        $caught = null;
        try {
            ResponseValidator::validate($this->buildAddResponse(false, $detail));
        } catch (DataIntegrityException $exception) {
            $caught = $exception;
        }

        $this->assertInstanceOf(DataIntegrityException::class, $caught);
        $this->assertSame('', $caught->getMessage());
    }

    /**
     * A response object that carries none of the supported response types passes silently
     */
    public function testItPassesWhenNoSupportedResponseTypeIsPresent(): void
    {
        $this->expectNotToPerformAssertions();
        ResponseValidator::validate(new AddResponse());
    }

    /**
     * Every loosely falsy success flag is treated as a failure
     */
    #[DataProvider('falsySuccessFlagProvider')]
    public function testItTreatsFalsySuccessFlagsAsFailure(mixed $isSuccess): void
    {
        $this->expectException(DataIntegrityException::class);
        $this->expectExceptionMessage('UNEXPECTED_ERROR');
        ResponseValidator::validate(
            $this->buildAddResponse($isSuccess, [$this->buildStatusDetail('UNEXPECTED_ERROR')])
        );
    }

    /**
     * Values that the loose comparison in the validator reads as a failure
     *
     * @return array<string, array{0: mixed}>
     */
    public static function falsySuccessFlagProvider(): array
    {
        return [
            'boolean false' => [false],
            'integer zero' => [0],
            'string zero' => ['0'],
            'empty string' => [''],
            'null' => [null],
        ];
    }

    /**
     * The success flag is compared loosely, so the string false is read as a success and
     * the failed record is accepted without any exception
     */
    public function testItAcceptsTheStringFalseAsASuccessFlag(): void
    {
        $this->expectNotToPerformAssertions();
        ResponseValidator::validate(
            $this->buildAddResponse('false', [$this->buildStatusDetail('NEVER REPORTED')])
        );
    }

    /**
     * A failed list response with no child responses reaches into readResponse[0] on a
     * null value. Under an error handler that turns PHP warnings into exceptions, which
     * is what the unit bootstrap and Magento developer mode both install, the failure
     * escapes as something other than a DataIntegrityException, so the callers that
     * catch DataIntegrityException do not catch it
     */
    public function testAFailedListResponseWithNoChildrenDoesNotRaiseADataIntegrityError(): void
    {
        $response = new GetListResponse();
        $response->readResponseList = new ReadResponseList();
        $response->readResponseList->status = $this->buildStatus(
            false,
            [$this->buildStatusDetail('List level detail')]
        );

        $caught = null;
        try {
            ResponseValidator::validate($response);
        } catch (\Throwable $throwable) {
            $caught = $throwable;
        }

        $this->assertInstanceOf(\Throwable::class, $caught);
        $this->assertNotInstanceOf(DataIntegrityException::class, $caught);
        $this->assertStringContainsString('Trying to access array offset on null', $caught->getMessage());
    }

    /**
     * Build an add response carrying the given success flag and status detail
     */
    private function buildAddResponse(mixed $isSuccess, mixed $statusDetail): AddResponse
    {
        $response = new AddResponse();
        $response->writeResponse = new WriteResponse();
        $response->writeResponse->status = $this->buildStatus($isSuccess, $statusDetail);

        return $response;
    }

    /**
     * Build a NetSuite status node
     */
    private function buildStatus(mixed $isSuccess, mixed $statusDetail): Status
    {
        $status = new Status();
        $status->isSuccess = $isSuccess;
        $status->statusDetail = $statusDetail;

        return $status;
    }

    /**
     * Build a NetSuite status detail node carrying the given message
     */
    private function buildStatusDetail(string $message): StatusDetail
    {
        $detail = new StatusDetail();
        $detail->message = $message;

        return $detail;
    }
}
