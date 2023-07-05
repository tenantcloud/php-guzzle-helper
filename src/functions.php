<?php

namespace TenantCloud\GuzzleHelper;

use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;
use Tests\PsrResponseToJsonTest;

function arr_replace(array $data, string $key, $valueOrCallback): array
{
	// If array has that key, replace it. Otherwise do nothing.
	if (Arr::has($data, $key)) {
		// If callback passed, call it with previous value.
		if (is_callable($valueOrCallback)) {
			$valueOrCallback = $valueOrCallback(Arr::get($data, $key));
		}

		// Set the new value.
		Arr::set($data, $key, $valueOrCallback);
	}

	return $data;
}

/**
 * Converts PSR-7 response interface into arrayed parsed JSON.
 *
 * @return array|string|int|float
 *
 * @see PsrResponseToJsonTest
 */
function psr_response_to_json(ResponseInterface $response)
{
	return json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
}
