<?php

declare(strict_types=1);

namespace muqsit\pmhopper\item;

use Generator;
use muqsit\pmhopper\HopperConfig;
use muqsit\pmhopper\Loader;
use muqsit\pmhopper\utils\iterator\AsyncIterator;
use pocketmine\block\tile\Hopper;
use pocketmine\entity\object\ItemEntity;
use pocketmine\event\entity\EntityDespawnEvent;
use pocketmine\event\entity\ItemSpawnEvent;
use pocketmine\event\Listener;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\world\World;

final class ItemEntityListener implements Listener{

	/** @var TaskScheduler */
	private $scheduler;

	/** @var TaskHandler|null */
	private $ticker;

	/** @var ItemEntityMovementNotifier[] */
	private $entities = [];

	/** @var bool */
	private $scanning = false;

	public function __construct(Loader $plugin){
		$this->scheduler = $plugin->getScheduler();
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
		foreach($plugin->getServer()->getWorldManager()->getWorlds() as $world){
			foreach($world->getEntities() as $entity){
				if($entity instanceof ItemEntity && !$entity->isClosed()){
					$this->onItemEntitySpawn($entity);
				}
			}
		}
	}

	public function onItemEntityMove(ItemEntity $entity, int $x, int $y, int $z, World $world) : void{
		if(!$entity->isFlaggedForDespawn()){
			$tile = $world->getTileAt($x, $y - 1, $z);
			if($tile instanceof Hopper){
				$item = $entity->getItem();
				$residue_count = 0;
				foreach($tile->getInventory()->addItem($item) as $residue){
					assert($residue_count === 0); // addItem() can't return > 1
					$residue_count = $residue->getCount();
				}
				if($residue_count === 0){
					$entity->flagForDespawn();
				}else{
					$item->setCount($residue_count);
				}
			}
		}
	}

	/**
	 * @param ItemSpawnEvent $event
	 * @priority MONITOR
	 */
	public function onItemSpawn(ItemSpawnEvent $event) : void{
		$this->onItemEntitySpawn($event->getEntity());
	}

	/**
	 * @param EntityDespawnEvent $event
	 * @priority MONITOR
	 */
	public function onItemDespawn(EntityDespawnEvent $event) : void{ // ItemDespawnEvent does not notify when ItemEntities are directly close()d
		$entity = $event->getEntity();
		if($entity instanceof ItemEntity){
			$this->onItemEntityDespawn($entity);
		}
	}

	private function onItemEntitySpawn(ItemEntity $entity) : void{
		$this->entities[$entity->getId()] = new ItemEntityMovementNotifier($entity, $this);
		if(count($this->entities) === 1){
			assert($this->ticker === null);
			$this->ticker = $this->scheduler->scheduleRepeatingTask(new ClosureTask(function() : void{ $this->tick(); }), HopperConfig::getInstance()->getItemsSuckingTickRate());
		}
	}

	private function onItemEntityDespawn(ItemEntity $entity) : void{
		if(isset($this->entities[$id = $entity->getId()])){
			unset($this->entities[$id]);
			if(count($this->entities) === 0){
				assert($this->ticker !== null);
				$this->ticker->cancel();
				$this->ticker = null;
			}
		}
	}

	/**
	 * @return ItemEntityMovementNotifier[]
	 */
	public function getEntities() : array{
		return $this->entities;
	}

	public function isTicking() : bool{
		return $this->ticker !== null;
	}

	private function tick() : void{
		if(!$this->scanning){
			AsyncIterator::iterate(function() : Generator{
				$this->scanning = true;
				reset($this->entities);
				while(($entity = current($this->entities)) !== false){
					$entity->update();
					next($this->entities);
					yield true;
				}
				$this->scanning = false;
			}, 10);
		}
	}
}