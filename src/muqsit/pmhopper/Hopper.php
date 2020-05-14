<?php

declare(strict_types=1);

namespace muqsit\pmhopper;

use pocketmine\block\Hopper as VanillaHopper;
use pocketmine\block\inventory\HopperInventory;
use pocketmine\block\tile\Container;
use pocketmine\block\tile\Hopper as HopperTile;
use pocketmine\inventory\Inventory;
use ReflectionProperty;

class Hopper extends VanillaHopper{

	private static function doTransferring(Inventory $from, Inventory $to) : bool{
		for($slot = 0, $max = $from->getSize(); $slot < $max; ++$slot){
			$item = $from->getItem($slot);
			if(!$item->isNull()){
				foreach($to->addItem($item->pop(min($item->getCount(), HopperConfig::getInstance()->getItemsSucked()))) as $residue){
					$item->setCount($item->getCount() + $residue->getCount());
				}
				$from->setItem($slot, $item);
				break;
			}
		}
		return false;
	}

	public function getInventory() : ?HopperInventory{
		$tile = $this->pos->getWorldNonNull()->getTileAt($this->pos->x, $this->pos->y, $this->pos->z);
		return $tile instanceof HopperTile ? $tile->getInventory() : null;
	}

	public function getInventoryAbove() : ?Inventory{
		$above = $this->pos->getWorldNonNull()->getTileAt($this->pos->x, $this->pos->y + 1, $this->pos->z);
		return $above instanceof Container ? $above->getInventory() : null;
	}

	public function getInventoryFacing() : ?Inventory{
		static $_facing = null;
		if($_facing === null){
			$_facing = new ReflectionProperty(VanillaHopper::class, "facing");
			$_facing->setAccessible(true);
		}

		$facing_pos = $this->pos->getSide($_facing->getValue($this));
		$facing = $this->pos->getWorldNonNull()->getTileAt($facing_pos->x, $facing_pos->y, $facing_pos->z);
		return $facing instanceof Container ? $facing->getInventory() : null;
	}

	protected function canRescheduleTransferCooldown() : bool{
		return ($this->getInventoryAbove() ?? $this->getInventoryFacing()) !== null;
	}

	protected function rescheduleTransferCooldown() : void{
		$this->pos->getWorldNonNull()->scheduleDelayedBlockUpdate($this->pos, HopperConfig::getInstance()->getTransferCooldown());
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
			$facing_inventory = $this->getInventoryFacing();
			if($facing_inventory !== null){
				self::doTransferring($hopper_inventory, $facing_inventory);
			}

			$above_inventory = $this->getInventoryAbove();
			if($above_inventory !== null){
				self::doTransferring($above_inventory, $hopper_inventory);
			}

			if($this->canRescheduleTransferCooldown()){
				$this->rescheduleTransferCooldown();
			}
		}
	}
}