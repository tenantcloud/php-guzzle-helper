<?php

namespace TenantCloud\GuzzleHelper\DumpRequestBody;

use Psr\Http\Message\RequestInterface;

/**
 * Obfuscate request to hide sensitive information before logging/dumping.
 */
interface RequestObfuscator
{
	/**
	 * Create an obfuscated copy of the request.
	 */
	public function obfuscate(RequestInterface $request): RequestInterface;
}
