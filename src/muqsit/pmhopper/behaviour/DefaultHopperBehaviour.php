<?php

declare(strict_types=1);

namespace muqsit\pmhopper\behaviour;

use pocketmine\inventory\Inventory;

final class DefaultHopperBehaviour implements HopperBehaviour{

	public static function getInstance() : self{
		static $instance = null;
		return $instance ??= new self();
	}

	public static function doTransferring(Inventory $from, Inventory $to, int $transfer_cap) : bool{
		for($slot = 0, $max = $from->getSize(); $slot < $max; ++$slot){
			if(!$from->isSlotEmpty($slot)){
				$item = $from->getItem($slot);
				$residue_count = 0;
				$deducted_count = min($item->getCount(), $transfer_cap);
				foreach($to->addItem($item->pop($deducted_count)) as $residue){
					$residue_count += $residue->getCount();
				}
				if($residue_count !== $deducted_count){
					$item->setCount($item->getCount() + $residue_count);
					$from->setItem($slot, $item);
					return true;
				}
			}
		}
		return false;
	}

	private function __construct(){
	}

	public function above(Inventory $hopper_inventory, Inventory $inventory, int $transfer_cap) : void{
		self::doTransferring($inventory, $hopper_inventory, $transfer_cap);
	}

	public function side(Inventory $hopper_inventory, Inventory $inventory, int $transfer_cap) : void{
		self::doTransferring($hopper_inventory, $inventory, $transfer_cap);
	}

	public function below(Inventory $hopper_inventory, Inventory $inventory, int $transfer_cap) : void{
		self::doTransferring($hopper_inventory, $inventory, $transfer_cap);
	}
}