<?php

declare(strict_types=1);

namespace muqsit\pmhopper\behaviour;

use pocketmine\inventory\Inventory;

/**
 * above() -> When container is above hopper
 * side() -> When container is on a horizontal side of the hopper
 * below() -> When container is below hopper
 */
interface HopperBehaviour{

	public function above(Inventory $hopper_inventory, Inventory $inventory) : void;

	public function side(Inventory $hopper_inventory, Inventory $inventory) : void;

	public function below(Inventory $hopper_inventory, Inventory $inventory) : void;
}