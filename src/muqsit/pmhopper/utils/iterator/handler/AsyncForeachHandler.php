<?php

declare(strict_types=1);

namespace muqsit\pmhopper\utils\iterator\handler;

use Closure;

/**
 * @phpstan-template TKey
 * @phpstan-template TValue
 */
interface AsyncForeachHandler{

	/**
	 * Called on each iterated entry. Accepts two parameters,
	 * the first being the key, and the second being the value.
	 *
	 * @param Closure $callback
	 * @return AsyncForeachHandler
	 *
	 * @phpstan-param Closure(TKey, TValue) : bool $callback
	 * @phpstan-return AsyncForeachHandler<TKey, TValue>
	 */
	public function as(Closure $callback) : self;

	/**
	 * Cancels the foreach task.
	 *
	 * NOTE: Cancelling a foreach task will NOT call it's
	 * {@see AsyncForeachHandler::then()} callbacks.
	 */
	public function cancel() : void;

	/**
	 * Called when the foreach task ends.
	 *
	 * @param Closure $callback
	 * @return AsyncForeachHandler
	 *
	 * @phpstan-param Closure() : void $callback
	 *
	 * @phpstan-param Closure() : void $callback
	 * @phpstan-return AsyncForeachHandler<TKey, TValue>
	 */
	public function then(Closure $callback) : self;
}