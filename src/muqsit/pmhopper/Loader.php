<?php

declare(strict_types=1);

namespace muqsit\pmhopper;

use pocketmine\block\BlockFactory;
use pocketmine\block\VanillaBlocks;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\event\Listener;
use pocketmine\event\world\WorldUnloadEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

final class Loader extends PluginBase implements Listener{

	protected function onLoad() : void{
		$hopper = VanillaBlocks::HOPPER();
		BlockFactory::getInstance()->register(new Hopper($hopper->getIdInfo(), $hopper->getName(), $hopper->getBreakInfo()), true);
	}

	protected function onEnable() : void{
		if(!HopperConfig::hasInstance()){
			$config = $this->getConfig();
			HopperConfig::setInstance(new HopperConfig(
				$config->get("transfer-cooldown"),
				$config->get("items-sucked"),
				$config->get("items-sucking-tick-rate")
			));
		}

		if(!HopperItemEntitySucker::hasInstance()){
			HopperItemEntitySucker::setInstance(new HopperItemEntitySucker($this));
		}

		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		if($this->getConfig()->get("debug", false)){
			$command = new PluginCommand("pmhopper", $this, $this);
			$command->setPermission("pmhopper.command");
			$this->getServer()->getCommandMap()->register($this->getName(), $command);
		}
	}

	/**
	 * @param WorldUnloadEvent $event
	 * @priority MONITOR
	 */
	public function onWorldUnload(WorldUnloadEvent $event) : void{
		HopperItemEntitySucker::getInstance()->unsubscribeWorld($event->getWorld());
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if(isset($args[0])){
			switch($args[0]){
				case "debugsucker":
					$instance = HopperItemEntitySucker::getInstance();
					$sender->sendMessage($this->formatDebug([
						"isSubscriptionsEmpty" => $instance->isSubscriptionsEmpty(),
						"getSubscriptionsCount" => $instance->getSubscriptionsCount(),
						"isUpdatesEmpty" => $instance->isUpdatesEmpty(),
						"getUpdatesCount" => $instance->getUpdatesCount()
					], [
						"If subscriptions count is 0, subscription must be empty",
						"If updates count is 0, updates must be empty"
					]));
					return true;
			}
		}

		$sender->sendMessage(
			TextFormat::GOLD . "PMHopper Debug Command" . TextFormat::EOL .
			TextFormat::GOLD . "/" . $label . " debugsucker" . TextFormat::GRAY . " - Information about HopperItemEntitySucker"
		);
		return true;
	}

	private function formatDebug(array $kv_entries, array $assertions = []) : string{
		$result = "";
		foreach($kv_entries as $k => $v){
			switch(gettype($v)){
				case "boolean":
					$value = $v ? "true" : "false";
					break;
				default:
					$value = (string) $v;
					break;
			}
			$result .= TextFormat::GOLD . $k . ": " . TextFormat::WHITE . $value . TextFormat::EOL;
		}
		if(count($assertions) > 0){
			$result .= TextFormat::RED . "Assertions (" . count($assertions) . "):" . TextFormat::EOL;
			foreach($assertions as $assertion){
				$result .= TextFormat::RED . " - " . $assertion . TextFormat::EOL;
			}
		}
		return $result;
	}
}