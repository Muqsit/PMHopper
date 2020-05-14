<?php

declare(strict_types=1);

namespace muqsit\pmhopper\item;

use pocketmine\entity\object\ItemEntity;
use pocketmine\world\Position;

final class ItemEntityMovementNotifier{

	/** @var ItemEntity */
	private $entity;

	/** @var int */
	private $x;

	/** @var int */
	private $y;

	/** @var int */
	private $z;

	/** @var int */
	private $world_id;

	/** @var ItemEntityListener */
	private $listener;

	public function __construct(ItemEntity $entity, ItemEntityListener $listener){
		$this->entity = $entity;
		$this->listener = $listener;
		$this->check($this->entity->getPosition());
	}

	public function update() : void{
		$this->check($this->entity->getPosition());
	}

	private function check(Position $position) : void{
		$x = $position->getFloorX();
		$y = $position->getFloorY();
		$z = $position->getFloorZ();
		$world_id = $position->world->getId();
		if($this->x !== $x || $this->y !== $y || $this->z !== $z || $this->world_id !== $world_id){
			$this->x = $x;
			$this->y = $y;
			$this->z = $z;
			$this->world_id = $world_id;
			$this->listener->onItemEntityMove($this->entity, $x, $y, $z, $position->world);
		}
	}
}