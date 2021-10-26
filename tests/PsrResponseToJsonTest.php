<?php

namespace Tests;

use GuzzleHttp\Psr7\Response;
use Orchestra\Testbench\TestCase;
use function TenantCloud\GuzzleHelper\psr_response_to_json;

/**
 * @see psr_response_to_json()
 */
class PsrResponseToJsonTest extends TestCase
{
	public function testConvertsResponseIntoJson(): void
	{
		self::assertSame(
			['test'],
			psr_response_to_json(
				new Response(200, [], '["test"]'),
			)
		);
	}
}
