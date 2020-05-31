<?php

declare(strict_types=1);

namespace muqsit\pmhopper\behaviour;

use muqsit\pmhopper\HopperConfig;
use pocketmine\block\inventory\FurnaceInventory;
use pocketmine\inventory\Inventory;

class FurnaceHopperBehaviour implements HopperBehaviour{

	public function above(Inventory $hopper_inventory, Inventory $inventory) : void{
		assert($inventory instanceof FurnaceInventory);
		$item = $inventory->getResult();
		if(!$item->isNull()){
			$config = HopperConfig::getInstance();
			foreach($hopper_inventory->addItem($item->pop(min($item->getCount(), $config->getItemsSucked()))) as $residue){
				$item->setCount($item->getCount() + $residue->getCount());
			}
			$inventory->setResult($item);
		}
	}

	public function side(Inventory $hopper_inventory, Inventory $inventory) : void{
		assert($inventory instanceof FurnaceInventory);
		$fuel = $inventory->getFuel();
		if($fuel->isNull() || $fuel->getCount() < $fuel->getMaxStackSize()){
			$config = HopperConfig::getInstance();
			for($slot = 0, $max = $hopper_inventory->getSize(); $slot < $max; ++$slot){
				$item = $hopper_inventory->getItem($slot);
				/** @noinspection NotOptimalIfConditionsInspection */
				if($fuel->isNull() || $item->equals($fuel)){
					$transferred = min($fuel->getMaxStackSize() - $fuel->getCount(), $item->getCount(), $config->getItemsSucked());
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

	public function below(Inventory $hopper_inventory, Inventory $inventory) : void{
		assert($inventory instanceof FurnaceInventory);
		$smelting = $inventory->getSmelting();
		if($smelting->isNull() || $smelting->getCount() < $smelting->getMaxStackSize()){
			$config = HopperConfig::getInstance();
			for($slot = 0, $max = $hopper_inventory->getSize(); $slot < $max; ++$slot){
				$item = $hopper_inventory->getItem($slot);
				/** @noinspection NotOptimalIfConditionsInspection */
				if($smelting->isNull() || $item->equals($smelting)){
					$transferred = min($smelting->getMaxStackSize() - $smelting->getCount(), $item->getCount(), $config->getItemsSucked());
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