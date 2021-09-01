<?php

namespace Tests\DumpRequestBody;

use GuzzleHttp\Psr7\Request;
use Orchestra\Testbench\TestCase;
use TenantCloud\GuzzleHelper\DumpRequestBody\JsonObfuscator;

/**
 * @see JsonObfuscator
 */
class JsonObfuscatorTest extends TestCase
{
	public function testSkipsNonJsonBodies(): void
	{
		$request = (new JsonObfuscator(['non']))
			->obfuscate(new Request('get', '', [], 'non json'));

		$this->assertSame('non json', (string) $request->getBody());
	}

	public function testDenotesArraysOrObjects(): void
	{
		$this->assertSame([
			'arr' => '**_array_or_object_**',
			'obj' => '**_array_or_object_**',
		], $this->obfuscate(['arr', 'obj'], [
			'arr' => ['dasdsda'],
			'obj' => [
				'dasd' => 'dasdad123',
			],
		]));
	}

	public function testObfuscatesKeys(): void
	{
		$this->assertSame([
			'some_key' => '**_array_or_object_**',
			'nested'   => [
				'key' => '*************',
			],
			'other' => 123,
		], $this->obfuscate(['some_key', 'nested.key'], [
			'some_key' => ['dasdsda'],
			'nested'   => [
				'key' => 'heheheehheheh',
			],
			'other' => 123,
		]));
	}

	/**
	 * Obfuscate given keys/data as JSON.
	 */
	private function obfuscate(array $keys, array $data): array
	{
		$request = (new JsonObfuscator($keys))
			->obfuscate(new Request('get', '', [], json_encode($data, JSON_THROW_ON_ERROR, 512)));

		return json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
	}
}
