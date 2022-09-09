<?php

namespace TenantCloud\GuzzleHelper;

use Exception;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Message;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use TenantCloud\GuzzleHelper\DumpRequestBody\RequestObfuscator;
use Tests\GuzzleDumpRequestBodyMiddlewareTest;
use Tests\GuzzleFullErrorResponseBodyMiddlewareTest;
use Tests\GuzzleRethrowExceptionMiddlewareTest;
use Tests\GuzzleTracingLogMiddlewareTest;
use Throwable;

/**
 * Guzzle middleware helpers.
 */
class GuzzleMiddleware
{
	/**
	 * Middleware for Guzzle's {@see \GuzzleHttp\HandlerStack} to catch
	 * an exception and rethrow it.
	 *
	 * @see GuzzleRethrowExceptionMiddlewareTest
	 */
	public static function rethrowException(callable $handleException): callable
	{
		return static function (callable $handler) use ($handleException) {
			return static function ($request, array $options) use ($handleException, $handler) {
				return $handler($request, $options)->then(static fn (ResponseInterface $response): ResponseInterface => $response, static function ($exception) use ($options, $handleException) {
					try {
						$response = $handleException($exception, $options);

						if ($response instanceof ResponseInterface) {
							return Create::promiseFor($response);
						}
					} catch (Throwable $newException) {
						return Create::rejectionFor($newException);
					}

					return Create::rejectionFor($exception);
				});
			};
		};
	}

	/**
	 * Puts full response body into exception messages.
	 *
	 * @see GuzzleFullErrorResponseBodyMiddlewareTest
	 */
	public static function fullErrorResponseBody(): callable
	{
		return static::rethrowException(static function (Throwable $e) {
			if (!$e instanceof RequestException || !$e->getResponse()) {
				throw $e;
			}

			// Drop short summary from the message
			$message = preg_replace('/^(.* resulted in a `.*` response):?.*$/is', '$1', $e->getMessage());

			// Add a full summary
			$message .= ":\n" . $e->getResponse()->getBody();

			throw new RequestException($message, $e->getRequest(), $e->getResponse(), $e->getPrevious() instanceof Exception ? $e->getPrevious() : null, $e->getHandlerContext());
		});
	}

	/**
	 * Dumps whole request body and headers on error.
	 *
	 * Optionally, can use obfuscators to hide sensitive information before dumping.
	 *
	 * @param RequestObfuscator|RequestObfuscator[] $obfuscators
	 *
	 * @see GuzzleDumpRequestBodyMiddlewareTest
	 */
	public static function dumpRequestBody($obfuscators = []): callable
	{
		$obfuscators = Arr::wrap($obfuscators);

		return static::rethrowException(static function (Throwable $e) use ($obfuscators) {
			if (!$e instanceof RequestException) {
				throw $e;
			}

			$obfuscatedRequest = $e->getRequest();

			foreach ($obfuscators as $obfuscator) {
				$obfuscatedRequest = $obfuscator->obfuscate($obfuscatedRequest);
			}

			// Add a full summary
			$message = $e->getMessage() . ", request:\n" . Message::toString($obfuscatedRequest);

			throw new RequestException($message, $e->getRequest(), $e->getResponse(), $e->getPrevious() instanceof Exception ? $e->getPrevious() : null, $e->getHandlerContext());
		});
	}

	/**
	 * Logs all HTTP requests with minimum information - no body, no response, no headers.
	 *
	 * @see GuzzleTracingLogMiddlewareTest
	 *
	 * @param LogLevel::* $level
	 */
	public static function tracingLog(LoggerInterface $logger, string $level = 'debug'): callable
	{
		return Middleware::log($logger, new MessageFormatter('{method} {uri} {req_header_content-length} -> {code}'), $level);
	}
}
