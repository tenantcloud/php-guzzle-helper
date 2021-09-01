<?php

namespace TenantCloud\GuzzleHelper\DumpRequestBody;

use Psr\Http\Message\RequestInterface;

/**
 * Obfuscates request's headers, case insensitive.
 */
class HeaderObfuscator implements RequestObfuscator
{
	/** @var string[] */
	private array $headers;

	/**
	 * @param string[] $headers
	 */
	public function __construct(array $headers = [])
	{
		$this->headers = $headers;
	}

	/**
	 * {@inheritdoc}
	 */
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
