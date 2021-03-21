<?php

declare(strict_types=1);

namespace muqsit\pmhopper;

use InvalidArgumentException;
use muqsit\pmhopper\blockscheduler\BlockScheduler;

final class HopperConfig{

	/** @var HopperConfig */
	private static $instance;

	public static function hasInstance() : bool{
		return self::$instance !== null;
	}

	public static function getInstance() : self{
		return self::$instance;
	}

	public static function setInstance(HopperConfig $instance) : void{
		if(self::$instance !== $instance && self::hasInstance()){
			self::$instance->destroy();
		}
		self::$instance = $instance;
	}

	/** @var int */
	private $transfer_tick_rate;

	/** @var int */
	private $transfer_per_tick;

	/** @var int */
	private $item_sucking_tick_rate;

	/** @var int */
	private $item_sucking_per_tick;

	/** @var BlockScheduler */
	private $block_scheduler;

	public function __construct(int $transfer_tick_rate, int $transfer_per_tick, int $item_sucking_tick_rate, int $item_sucking_per_tick, BlockScheduler $block_scheduler){
		if($transfer_tick_rate <= 0){
			throw new InvalidArgumentException("transfer_tick_rate cannot be <= 0, got {$transfer_tick_rate}");
		}
		$this->transfer_tick_rate = $transfer_tick_rate;
		$this->transfer_per_tick = $transfer_per_tick;

		if($item_sucking_tick_rate < 0){
			throw new InvalidArgumentException("item_sucking_tick_rate cannot be < 0, got {$item_sucking_tick_rate}");
		}
		$this->item_sucking_tick_rate = $item_sucking_tick_rate;
		$this->item_sucking_per_tick = $item_sucking_per_tick;

		$this->block_scheduler = $block_scheduler;
	}

	public function getTransferTickRate() : int{
		return $this->transfer_tick_rate;
	}

	public function getTransferPerTick() : int{
		return $this->transfer_per_tick;
	}

	public function getItemSuckingTickRate() : int{
		return $this->item_sucking_tick_rate;
	}

	public function getItemSuckingPerTick() : int{
		return $this->item_sucking_per_tick;
	}

	public function getBlockScheduler() : BlockScheduler{
		return $this->block_scheduler;
	}

	private function destroy() : void{
		$this->block_scheduler->destroy();
	}
}