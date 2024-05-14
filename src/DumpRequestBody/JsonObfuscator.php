<?php

namespace TenantCloud\GuzzleHelper\DumpRequestBody;

use GuzzleHttp\Psr7\Utils;
use JsonException;
use Psr\Http\Message\RequestInterface;
use Tests\DumpRequestBody\JsonObfuscatorTest;

use function TenantCloud\GuzzleHelper\arr_replace;

/**
 * Obfuscates JSON body's fields.
 *
 * @see JsonObfuscatorTest
 */
class JsonObfuscator implements RequestObfuscator
{
	/**
	 * @param list<string> $fields
	 */
	public function __construct(/** @var list<string> */
		private array $fields = []
	) {}

	public function obfuscate(RequestInterface $request): RequestInterface
	{
		$body = (string) $request->getBody();

		try {
			$decodedBody = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
		} catch (JsonException $e) {
			return $request;
		}

		foreach ($this->fields as $field) {
			$decodedBody = arr_replace($decodedBody, $field, function ($value) {
				if (is_array($value)) {
					return '**_array_or_object_**';
				}

				return str_repeat('*', mb_strlen($value));
			});
		}

		return $request->withBody(Utils::streamFor(json_encode($decodedBody, JSON_THROW_ON_ERROR, 512)));
	}
}
