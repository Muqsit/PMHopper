<?php

declare(strict_types=1);

namespace muqsit\pmhopper\blockscheduler;

use pocketmine\math\Vector3;
use pocketmine\world\World;

final class SimpleBlockScheduler implements BlockScheduler{

	public static function getInstance() : self{
		static $instance = null;
		return $instance ??= new self();
	}

	private function __construct(){
	}

	public function scheduleDelayedBlockUpdate(World $world, Vector3 $pos, int $min_delay) : int{
		$world->scheduleDelayedBlockUpdate($pos, $min_delay);
		return $min_delay;
	}

	public function destroy() : void{
	}
}