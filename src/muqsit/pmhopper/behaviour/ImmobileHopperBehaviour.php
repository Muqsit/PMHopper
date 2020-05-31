<?php

declare(strict_types=1);

namespace muqsit\pmhopper\behaviour;

use pocketmine\inventory\Inventory;

final class ImmobileHopperBehaviour implements HopperBehaviour{

	public static function getInstance() : self{
		static $instance = null;
		return $instance ?? $instance = new self();
	}

	private function __construct(){
	}

	public function above(Inventory $hopper_inventory, Inventory $inventory) : void{
	}

	public function side(Inventory $hopper_inventory, Inventory $inventory) : void{
	}

	public function below(Inventory $hopper_inventory, Inventory $inventory) : void{
	}
}