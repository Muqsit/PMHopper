<?php

declare(strict_types=1);

namespace muqsit\pmhopper\blockscheduler;

use pocketmine\math\Vector3;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\world\World;

final class LoadBalancingBlockScheduler implements BlockScheduler{

	/** @var TaskHandler */
	private $handler;

	/** @var int */
	private $capacity;

	/** @var int */
	private $tick;

	/** @var int */
	private $position = 0;

	/** @var int[] */
	private $update_counts = [];

	public function __construct(TaskScheduler $scheduler, int $capacity){
		$this->capacity = $capacity;
		$this->handler = $scheduler->scheduleRepeatingTask(new ClosureTask(function() : void{
			unset($this->update_counts[$this->tick++]);
			if(!isset($this->update_counts[$this->tick])){
				$this->update_counts[$this->tick] = 0;
			}
		}), 1);
	}

	public function scheduleDelayedBlockUpdate(World $world, Vector3 $pos, int $min_delay) : int{
		if(!isset($this->update_counts[$this->position])){
			$this->position = $this->tick;
			assert(isset($this->update_counts[$this->position]));
		}

		if($this->update_counts[$this->position] > $this->capacity){
			$this->update_counts[++$this->position] = 0;
		}

		$delay_offset = $this->position - $this->tick;
		$delay = $min_delay + $delay_offset;
		++$this->update_counts[$this->position];
		$world->scheduleDelayedBlockUpdate($pos, $delay);
		return $delay;
	}

	public function destroy() : void{
		if(!$this->handler->isCancelled()){
			$this->handler->cancel();
		}
	}
}
