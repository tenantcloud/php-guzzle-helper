<?php

namespace Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Orchestra\Testbench\TestCase;
use Psr\Http\Message\RequestInterface;
use TenantCloud\GuzzleHelper\DumpRequestBody\RequestObfuscator;
use TenantCloud\GuzzleHelper\GuzzleMiddleware;

/**
 * @see GuzzleMiddleware::dumpRequestBody()
 */
class GuzzleDumpRequestBodyMiddlewareTest extends TestCase
{
	public function testDoesNotDoAnythingWithNonRequestException(): void
	{
		$this->expectException(TransferException::class);
		$this->expectExceptionMessage('Connection failure');

		$this
			->newClientWithMiddleware([], fn () => new TransferException('Connection failure'))
			->get('');
	}

	public function testAddsRequestBodyForRequestWithResponse(): void
	{
		$this->expectException(RequestException::class);
		$this->expectExceptionMessageMatches(
			<<<'REGEXP'
				/Unsuccessful request: `GET zal` resulted in a `200 OK` response, request:\v*
				GET zal HTTP\/1\.1\v*
				Host: \v*
				Content\-Length: \d+\v*
				User\-Agent: .*\v*
				Content\-Type: application\/json\v*
				\v*
				\{"test":123\}/
				REGEXP
		);

		$this
			->newClientWithMiddleware(
				[],
				fn (RequestInterface $request) => RequestException::create($request, new Response())
			)
			->get('zal', [
				'json' => [
					'test' => 123,
				],
			]);
	}

	public function testAddsRequestBodyForRequestWithoutResponse(): void
	{
		$this->expectException(RequestException::class);
		$this->expectExceptionMessageMatches(
			<<<'REGEXP'
				/Error completing request, request:\v*
				GET zal HTTP\/1\.1\v*
				Host: \v*
				Content\-Length: \d+\v*
				User\-Agent: .*\v*
				Content\-Type: application\/json\v*
				\v*
				\{"test":123\}/
				REGEXP
		);

		$this
			->newClientWithMiddleware()
			->get('zal', [
				'json' => [
					'test' => 123,
				],
			]);
	}

	public function testPipesRequestThroughObfuscatorsSequentially(): void
	{
		$this->expectException(RequestException::class);
		$this->expectExceptionMessageMatches(
			<<<'REGEXP'
				/Error completing request, request:\v*
				GET zal HTTP\/1\.1\v*
				Host: \v*
				Content\-Length: \d+\v*
				User\-Agent: .*\v*
				Content\-Type: application\/json\v*
				New-Header2: upa\v*
				\v*
				\{"test":123\}/
				REGEXP
		);

		$this
			->newClientWithMiddleware([
				new class () implements RequestObfuscator {
					public function obfuscate(RequestInterface $request): RequestInterface
					{
						return $request->withHeader('New-Header1', 'zal')
							->withHeader('New-Header2', 'upa');
					}
				},
				new class () implements RequestObfuscator {
					public function obfuscate(RequestInterface $request): RequestInterface
					{
						return $request->withoutHeader('New-Header1');
					}
				},
			])
			->get('zal', [
				'json' => [
					'test' => 123,
				],
			]);
	}

	/**
	 * Create new Guzzle client with the middleware.
	 */
	private function newClientWithMiddleware(array $obfuscators = [], ?callable $getOriginalException = null): Client
	{
		if (!$getOriginalException) {
			$getOriginalException = fn (RequestInterface $request) => RequestException::create($request);
		}

		$stack = HandlerStack::create(static fn (RequestInterface $request) => Create::rejectionFor($getOriginalException($request)));

		$stack->unshift(GuzzleMiddleware::dumpRequestBody($obfuscators));

		return new Client([
			'handler' => $stack,
		]);
	}
}
