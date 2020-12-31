<?php

declare(strict_types=1);

namespace muqsit\pmhopper\utils\iterator;

use muqsit\pmhopper\utils\iterator\handler\SimpleAsyncForeachHandler;
use pocketmine\scheduler\Task;
use pocketmine\Server;

final class AsyncForeachTask extends Task{

	/**
	 * @var SimpleAsyncForeachHandler
	 *
	 * @phpstan-var SimpleAsyncForeachHandler<mixed, mixed>
	 */
	private $async_foreach_handler;

	/**
	 * @param SimpleAsyncForeachHandler $async_foreach_handler
	 *
	 * @phpstan-param SimpleAsyncForeachHandler<mixed, mixed> $async_foreach_handler
	 */
	public function __construct(SimpleAsyncForeachHandler $async_foreach_handler){
		$this->async_foreach_handler = $async_foreach_handler;
	}

	public function onRun() : void{
		if(!$this->async_foreach_handler->handle()){
			$this->async_foreach_handler->doCancel();
			/** @noinspection NullPointerExceptionInspection */
			$this->getHandler()->cancel();
		}
	}
}