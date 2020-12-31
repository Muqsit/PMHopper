<?php

declare(strict_types=1);

namespace muqsit\pmhopper\utils\iterator;

use muqsit\pmhopper\Loader;
use muqsit\pmhopper\utils\iterator\handler\AsyncForeachHandler;
use muqsit\pmhopper\utils\iterator\handler\SimpleAsyncForeachHandler;
use Iterator;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\Server;

final class AsyncIterator{

	/** @var TaskScheduler */
	private static $scheduler;

	public static function init(Loader $plugin) : void{
		self::$scheduler = $plugin->getScheduler();
	}

	/**
	 * @param Iterator $iterable
	 * @param int $entries_per_tick
	 * @param int $sleep_time
	 * @return AsyncForeachHandler
	 *
	 * @phpstan-template TKey
	 * @phpstan-template TValue
	 * @phpstan-param Iterator<TKey, TValue> $iterable
	 * @phpstan-return AsyncForeachHandler<TKey, TValue>
	 */
	public static function forEach(Iterator $iterable, int $entries_per_tick = 10, int $sleep_time = 1) : AsyncForeachHandler{
		$handler = new SimpleAsyncForeachHandler($iterable, $entries_per_tick);
		self::$scheduler->scheduleDelayedRepeatingTask(new AsyncForeachTask($handler), 1, $sleep_time);
		return $handler;
	}
}