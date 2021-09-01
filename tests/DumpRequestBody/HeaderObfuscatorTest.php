<?php

namespace Tests\DumpRequestBody;

use GuzzleHttp\Psr7\Request;
use Orchestra\Testbench\TestCase;
use TenantCloud\GuzzleHelper\DumpRequestBody\HeaderObfuscator;

/**
 * @see HeaderObfuscator
 */
class HeaderObfuscatorTest extends TestCase
{
	public function testObfuscatesHeaders(): void
	{
		$request = (new HeaderObfuscator(['Za', 'lu']))
			->obfuscate(new Request('get', '', [
				'Za'    => 'sdad',
				'Lu'    => ['one', 'he'],
				'Other' => 'L',
			]));

		$this->assertSame('****', $request->getHeader('Za')[0]);
		$this->assertSame(['***', '**'], $request->getHeader('Lu'));
		$this->assertSame('L', $request->getHeader('Other')[0]);
	}
}
