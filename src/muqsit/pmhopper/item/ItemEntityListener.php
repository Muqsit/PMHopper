<?php

declare(strict_types=1);

namespace muqsit\pmhopper\item;

use ArrayIterator;
use InvalidStateException;
use muqsit\asynciterator\AsyncIterator;
use muqsit\asynciterator\handler\AsyncForeachHandler;
use muqsit\asynciterator\handler\AsyncForeachResult;
use muqsit\pmhopper\HopperConfig;
use muqsit\pmhopper\Loader;
use pocketmine\block\tile\Hopper;
use pocketmine\entity\object\ItemEntity;
use pocketmine\event\entity\EntityDespawnEvent;
use pocketmine\event\entity\ItemSpawnEvent;
use pocketmine\event\Listener;
use pocketmine\world\World;

final class ItemEntityListener implements Listener{

	/** @var AsyncIterator */
	private $async_iterator;

	/** @var AsyncForeachHandler<int, ItemEntityMovementNotifier>|null */
	private $ticker;

	/**
	 * @var ItemEntityMovementNotifier[]
	 *
	 * @phpstan-var array<int, ItemEntityMovementNotifier>
	 */
	private $entities = [];

	public function __construct(Loader $plugin){
		$this->async_iterator = new AsyncIterator($plugin->getScheduler());
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
		for($i = 0; $i >= -1; --$i){
			$tile = $world->getTileAt($x, $y + $i, $z);
			if($tile instanceof Hopper){
				$item = $entity->getItem();
				if(!$item->isNull()){
					$residue_count = 0;
					foreach($tile->getInventory()->addItem($item) as $residue){
						$residue_count += $residue->getCount();
					}
					$item->setCount($residue_count);
					if($residue_count === 0){
						$entity->flagForDespawn();
					}
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
		if(!$entity->isClosed() && !$entity->isFlaggedForDespawn()){
			$this->entities[$entity->getId()] = new ItemEntityMovementNotifier($entity, $this);
			if($this->ticker === null){
				$this->tick();
			}
		}
	}

	private function onItemEntityDespawn(ItemEntity $entity) : void{
		if(isset($this->entities[$id = $entity->getId()])){
			unset($this->entities[$id]);
			if($this->ticker !== null && count($this->entities) === 0){
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

	private function tick() : bool{
		if($this->ticker !== null){
			throw new InvalidStateException("Tried scheduling multiple item entity tickers");
		}

		$config = HopperConfig::getInstance();
		$tick_rate = $config->getItemSuckingTickRate();
		if($tick_rate > 0){
			$per_tick = $config->getItemSuckingPerTick();
			$this->ticker = $this->async_iterator->forEach(new ArrayIterator($this->entities), $per_tick, $tick_rate)->as(static function(int $id, ItemEntityMovementNotifier $notifier) : AsyncForeachResult{
				$notifier->update();
				return AsyncForeachResult::CONTINUE();
			})->onCompletion(function() : void{
				$this->ticker = null;
				$this->tick();
			});
			return true;
		}

		return false;
	}
}