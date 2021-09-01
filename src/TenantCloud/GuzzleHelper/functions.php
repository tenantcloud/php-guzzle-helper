<?php

namespace TenantCloud\GuzzleHelper;

use Illuminate\Support\Arr;

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
