<?php

declare(strict_types=1);

namespace muqsit\pmhopper\behaviour;

use pocketmine\block\tile\EnderChest;
use pocketmine\block\tile\Furnace;
use pocketmine\block\tile\Tile;
use pocketmine\block\tile\TileFactory;
use pocketmine\utils\Utils;

final class HopperBehaviourManager{

	/** @var HopperBehaviour[] */
	private static $behaviours = [];

	/** @var HopperBehaviour */
	private static $fallback;

	/** @var string[] */
	private static $cache = [];

	public static function registerDefaults() : void{
		self::registerFallback(DefaultHopperBehaviour::getInstance());
		self::register(EnderChest::class, ImmobileHopperBehaviour::getInstance());
		self::register(Furnace::class, new FurnaceHopperBehaviour());
	}

	/**
	 * @param string $tile_class
	 * @param HopperBehaviour $behaviour
	 *
	 * @phpstan-param class-string<Tile> $tile_class
	 */
	public static function register(string $tile_class, HopperBehaviour $behaviour) : void{
		Utils::testValidInstance($tile_class, Tile::class);
		self::$behaviours[$tile_class] = $behaviour;
		self::$cache = [];
	}

	public static function registerFallback(HopperBehaviour $behaviour) : void{
		self::$fallback = $behaviour;
	}

	public static function get(string $tile_class) : HopperBehaviour{
		return self::$behaviours[$tile_class] ?? self::$fallback;
	}

	public static function getFromTile(Tile $tile) : HopperBehaviour{
		if(!isset(self::$cache[$class = get_class($tile)])){
			$tile_factory = TileFactory::getInstance();
			$tile_save_id = $tile_factory->getSaveId($class);
			/**
			 * @phpstan-var class-string<Tile> $tile_class
			 */
			foreach(self::$behaviours as $tile_class => $_){
				if($tile_factory->getSaveId($tile_class) === $tile_save_id){
					self::$cache[$class] = $tile_class;
					break;
				}
			}
		}

		return isset(self::$cache[$class]) ? self::get(self::$cache[$class]) : self::$fallback;
	}
}