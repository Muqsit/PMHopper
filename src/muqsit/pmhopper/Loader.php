<?php

declare(strict_types=1);

namespace muqsit\pmhopper;

use muqsit\pmhopper\behaviour\HopperBehaviourManager;
use muqsit\pmhopper\item\ItemEntityListener;
use muqsit\pmhopper\utils\iterator\AsyncIterator;
use pocketmine\block\BlockFactory;
use pocketmine\block\VanillaBlocks;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

final class Loader extends PluginBase implements Listener{

	/** @var ItemEntityListener */
	private $item_entity_listener;

	protected function onLoad() : void{
		$hopper = VanillaBlocks::HOPPER();
		BlockFactory::getInstance()->register(new Hopper($hopper->getIdInfo(), $hopper->getName(), $hopper->getBreakInfo()), true);

		HopperBehaviourManager::registerDefaults();
	}

	protected function onEnable() : void{
		if(!HopperConfig::hasInstance()){
			$config = $this->getConfig();
			HopperConfig::setInstance(new HopperConfig(
				$config->getNested("transfer.tick-rate"),
				$config->getNested("transfer.per-tick"),
				$config->getNested("item-sucking.tick-rate"),
				$config->getNested("item-sucking.per-tick")
			));
		}

		AsyncIterator::init($this);
		$this->item_entity_listener = new ItemEntityListener($this);

		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		if($this->getConfig()->get("debug", false)){
			$command = new PluginCommand("pmhopper", $this, $this);
			$command->setPermission("pmhopper.command");
			$this->getServer()->getCommandMap()->register($this->getName(), $command);
		}
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if(isset($args[0])){
			switch($args[0]){
				case "debugiel":
					$sender->sendMessage($this->formatDebug([
						"isTicking" => $this->item_entity_listener->isTicking(),
						"count(getEntities)" => count($this->item_entity_listener->getEntities())
					], [
						"isTicking must be false if count(getEntities) is 0"
					]));
					return true;
			}
		}

		$sender->sendMessage(
			TextFormat::GOLD . "PMHopper Debug Command" . TextFormat::EOL .
			TextFormat::GOLD . "/{$label} debugiel" . TextFormat::GRAY . " - Information about item entity listener"
		);
		return true;
	}

	/**
	 * @param array<string, mixed> $kv_entries
	 * @param string[] $assertions
	 * @return string
	 */
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
			$result .= TextFormat::GOLD . "{$k}: " . TextFormat::WHITE . $value . TextFormat::EOL;
		}
		if(count($assertions) > 0){
			$result .= TextFormat::RED . "Assertions (" . count($assertions) . "):" . TextFormat::EOL;
			foreach($assertions as $assertion){
				$result .= TextFormat::RED . " - {$assertion}" . TextFormat::EOL;
			}
		}
		return $result;
	}
}