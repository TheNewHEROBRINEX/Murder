<?php
declare(strict_types=1);

namespace TheNewHEROBRINE\Murder;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\lang\TranslationContainer;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class MurderCommand extends Command implements PluginIdentifiableCommand{

	/** @var MurderMain */
	private $plugin;

	public function __construct(MurderMain $plugin){
		parent::__construct("murder", $plugin->translateString("command.description"), $plugin->translateString("command.usage"), ["mdr"]);
		$this->setPermission("murder.command.join;murder.command.quit;murder.command.setarena");
		$this->plugin = $plugin;
	}

	/**
	 * @param string[] $args
	 *
	 * @throws InvalidCommandSyntaxException
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
		if($this->plugin->isDisabled() or !$this->testPermission($sender)){
			return;
		}

		if(!$sender instanceof Player){
			$sender->sendMessage($this->getPlugin()->translateString("command.inGameOnly"));
			return;
		}

		if(count($args) === 0 or count($args) > 3){
			throw new InvalidCommandSyntaxException();
		}

		switch(array_shift($args)){
			case "join":
				if($this->badPerm($sender, "join")){
					return;
				}

				if(count($args) !== 1){
					throw new InvalidCommandSyntaxException();
				}

				$arena = $this->getPlugin()->getArenaByName($args[0]);
				if($arena !== null){
					if(!$this->getPlugin()->getServer()->isLevelLoaded($arena->getName())){
						$this->getPlugin()->getServer()->loadLevel($arena->getName());
					}
					$arena->join($sender);
				}else{
					$sender->sendMessage($this->getPlugin()->translateString("game.notExisting", [$args[0]]));
				}
				return;

			case "quit":
				if($this->badPerm($sender, "quit")){
					return;
				}

				if(count($args)){
					throw new InvalidCommandSyntaxException();
				}

				$arena = $this->getPlugin()->getArenaByName($args[0]);
				if($arena !== null){
					$arena->quit($sender);
				}else{
					$sender->sendMessage($this->getPlugin()->translateString("command.notInGame"));
				}
				return;

			case "setarena":
				if($this->badPerm($sender, "setarena")){
					return;
				}

				if(count($args) < 2 or !ctype_digit(implode("", $args))){
					throw new InvalidCommandSyntaxException();
				}

				$world = $sender->getLevel()->getFolderName();
				$name = $sender->getName();
				$this->getPlugin()->getListener()->setspawns[$name][$world] = (int)$args[0];
				$this->getPlugin()->getListener()->setespawns[$name][$world] = (int)$args[1];
				$this->getPlugin()->getArenasCfg()->setNested("$world.spawns", []);
				$this->getPlugin()->getArenasCfg()->setNested("$world.espawns", []);
				$this->getPlugin()->sendMessage($this->getPlugin()->translateString("arenaSetting.playersSpawns.started", [$args[0], $sender->getLevel()->getFolderName()]), $sender);
				return;

			default:
				throw new InvalidCommandSyntaxException();
		}
	}

	private function badPerm(CommandSender $sender, string $perm) : bool{
		if(!$sender->hasPermission("murder.command.$perm")){
			$sender->sendMessage(new TranslationContainer(TextFormat::RED . "%commands.generic.permission"));
			return true;
		}
		return false;
	}

	/**
	 * @return MurderMain
	 */
	public function getPlugin() : Plugin{
		return $this->plugin;
	}
}
