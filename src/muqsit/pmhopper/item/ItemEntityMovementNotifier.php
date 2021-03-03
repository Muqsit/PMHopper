<?php

declare(strict_types=1);

namespace muqsit\pmhopper\item;

use pocketmine\entity\object\ItemEntity;
use pocketmine\world\Position;

final class ItemEntityMovementNotifier{

	/** @var ItemEntity */
	private $entity;

	/** @var ItemEntityListener */
	private $listener;

	public function __construct(ItemEntity $entity, ItemEntityListener $listener){
		$this->entity = $entity;
		$this->listener = $listener;
		$this->check($this->entity->getPosition());
	}

	public function update() : void{
		if(!$this->entity->isClosed() && !$this->entity->isFlaggedForDespawn()){
			$this->check($this->entity->getPosition());
		}
	}

	private function check(Position $position) : void{
		$this->listener->onItemEntityMove(
			$this->entity,
			$position->getFloorX(),
			$position->getFloorY(),
			$position->getFloorZ(),
			$position->getWorld()
		);
	}
}