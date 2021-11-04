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

	/** @var int */
	private $_transfer_cap = 0;

	public function getInventory() : ?HopperInventory{
		$tile = $this->position->getWorld()->getTileAt($this->position->x, $this->position->y, $this->position->z);
		return $tile instanceof HopperTile ? $tile->getInventory() : null;
	}

	public function getContainerAbove() : ?Container{
		$above = $this->position->getWorld()->getTileAt($this->position->x, $this->position->y + 1, $this->position->z);
		return $above instanceof Container ? $above : null;
	}

	public function getContainerFacing(int $face) : ?Container{
		$facing_pos = $this->position->getSide($face);
		$facing = $this->position->getWorld()->getTileAt($facing_pos->x, $facing_pos->y, $facing_pos->z);
		return $facing instanceof Container ? $facing : null;
	}

	protected function canRescheduleTransferCooldown() : bool{
		return ($this->getContainerAbove() ?? $this->getContainerFacing($this->getFacing())) !== null;
	}

	protected function rescheduleTransferCooldown() : void{
		$config = HopperConfig::getInstance();
		$scheduler = $config->getBlockScheduler();

		$requested_delay = $config->getTransferTickRate();
		$actual_delay = $scheduler->scheduleDelayedBlockUpdate($this->position->getWorld(), $this->position, $config->getTransferTickRate());

		assert($actual_delay >= $requested_delay);
		$this->_transfer_cap = $config->getTransferPerTick() * (1 + ($actual_delay - $requested_delay));
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

			$this->_transfer_cap ??= HopperConfig::getInstance()->getTransferPerTick();

			if($facing !== null){
				assert($facing instanceof Tile);
				if($face !== Facing::DOWN){
					HopperBehaviourManager::getFromTile($facing)->side($hopper_inventory, $facing->getInventory(), $this->_transfer_cap);
				}else{
					HopperBehaviourManager::getFromTile($facing)->below($hopper_inventory, $facing->getInventory(), $this->_transfer_cap);
				}
			}

			$above = $this->getContainerAbove();
			if($above !== null){
				assert($above instanceof Tile);
				HopperBehaviourManager::getFromTile($above)->above($hopper_inventory, $above->getInventory(), $this->_transfer_cap);
			}

			if($this->canRescheduleTransferCooldown()){
				$this->rescheduleTransferCooldown();
			}
		}
	}
}