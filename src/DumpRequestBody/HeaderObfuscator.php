<?php

namespace TenantCloud\GuzzleHelper\DumpRequestBody;

use Psr\Http\Message\RequestInterface;

/**
 * Obfuscates request's headers, case insensitive.
 */
class HeaderObfuscator implements RequestObfuscator
{
	/**
	 * @param list<string> $headers
	 */
	public function __construct(/** @var list<string> */
		private array $headers = []
	) {}

	public function obfuscate(RequestInterface $request): RequestInterface
	{
		foreach ($this->headers as $header) {
			if (!$request->hasHeader($header)) {
				continue;
			}

			$request = $request->withHeader(
				$header,
				array_map(fn ($headerValue) => str_repeat('*', mb_strlen($headerValue)), $request->getHeader($header))
			);
		}

		return $request;
	}
}
