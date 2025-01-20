<?php

namespace Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;
use Psr\Http\Message\RequestInterface;
use TenantCloud\GuzzleHelper\GuzzleMiddleware;

/**
 * @see GuzzleMiddleware::fullErrorResponseBody()
 */
class GuzzleFullErrorResponseBodyMiddlewareTest extends TestCase
{
	public function testDoesNotDoAnythingWithAnExceptionWithoutResponse(): void
	{
		$this->expectException(RequestException::class);
		$this->expectExceptionMessage('Error completing request');

		$this
			->newClientWithMiddleware(static fn (RequestInterface $request) => RequestException::create($request))
			->get('');
	}

	public function testDoesNotDoAnythingWithNonRequestException(): void
	{
		$this->expectException(TransferException::class);
		$this->expectExceptionMessage('Connection failure');

		$this
			->newClientWithMiddleware(static fn () => new TransferException('Connection failure'))
			->get('');
	}

	public function testAddsFullResponseBodyForRequestExceptions(): void
	{
		$responseBody = Str::random(200);

		$this->expectException(RequestException::class);
		$this->expectExceptionMessage("Unsuccessful request: `GET ` resulted in a `200 OK` response:\n{$responseBody}");

		$this
			->newClientWithMiddleware(static fn (RequestInterface $request) => RequestException::create(
				$request,
				new Response(200, [], $responseBody)
			))
			->get('');
	}

	/**
	 * Create new Guzzle client with the middleware.
	 */
	private function newClientWithMiddleware(callable $getOriginalException): Client
	{
		$stack = HandlerStack::create(static fn (RequestInterface $request, array $options) => Create::rejectionFor($getOriginalException($request, $options)));

		$stack->unshift(GuzzleMiddleware::fullErrorResponseBody());

		return new Client([
			'handler' => $stack,
		]);
	}
}
