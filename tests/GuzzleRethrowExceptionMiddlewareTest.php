<?php

namespace Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use LogicException;
use Orchestra\Testbench\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use TenantCloud\GuzzleHelper\GuzzleMiddleware;
use Throwable;

/**
 * @see GuzzleMiddleware::rethrowException()
 */
class GuzzleRethrowExceptionMiddlewareTest extends TestCase
{
	public function testNotCalledWithoutAnException(): void
	{
		$this
			->newClientWithMiddleware(static function () {}, function (Throwable $e) {
				$this->assertFalse(true);
			})
			->get('');

		$this->assertTrue(true);
	}

	public function testRethrowsTheExceptionIfNotThrown(): void
	{
		$this->expectException(RequestException::class);

		$called = false;

		$this
			->newClientWithMiddleware(static function (RequestInterface $request) {
				throw RequestException::create($request);
			}, function (Throwable $e) use (&$called) {
				$this->assertInstanceOf(RequestException::class, $e);

				$called = true;
			})
			->get('');

		$this->assertTrue($called);
	}

	public function testRethrowsOtherExceptionIfRethrown(): void
	{
		$this->expectException(LogicException::class);
		$this->expectExceptionMessage('Test test');

		$this
			->newClientWithMiddleware(static function (RequestInterface $request) {
				throw RequestException::create($request);
			}, function (Throwable $e) {
				$this->assertInstanceOf(RequestException::class, $e);

				throw new LogicException('Test test');
			})
			->get('');
	}

	public function testRethrowsOtherMiddlewareException(): void
	{
		$this->expectException(LogicException::class);
		$this->expectExceptionMessage('Test test');

		$this
			->newClientWithMiddleware(static function (RequestInterface $request) {}, function (Throwable $e) {
				$this->assertInstanceOf(RequestException::class, $e);

				throw new LogicException('Test test');
			}, static function (HandlerStack $stack) {
				$stack->push(static function (callable $handler) {
					return static function ($request, array $options) use ($handler) {
						return $handler($request, $options)
							->then(static function (ResponseInterface $response) use ($request) {
								throw RequestException::create($request, $response);
							});
					};
				});
			})
			->get('');
	}

	/**
	 * Create new Guzzle client with the middleware.
	 */
	private function newClientWithMiddleware(callable $handleRequest, callable $handleException, callable $modifyHandler = null): Client
	{
		$stack = HandlerStack::create(static function (RequestInterface $request, array $options) use ($handleRequest) {
			try {
				return Create::promiseFor(new Response(200, [], $handleRequest($request, $options)));
			} catch (Throwable $e) {
				return Create::rejectionFor($e);
			}
		});

		if ($modifyHandler) {
			$modifyHandler($stack);
		}

		$stack->unshift(GuzzleMiddleware::rethrowException($handleException));

		return new Client([
			'handler' => $stack,
		]);
	}
}
