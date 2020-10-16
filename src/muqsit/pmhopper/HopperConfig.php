<?php

declare(strict_types=1);

namespace muqsit\pmhopper;

use InvalidArgumentException;

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
		self::$instance = $instance;
	}

	/** @var int */
	private $transfer_cooldown;

	/** @var int */
	private $items_sucked;

	/** @var int */
	private $items_sucking_tick_rate;

	public function __construct(int $transfer_cooldown, int $items_sucked, int $items_sucking_tick_rate){
		if($transfer_cooldown <= 0){
			throw new InvalidArgumentException("Expected transfer cooldown to be > 0, got {$transfer_cooldown}");
		}

		$this->transfer_cooldown = $transfer_cooldown;
		$this->items_sucked = $items_sucked;
		$this->items_sucking_tick_rate = $items_sucking_tick_rate;
	}

	public function getTransferCooldown() : int{
		return $this->transfer_cooldown;
	}

	public function getItemsSucked() : int{
		return $this->items_sucked;
	}

	public function getItemsSuckingTickRate() : int{
		return $this->items_sucking_tick_rate;
	}
}