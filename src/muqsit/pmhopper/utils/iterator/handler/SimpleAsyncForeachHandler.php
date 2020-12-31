<?php

declare(strict_types=1);

namespace muqsit\pmhopper\utils\iterator\handler;

use Closure;
use Iterator;

/**
 * @phpstan-template TKey
 * @phpstan-template TValue
 * @phpstan-implements AsyncForeachHandler<TKey, TValue>
 */
final class SimpleAsyncForeachHandler implements AsyncForeachHandler{

	/**
	 * @var Iterator
	 * @phpstan-var Iterator<TKey, TValue>
	 */
	private $iterable;

	/** @var int */
	private $entries_per_tick;

	/** @var Closure[] */
	private $callbacks = [];

	/** @var Closure[] */
	private $cancel_callbacks = [];

	/**
	 * @param Iterator $iterable
	 * @param int $entries_per_tick
	 *
	 * @phpstan-param Iterator<TKey, TValue> $iterable
	 */
	public function __construct(Iterator $iterable, int $entries_per_tick){
		$this->iterable = $iterable;
		$this->entries_per_tick = $entries_per_tick;
		$iterable->rewind();
	}

	public function cancel() : void{
		$this->callbacks = [static function($key, $value) : bool{ return false; }];
		$this->cancel_callbacks = [];
	}

	public function handle() : bool{
		$per_run = $this->entries_per_tick;
		while($this->iterable->valid()){
			$key = $this->iterable->key();
			$value = $this->iterable->current();
			foreach($this->callbacks as $callback){
				if(!$callback($key, $value)){
					return false;
				}
			}
			$this->iterable->next();
			if(--$per_run === 0){
				return true;
			}
		}

		return false;
	}

	public function doCancel() : void{
		foreach($this->cancel_callbacks as $callback){
			$callback();
		}
	}

	public function as(Closure $callback) : AsyncForeachHandler{
		$this->callbacks[spl_object_id($callback)] = $callback;
		return $this;
	}

	public function then(Closure $callback) : AsyncForeachHandler{
		$this->cancel_callbacks[spl_object_id($callback)] = $callback;
		return $this;
	}
}