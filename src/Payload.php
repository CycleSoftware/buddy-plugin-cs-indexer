<?php declare(strict_types=1);

/*
  Copyright (c) 2023, Manticore Software LTD (https://manticoresearch.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License version 2 or any later
  version. You should have received a copy of the GPL license along with this
  program; if you did not, you can find it at http://www.gnu.org/
*/

namespace Manticoresearch\Buddy\Plugin\CsIndexer;

use Manticoresearch\Buddy\Core\Network\Request;
use Manticoresearch\Buddy\Core\Plugin\BasePayload;

/**
 * This is simple do nothing request that handle empty queries
 * which can be as a result of only comments in it that we strip
 * @extends BasePayload<array{}>
 */
final class Payload extends BasePayload
{

	public string $path;

	/**
	 * @param Request $request
	 * @return static
	 */
	public static function fromRequest(Request $request): static
	{
		$self = new static();
		$self->path = $request->payload;
		return $self;
	}

	/**
	 * @param Request $request
	 * @return bool
	 */
	public static function hasMatch(Request $request): bool
	{
		$payload = preg_replace('/\s+/', ' ', $request->payload ?? '') ?? '';
		$payload = str_replace('  ', ' ', $payload);
		$payload = trim(strtolower($payload));
		if (str_starts_with($payload, 'indexer status')) {
			return true;
		}
		if (str_starts_with($payload, 'indexer rotate')) {
			return true;
		}
		if ($payload === 'indexer nodeid') {
			return true;
		}
		if ($payload === 'show unattached indexes') {
			return true;
		}
		return false;
	}
}
