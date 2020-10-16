<?php

declare(strict_types=1);

namespace muqsit\pmhopper\utils\iterator;

use Closure;
use Generator;
use pocketmine\scheduler\Task;
use pocketmine\timings\TimingsHandler;
use pocketmine\utils\Utils;

final class AsyncIteratorTask extends Task{

	/** @var Generator<mixed> */
	private $generator;

	/** @var int */
	private $entries_per_tick;

	/** @var TimingsHandler */
	private $timings;

	public function __construct(Closure $generator, int $entries_per_tick){
		$this->generator = (static function() use($generator) : Generator{
			yield true;
			yield from $generator();
		})();
		$this->entries_per_tick = $entries_per_tick;
		$this->timings = new TimingsHandler("AsyncIterator: " . Utils::getNiceClosureName($generator));
	}

	public function onRun() : void{
		$this->timings->startTiming();
		for($i = 0; $i < $this->entries_per_tick; ++$i){
			if(!$this->generator->send(true) || !$this->generator->valid()){
				/** @noinspection NullPointerExceptionInspection */
				$this->getHandler()->cancel();
				break;
			}
		}
		$this->timings->stopTiming();
	}
}