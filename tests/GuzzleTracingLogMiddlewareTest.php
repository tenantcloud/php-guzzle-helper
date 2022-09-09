<?php

namespace Tests;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Mockery;
use Orchestra\Testbench\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use TenantCloud\GuzzleHelper\GuzzleMiddleware;

/**
 * @see GuzzleMiddleware::tracingLog()
 */
class GuzzleTracingLogMiddlewareTest extends TestCase
{
	public function testLogsGetRequests(): void
	{
		$logger = Mockery::mock(LoggerInterface::class);
		$logger->expects()
			->log('debug', 'GET https://url/path?test=123  -> 201');

		$this
			->newClientWithMiddleware($logger)
			->get('https://url/path', [
				RequestOptions::QUERY => [
					'test' => 123,
				],
			]);
	}

	public function testLogsPostRequests(): void
	{
		$logger = Mockery::mock(LoggerInterface::class);
		$logger->expects()
			->log('debug', 'POST https://url/path 12 -> 201');

		$this
			->newClientWithMiddleware($logger)
			->post('https://url/path', [
				RequestOptions::JSON => [
					'test' => 123,
				],
			]);
	}

	/**
	 * Create new Guzzle client with the middleware.
	 */
	private function newClientWithMiddleware(LoggerInterface $logger): Client
	{
		$stack = HandlerStack::create(static fn (RequestInterface $request) => Create::promiseFor(new Response(201, [])));

		$stack->push(GuzzleMiddleware::tracingLog($logger));

		return new Client([
			'handler' => $stack,
		]);
	}
}
