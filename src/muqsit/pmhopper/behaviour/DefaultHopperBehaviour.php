<?php

declare(strict_types=1);

namespace muqsit\pmhopper\behaviour;

use muqsit\pmhopper\HopperConfig;
use pocketmine\inventory\Inventory;

final class DefaultHopperBehaviour implements HopperBehaviour{

	public static function getInstance() : self{
		static $instance = null;
		return $instance ?? $instance = new self();
	}

	public static function doTransferring(Inventory $from, Inventory $to) : bool{
		$transfer_per_tick = HopperConfig::getInstance()->getTransferPerTick();
		for($slot = 0, $max = $from->getSize(); $slot < $max; ++$slot){
			$item = $from->getItem($slot);
			if(!$item->isNull()){
				$residue_count = 0;
				$deducted_count = min($item->getCount(), $transfer_per_tick);
				foreach($to->addItem($item->pop($deducted_count)) as $residue){
					$residue_count += $residue->getCount();
				}
				$item->setCount($item->getCount() + $residue_count);
				$from->setItem($slot, $item);
				break;
			}
		}
		return false;
	}

	private function __construct(){
	}

	public function above(Inventory $hopper_inventory, Inventory $inventory) : void{
		self::doTransferring($inventory, $hopper_inventory);
	}

	public function side(Inventory $hopper_inventory, Inventory $inventory) : void{
		self::doTransferring($hopper_inventory, $inventory);
	}

	public function below(Inventory $hopper_inventory, Inventory $inventory) : void{
		self::doTransferring($hopper_inventory, $inventory);
	}
}