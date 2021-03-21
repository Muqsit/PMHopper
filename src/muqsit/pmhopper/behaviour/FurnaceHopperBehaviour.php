<?php

declare(strict_types=1);

namespace muqsit\pmhopper\behaviour;

use pocketmine\block\inventory\FurnaceInventory;
use pocketmine\crafting\FurnaceRecipeManager;
use pocketmine\inventory\Inventory;
use pocketmine\Server;

class FurnaceHopperBehaviour implements HopperBehaviour{

	/** @var FurnaceRecipeManager */
	private $furnace_recipe_manager;

	public function __construct(){
		$this->furnace_recipe_manager = Server::getInstance()->getCraftingManager()->getFurnaceRecipeManager();
	}

	public function above(Inventory $hopper_inventory, Inventory $inventory, int $transfer_cap) : void{
		assert($inventory instanceof FurnaceInventory);
		$item = $inventory->getResult();
		if(!$item->isNull()){
			foreach($hopper_inventory->addItem($item->pop(min($item->getCount(), $transfer_cap))) as $residue){
				$item->setCount($item->getCount() + $residue->getCount());
			}
			$inventory->setResult($item);
		}
	}

	public function side(Inventory $hopper_inventory, Inventory $inventory, int $transfer_cap) : void{
		assert($inventory instanceof FurnaceInventory);
		$fuel = $inventory->getFuel();
		if($fuel->isNull() || $fuel->getCount() < $fuel->getMaxStackSize()){
			for($slot = 0, $max = $hopper_inventory->getSize(); $slot < $max; ++$slot){
				$item = $hopper_inventory->getItem($slot);
				if($fuel->isNull() ? $item->getFuelTime() > 0 : $item->equals($fuel)){
					$transferred = min($fuel->getMaxStackSize() - $fuel->getCount(), $item->getCount(), $transfer_cap);
					$fuel = (clone $item)->setCount($fuel->getCount() + $transferred);
					$inventory->setFuel($fuel);
					$hopper_inventory->setItem($slot, $item->setCount($item->getCount() - $transferred));
					if($fuel->getCount() >= $fuel->getMaxStackSize()){
						break;
					}
				}
			}
		}
	}

	public function below(Inventory $hopper_inventory, Inventory $inventory, int $transfer_cap) : void{
		assert($inventory instanceof FurnaceInventory);
		$smelting = $inventory->getSmelting();
		if($smelting->isNull() || $smelting->getCount() < $smelting->getMaxStackSize()){
			for($slot = 0, $max = $hopper_inventory->getSize(); $slot < $max; ++$slot){
				$item = $hopper_inventory->getItem($slot);
				if($smelting->isNull() ? $this->furnace_recipe_manager->match($item) !== null : $item->equals($smelting)){
					$transferred = min($smelting->getMaxStackSize() - $smelting->getCount(), $item->getCount(), $transfer_cap);
					$smelting = (clone $item)->setCount($smelting->getCount() + $transferred);
					$inventory->setSmelting($smelting);
					$hopper_inventory->setItem($slot, $item->setCount($item->getCount() - $transferred));
					if($smelting->getCount() >= $smelting->getMaxStackSize()){
						break;
					}
				}
			}
		}
	}
}