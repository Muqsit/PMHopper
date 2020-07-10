<?php

declare(strict_types=1);

namespace muqsit\pmhopper;

use muqsit\pmhopper\behaviour\HopperBehaviourManager;
use pocketmine\block\Hopper as VanillaHopper;
use pocketmine\block\inventory\HopperInventory;
use pocketmine\block\tile\Container;
use pocketmine\block\tile\Hopper as HopperTile;
use pocketmine\block\tile\Tile;
use ReflectionProperty;

class Hopper extends VanillaHopper{

	public function getInventory() : ?HopperInventory{
		$tile = $this->pos->getWorld()->getTileAt($this->pos->x, $this->pos->y, $this->pos->z);
		return $tile instanceof HopperTile ? $tile->getInventory() : null;
	}

	public function getContainerAbove() : ?Container{
		$above = $this->pos->getWorld()->getTileAt($this->pos->x, $this->pos->y + 1, $this->pos->z);
		return $above instanceof Container ? $above : null;
	}

	public function getContainerBelow() : ?Container{
		$below = $this->pos->getWorld()->getTileAt($this->pos->x, $this->pos->y - 1, $this->pos->z);
		return $below instanceof Container ? $below : null;
	}

	public function getContainerFacing() : ?Container{
		static $_facing = null;
		if($_facing === null){
			$_facing = new ReflectionProperty(VanillaHopper::class, "facing");
			$_facing->setAccessible(true);
		}

		$facing_pos = $this->pos->getSide($_facing->getValue($this));
		$facing = $this->pos->getWorld()->getTileAt($facing_pos->x, $facing_pos->y, $facing_pos->z);
		return $facing instanceof Container ? $facing : null;
	}

	protected function canRescheduleTransferCooldown() : bool{
		return ($this->getContainerAbove() ?? $this->getContainerBelow() ?? $this->getContainerFacing()) !== null;
	}

	protected function rescheduleTransferCooldown() : void{
		$this->pos->getWorld()->scheduleDelayedBlockUpdate($this->pos, HopperConfig::getInstance()->getTransferCooldown());
	}

	protected function updateHopperTickers() : void{
		if($this->canRescheduleTransferCooldown()){
			$this->rescheduleTransferCooldown();
		}
	}

	public function readStateFromWorld() : void{
		parent::readStateFromWorld();
		$this->updateHopperTickers();
	}

	public function onNearbyBlockChange() : void{
		parent::onNearbyBlockChange();
		$this->updateHopperTickers();
	}

	public function onScheduledUpdate() : void{
		$hopper_inventory = $this->getInventory();
		if($hopper_inventory !== null){
			$facing = $this->getContainerFacing();
			if($facing !== null){
				assert($facing instanceof Tile);
				HopperBehaviourManager::getFromTile($facing)->side($hopper_inventory, $facing->getInventory());
			}

			$above = $this->getContainerAbove();
			if($above !== null){
				assert($above instanceof Tile);
				HopperBehaviourManager::getFromTile($above)->above($hopper_inventory, $above->getInventory());
			}

			$below = $this->getContainerBelow();
			if($below !== null){
				assert($below instanceof Tile);
				HopperBehaviourManager::getFromTile($below)->below($hopper_inventory, $below->getInventory());
			}

			if($this->canRescheduleTransferCooldown()){
				$this->rescheduleTransferCooldown();
			}
		}
	}
}