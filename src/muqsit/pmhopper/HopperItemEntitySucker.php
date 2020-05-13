<?php

declare(strict_types=1);

namespace muqsit\pmhopper;

use pocketmine\block\tile\Hopper;
use pocketmine\entity\object\ItemEntity;
use pocketmine\inventory\Inventory;
use pocketmine\math\AxisAlignedBB;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\World;

class HopperItemEntitySucker{

	/** @var HopperItemEntitySucker|null */
	private static $instance;

	public static function hasInstance() : bool{
		return self::$instance !== null;
	}

	public static function getInstance() : HopperItemEntitySucker{
		return self::$instance;
	}

	public static function setInstance(HopperItemEntitySucker $instance) : void{
		self::$instance = $instance;
	}

	/** @var array<int, array<int, int>> */
	private $subscriptions = [];

	/** @var array<int, array<int, array<int, booL>>> */
	private $updates = [];

	/** @var Server */
	private $server;

	public function __construct(Plugin $plugin){
		$this->server = Server::getInstance();
		$plugin->getScheduler()->scheduleRepeatingTask(new ClosureTask(function(int $currentTick) : void{
			$this->tick();
		}), HopperConfig::getInstance()->getItemsSuckingTickRate());
	}

	protected function getUpdateTick() : int{
		return $this->server->getTick() + HopperConfig::getInstance()->getItemsSuckingTickRate();
	}

	public function subscribe(Position $position) : void{
		$this->subscribeTo($position->getWorldNonNull()->getId(), World::blockHash($position->x, $position->y, $position->z));
	}

	public function subscribeTo(int $world_id, int $hash) : void{
		$update_tick = $this->getUpdateTick();
		$this->subscriptions[$world_id][$hash] = $update_tick;
		$this->updates[$update_tick][$world_id][$hash] = true;
	}

	public function unsubscribe(Position $position) : void{
		$this->unsubscribeFrom($position->getWorldNonNull()->getId(), World::blockHash($position->x, $position->y, $position->z));
	}

	private function unsubscribeFrom(int $world_id, int $hash) : void{
		if(isset($this->subscriptions[$world_id][$hash])){
			$update_tick = $this->subscriptions[$world_id][$hash];
			unset(
				$this->subscriptions[$world_id][$hash],
				$this->updates[$update_tick][$world_id][$hash]
			);
			if(count($this->subscriptions[$world_id]) === 0){
				unset($this->subscriptions[$world_id]);
			}
			if(count($this->updates[$update_tick][$world_id]) === 0){
				unset($this->updates[$update_tick][$world_id]);
				if(count($this->updates[$update_tick]) === 0){
					unset($this->updates[$update_tick]);
				}
			}
		}
	}

	public function unsubscribeWorld(World $world) : void{
		if(isset($this->subscriptions[$world_id = $world->getId()])){
			foreach($this->subscriptions[$world_id] as $hash => $update_tick){
				unset($this->updates[$update_tick][$world_id]);
				if(count($this->updates[$update_tick]) === 0){
					unset($this->updates[$update_tick]);
				}
			}
			unset($this->subscriptions[$world_id]);
		}
	}

	private function tick() : void{
		if(isset($this->updates[$tick = $this->server->getTick()])){
			$world_manager = $this->server->getWorldManager();
			foreach($this->updates[$tick] as $world_id => $subscriptions){
				$world = $world_manager->getWorld($world_id);
				assert($world !== null); // using world events to gc unloaded worlds already
				foreach($subscriptions as $hash => $_){
					$this->unsubscribeFrom($world_id, $hash);
					World::getBlockXYZ($hash, $x, $y, $z);
					$tile = $world->getTileAt($x, $y, $z);
					if($tile instanceof Hopper && $this->tickHopper($tile->getPos(), $tile->getInventory())){
						$this->subscribeTo($world_id, $hash);
					}
				}
			}
		}
	}

	/**
	 * Ticks the hopper and returns whether to renew their subscription.
	 *
	 * @param Position $position
	 * @param Inventory $inventory
	 * @return bool
	 */
	protected function tickHopper(Position $position, Inventory $inventory) : bool{
		foreach($position->world->getNearbyEntities(AxisAlignedBB::one()->offset($position->x, $position->y + 1, $position->z)) as $entity){
			if($entity instanceof ItemEntity && !$entity->isFlaggedForDespawn()){
				$item = $entity->getItem();
				if(!$item->isNull()){
					$residue_count = 0;
					foreach($inventory->addItem($item) as $residue){
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
		return true;
	}

	public function isSubscriptionsEmpty() : bool{
		return count($this->subscriptions) === 0;
	}

	public function getSubscriptionsCount() : int{
		$i = 0;
		foreach($this->subscriptions as $subscription){
			$i += count($subscription);
		}
		return $i;
	}

	public function isUpdatesEmpty() : bool{
		return count($this->updates) === 0;
	}

	public function getUpdatesCount() : int{
		$i = 0;
		foreach($this->updates as $update_tick => $updates){
			foreach($updates as $updates_list){
				$i += count($updates_list);
			}
		}
		return $i;
	}
}