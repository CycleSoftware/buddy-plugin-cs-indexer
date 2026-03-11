<?php declare(strict_types=1);

/*
  Copyright (c) 2023, Manticore Software LTD (https://manticoresearch.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License version 2 or any later
  version. You should have received a copy of the GPL license along with this
  program; if you did not, you can find it at http://www.gnu.org/
*/

namespace Manticoresearch\Buddy\Plugin\CsIndexer;

use Manticoresearch\Buddy\Core\Plugin\BaseHandler;
use Manticoresearch\Buddy\Core\Task\Column;
use Manticoresearch\Buddy\Core\Task\Task;
use Manticoresearch\Buddy\Core\Task\TaskResult;
use RuntimeException;

final class Handler extends BaseHandler
{
	/**
	 * Initialize the executor
	 *
	 * @param Payload $payload
	 * @return void
	 */
	public function __construct(public Payload $payload) {}

	/**
	 * Process the request
	 *
	 * @return Task
	 * @throws RuntimeException
	 */
	public function run(): Task
	{
		$taskFn = function (): TaskResult {
			if (PHP_OS_FAMILY === 'Windows') {
				return TaskResult::withError('this query is not supported on Windows');
			}
			// "run indexer rotate index_name"
			// "run indexer rotate"
			if (str_starts_with($this->payload->path, 'indexer rotate')) {
				$parts = explode(' ', $this->payload->path);
				$index_name = $parts[2] ?? null;
				if (count($parts) > 3) {
					return TaskResult::withError($this->payload->path . ' is not a valid index command');
				}
				if (count($parts) > 2 && $index_name !== null && $index_name !== preg_replace("/[^a-zA-Z0-9" . preg_quote('_-') . "]+/", "", $index_name)) {
					return TaskResult::withError('"' . $index_name . '" is not a valid index name');
				}
				if (isset($index_name) && str_starts_with($index_name, '-')) {
					// do not all other options to indexer
					return TaskResult::withError($index_name . ' is not a valid index name');
				}
				$index_name = $index_name ?? '--all';
				$reference = uniqid();
				$file = tempnam(sys_get_temp_dir(), "indexer_" . $reference . '_');
				exec('bash -c "indexer --rotate "' . $index_name . '" > ' . $file . ' 2>&1" > /dev/null 2>&1 & echo $!', $output, $return);
				if ((int)$return !== 0) {
					return TaskResult::withError('error starting the indexer: ' . $return);
				}
				return TaskResult::withRow(
					[
						'pid' => trim($output[0]),
						'reference' => $reference,
						'payload' => $this->payload->path,
					]
				)->column(
					'pid',
					Column::String,
				)->column(
					'reference',
					Column::String,
				)->column(
					'payload',
					Column::String,
				);
			}

			if ($this->payload->path === 'show indexer status' || $this->payload->path === 'indexer status') {
				// "show indexer status"
				exec('ps -eo pid,cmd', $output, $result);
				array_shift($output); // remove header
				$rows = [];
				foreach ($output as $line) {
					if (!str_contains($line, 'indexer --rotate')) {
						continue;
					}
					if (!str_contains($line, ' > ')) {
						continue;
					}
					$parts = preg_split('/\s+/', trim($line), 2);
					if ($parts === false) {
						continue;
					}
					[$before_output_file, $file] = explode(' > ', $parts[1]);
					[, $index_name] = explode('--rotate', $before_output_file);
					$index_name = trim($index_name, ' "');
					$file = trim($file);
					[$file] = explode(' ', $file);
					$contents = file_get_contents($file);
					if (!$contents) {
						@file_put_contents(
							sys_get_temp_dir() . "/indexer_buddy_error.log",
							date('Y-m-d H:i:s') . ' - ' . json_encode([$file, $line]),
							FILE_APPEND
						);
					}
					$rows[] = [
						'pid' => $parts[0],
						'index' => $index_name === '--all' ? null : $index_name,
						'command' => implode(' ', array_slice($parts, 10)),
						'output' => !$contents ? null : $contents,
						'payload' => $this->payload->path,
					];
				}
				return TaskResult::withData(
					$rows
				)->column(
					'pid',
					Column::String,
				)->column(
					'index',
					Column::String,
				)->column(
					'command',
					Column::String,
				)->column(
					'output',
					Column::String,
				)->column(
					'payload',
					Column::String,
				);
			}
			return TaskResult::withError('unknown request: ' . $this->payload->path);
		};
		return Task::create(
			$taskFn
		)->run();
	}

	/**
	 * @return array<string>
	 */
	public function getProps(): array
	{
		return [];
	}
}
