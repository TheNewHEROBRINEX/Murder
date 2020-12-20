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
use function array_shift;
use function count;
use function implode;

class MurderCommand extends Command implements PluginIdentifiableCommand{
	private MurderMain $plugin;

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

				$murderPlayer = $this->getPlugin()->getArenaByName($args[0]);
				if($murderPlayer !== null){
					if(!$this->getPlugin()->getServer()->isLevelLoaded($murderPlayer->getName())){
						$this->getPlugin()->getServer()->loadLevel($murderPlayer->getName());
					}
					$murderPlayer->join($sender);
				}else{
					$sender->sendMessage($this->getPlugin()->translateString("game.notExisting", [$args[0]]));
				}
				return;

			case "quit":
				if($this->badPerm($sender, "quit")){
					return;
				}

				if(count($args) > 0){
					throw new InvalidCommandSyntaxException();
				}

				$murderPlayer = $this->getPlugin()->findMurderPlayer($sender);
				if($murderPlayer !== null){
					$murderPlayer->onQuit();
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

				$worldName = $sender->getLevelNonNull()->getFolderName();
				$name = $sender->getName();
				$this->getPlugin()->getListener()->setspawns[$name][$worldName] = (int)$args[0];
				$this->getPlugin()->getListener()->setespawns[$name][$worldName] = (int)$args[1];
				$this->getPlugin()->getArenasCfg()->setNested("$worldName.spawns", []);
				$this->getPlugin()->getArenasCfg()->setNested("$worldName.espawns", []);
				$this->getPlugin()->sendMessage($this->getPlugin()->translateString("arenaSetting.playersSpawns.started", [$args[0], $worldName]), $sender);
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
