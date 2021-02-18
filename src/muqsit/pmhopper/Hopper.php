<?php

declare(strict_types=1);

namespace muqsit\pmhopper;

use muqsit\pmhopper\behaviour\HopperBehaviourManager;
use pocketmine\block\Hopper as VanillaHopper;
use pocketmine\block\inventory\HopperInventory;
use pocketmine\block\tile\Container;
use pocketmine\block\tile\Hopper as HopperTile;
use pocketmine\block\tile\Tile;
use pocketmine\math\Facing;

class Hopper extends VanillaHopper{

	public function getInventory() : ?HopperInventory{
		$tile = $this->pos->getWorld()->getTileAt($this->pos->x, $this->pos->y, $this->pos->z);
		return $tile instanceof HopperTile ? $tile->getInventory() : null;
	}

	public function getContainerAbove() : ?Container{
		$above = $this->pos->getWorld()->getTileAt($this->pos->x, $this->pos->y + 1, $this->pos->z);
		return $above instanceof Container ? $above : null;
	}

	public function getContainerFacing(int $face) : ?Container{
		$facing_pos = $this->pos->getSide($face);
		$facing = $this->pos->getWorld()->getTileAt($facing_pos->x, $facing_pos->y, $facing_pos->z);
		return $facing instanceof Container ? $facing : null;
	}

	protected function canRescheduleTransferCooldown() : bool{
		return ($this->getContainerAbove() ?? $this->getContainerFacing($this->getFacing())) !== null;
	}

	protected function rescheduleTransferCooldown() : void{
		$this->pos->getWorld()->scheduleDelayedBlockUpdate($this->pos, HopperConfig::getInstance()->getTransferTickRate());
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
			$face = $this->getFacing();
			$facing = $this->getContainerFacing($face);
			if($facing !== null){
				assert($facing instanceof Tile);
				if($face !== Facing::DOWN){
					HopperBehaviourManager::getFromTile($facing)->side($hopper_inventory, $facing->getInventory());
				}else{
					HopperBehaviourManager::getFromTile($facing)->below($hopper_inventory, $facing->getInventory());
				}
			}

			$above = $this->getContainerAbove();
			if($above !== null){
				assert($above instanceof Tile);
				HopperBehaviourManager::getFromTile($above)->above($hopper_inventory, $above->getInventory());
			}

			if($this->canRescheduleTransferCooldown()){
				$this->rescheduleTransferCooldown();
			}
		}
	}
}