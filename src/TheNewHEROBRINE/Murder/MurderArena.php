<?php
declare(strict_types=1);

namespace TheNewHEROBRINE\Murder;

use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ReflectionProperty;
use TheNewHEROBRINE\Murder\entity\Corpse;
use TheNewHEROBRINE\Murder\entity\projectile\MurderGunProjectile;
use function array_rand;
use function array_search;
use function array_shift;
use function count;
use function get_class;
use function implode;
use function in_array;
use function shuffle;

class MurderArena{

	const GAME_IDLE = 0;
	const GAME_STARTING = 1;
	const GAME_RUNNING = 2;

	/** @var MurderMain */
	private $plugin;

	/** @var string */
	private $name;

	/** @var int */
	private $countdown;

	// /** @var int */
	// private $maxTime;

	/** @var int */
	private $state = self::GAME_IDLE;

	/** @var Player[] */
	private $players = [];

	/** @var Skin[] */
	private $skins = [];

	/** @var Player|null */
	private $murderer;

	/** @var Player[] */
	private $bystanders;

	/** @var int[][] */
	private $spawns;

	/** @var int[][] */
	private $espawns;

	/** @var Level */
	private $world;

	/** @var int */
	private $spawnEmerald = 10;

	/**
	 * @param int[][] $spawns
	 * @param int[][] $espawns
	 */
	public function __construct(MurderMain $plugin, string $name, array $spawns, array $espawns){
		$this->spawns = $spawns;
		$this->espawns = $espawns;
		$this->plugin = $plugin;
		$this->name = $name;
		$this->world = $this->getPlugin()->getServer()->getLevelByName($name);
		$this->countdown = $this->getPlugin()->getCountdown();
	}

	public function tick() : void{
		if($this->isStarting()){
			if($this->countdown === 0){
				$this->start();
				$this->broadcastMessage($this->getPlugin()->translateString("game.started"));
			}else{
				$this->broadcastPopup($this->getPlugin()->translateString("game.starting", [$this->countdown]));
				$this->countdown--;
			}
		}elseif($this->isRunning()){
			/*$padding = str_repeat(" ", 55);
			  foreach ($this->getPlayers() as $player){
				  $player->sendPopup(
					  $padding . MurderMain::MESSAGE_PREFIX . "\n" .
					  $padding . TextFormat::AQUA . $this->getPlugin()->translateString("game.popup.role") . ": " . TextFormat::GREEN . $this->getRole($player) . "\n" .
					  $padding . TextFormat::AQUA . $this->getPlugin()->translateString("game.popup.emeralds") . ": " . TextFormat::YELLOW . $player->getItemCount() . "/5\n" .
					  $padding . TextFormat::AQUA . $this->getPlugin()->translateString("game.popup.identity") . ": " . "\n" .
					  $padding . TextFormat::GREEN . $player->getDisplayName() .
					  str_repeat("\n", 3));
			  }*/
			if($this->spawnEmerald === 0){
				$this->spawnEmerald($this->espawns[array_rand($this->espawns)]);
				$this->spawnEmerald = 10;
			}
			$this->spawnEmerald--;
		}
	}

	public function join(Player $player) : void{
		if(!$this->isRunning()){
			if($this->getPlugin()->getArenaByPlayer($player) === null){
				if(count($this->getPlayers()) < count($this->spawns)){
					$this->players[] = $player;
					$player->getInventory()->clearAll();
					$player->getInventory()->sendContents($player);
					$player->teleport($this->getPlugin()->getHub()->getSpawnLocation());
					$this->broadcastMessage($this->getPlugin()->translateString("game.join", [$player->getName()]));
					if(count($this->getPlayers()) >= 2 && $this->isIdle()){
						$this->state = self::GAME_STARTING;
					}
				}else{
					$player->sendMessage($this->getPlugin()->translateString("game.full"));
				}
			}else{
				$player->sendMessage($this->getPlugin()->translateString("game.alreadyIn"));
			}
		}else{
			$player->sendMessage($this->getPlugin()->translateString("game.running"));
		}
	}

	public function quit(Player $player, bool $silent = false) : void{
		if($this->inArena($player)){
			if(!$silent){
				$this->broadcastMessage($this->getPlugin()->translateString("game.quit", [$player->getName()]));
			}
			$this->closePlayer($player);
			if($this->isRunning()){
				if($this->isMurderer($player)){
					$bystanders = [];
					$event = $this->getMurderer()->getLastDamageCause();
					$lastDamageCause = new ReflectionProperty(get_class($player), "lastDamageCause");
					$lastDamageCause->setAccessible(true);
					$lastDamageCause->setValue($player, null);
					foreach($this->getBystanders() as $bystander){
						$name = $bystander->getName();
						if($this->inArena($bystander)){
							$name = TextFormat::BLUE . $name;
							if($event instanceof EntityDamageByChildEntityEvent and $event->getChild() instanceof MurderGunProjectile and $event->getDamager() === $bystander){
								$name = TextFormat::BOLD . $name;
							}
						}else{
							$name = TextFormat::RED . $name;
						}
						if($this->getBystanders()[0] === $bystander){
							$name = TextFormat::ITALIC . $name;
						}
						$bystanders[] = $name;
					}
					$this->stop($this->getPlugin()->translateString("game.win.bystanders", [implode(TextFormat::RESET . ", ", $bystanders), $this->getName()]));
				}elseif(count($this->getPlayers()) === 1){
					$this->stop($this->getPlugin()->translateString("game.win.murderer", [$this->getMurderer()->getName(), $this->getName()]));
				}
			}elseif($this->isStarting()){
				if(count($this->getPlayers()) < 2){
					$this->state = self::GAME_IDLE;
				}
			}
		}
	}

	public function start() : void{
		$this->state = self::GAME_RUNNING;
		$skins = [];
		foreach($this->getPlayers() as $player){
			$skins[$player->getName()] = $player->getSkin();
		}
		$this->skins = $skins;
		shuffle($skins);
		$players = $this->getPlayers();
		shuffle($players);
		foreach($this->getPlayers() as $player){
			$player->setSkin(array_shift($skins));
			$player->sendSkin($this->getPlayers());
			$name = array_shift($players)->getName();
			$player->setDisplayName($name);
			$player->setNameTag($name);
			$player->setNameTagAlwaysVisible(false);
		}
		$random = array_rand($this->getPlayers(), 2);
		shuffle($random);
		$this->murderer = $this->getPlayers()[$random[0]];
		$this->bystanders[] = $this->getPlayers()[$random[1]];
		foreach($random as $key){
			$player = $this->getPlayers()[$key];
			$player->getInventory()->clearAll();
		}
		$this->getMurderer()->getInventory()->setItem(0, ItemFactory::get(ItemIds::WOODEN_SWORD)->setCustomName($this->getPlugin()->translateString("game.knife")));
		$this->getMurderer()->setFood($this->murderer->getMaxFood());
		$this->getMurderer()->sendTitle(TextFormat::BOLD . TextFormat::RED . $this->getPlugin()->translateString("game.murderer"), $this->getPlugin()->translateString("game.startSubtitle.murderer"));
		$this->getBystanders()[0]->getInventory()->setItem(0, ItemFactory::get(ItemIds::WOODEN_HOE)->setCustomName($this->getPlugin()->translateString("game.gun")));
		$this->getBystanders()[0]->sendTitle(TextFormat::BOLD . TextFormat::AQUA . $this->getPlugin()->translateString("game.bystander"), $this->getPlugin()->translateString("game.startSubtitle.detective"));
		$spawns = $this->spawns;
		shuffle($spawns);
		foreach($this->getPlayers() as $player){
			$player->setGamemode($player::ADVENTURE);
			$player->setHealth($player->getMaxHealth());
			$player->removeAllEffects();
			if($player !== $this->getMurderer()){
				$player->setFood(6);
				if($player !== $this->getBystanders()[0]){
					$player->sendTitle(TextFormat::BOLD . TextFormat::AQUA . $this->getPlugin()->translateString("game.bystander"), $this->getPlugin()->translateString("game.startSubtitle.bystander"));
					$this->bystanders[] = $player;
				}
			}
			$spawn = array_shift($spawns);
			$player->teleport(new Position($spawn[0], $spawn[1], $spawn[2], $this->getWorld()));
		}
		foreach($this->espawns as $espawn){
			$this->spawnEmerald($espawn);
		}
	}

	public function stop(string $message = "") : void{
		if($this->isRunning()){
			foreach($this->getWorld()->getPlayers() as $player){
				if($this->inArena($player)){
					$this->closePlayer($player);
				}else{
					$player->teleport($this->getPlugin()->getServer()->getDefaultLevel()->getSpawnLocation());
				}
			}
			$this->getPlugin()->broadcastMessage($message);
			$this->players = [];
			$this->skins = [];
			$this->countdown = $this->getPlugin()->getCountdown();
			$this->bystanders = [];
			$this->murderer = null;
			$this->spawnEmerald = 10;
			$this->state = self::GAME_IDLE;
			foreach($this->getWorld()->getEntities() as $entity){
				if(!$entity instanceof Player){
					$entity->flagForDespawn();
				}
			}
		}
	}

	public function closePlayer(Player $player) : void{
		if($this->inArena($player)){
			$player->setNameTagAlwaysVisible(true);
			$player->setNameTag($player->getName());
			$player->setDisplayName($player->getName());
			if(isset($this->getSkins()[$player->getName()])){
				$player->setSkin($this->getSkins()[$player->getName()]);
				$player->sendSkin();
			}
			$player->getInventory()->clearAll();
			$player->getInventory()->sendContents($player);
			$player->setGamemode($this->getPlugin()->getServer()->getDefaultGamemode());
			$player->setHealth($player->getMaxHealth());
			$player->setFood($player->getMaxFood());
			$player->removeAllEffects();
			unset($this->players[array_search($player, $this->getPlayers(), true)]);
			$player->teleport($this->getPlugin()->getServer()->getDefaultLevel()->getSpawnLocation());
		}
	}

	public function spawnCorpse(Human $player) : void{
		/** @var Corpse $corpse */
		$corpse = Entity::createEntity("Corpse", $player->getLevel(), Entity::createBaseNBT($player, null, $player->yaw, $player->pitch), $player);
		$corpse->spawnToAll();
	}

	/**
	 * @param int[] $espawn
	 */
	public function spawnEmerald(array $espawn) : void{
		$this->getWorld()->dropItem(new Vector3($espawn[0], $espawn[1], $espawn[2]), ItemFactory::get(ItemIds::EMERALD));
	}

	public function inArena(Player $player) : bool{
		return in_array($player, $this->getPlayers(), true);
	}

	public function getRole(Player $player) : string{
		return $this->isMurderer($player) ? $this->getPlugin()->translateString("game.murderer") : $this->getPlugin()->translateString("game.bystander");
	}

	public function getFullName(Player $player) : string{
		return ($player->getDisplayName() !== $player->getName()) ? ($player->getDisplayName() . " (" . $player->getName() . ")") : $player->getName();
	}

	public function broadcastMessage(string $msg) : void{
		$this->getPlugin()->broadcastMessage($msg, $this->getPlayers());
	}

	public function broadcastPopup(string $msg) : void{
		$this->getPlugin()->broadcastPopup($msg, $this->getPlayers());
	}

	public function isMurderer(Player $player) : bool{
		return $this->getMurderer() === $player;
	}

	public function isBystander(Player $player) : bool{
		return in_array($player, $this->getBystanders(), true);
	}

	public function isIdle() : bool{
		return $this->state === self::GAME_IDLE;
	}

	public function isStarting() : bool{
		return $this->state === self::GAME_STARTING;
	}

	public function isRunning() : bool{
		return $this->state === self::GAME_RUNNING;
	}

	public function getMurderer() : Player{
		return $this->murderer;
	}

	/**
	 * @return Player[]
	 */
	public function getBystanders() : array{
		return $this->bystanders;
	}

	/**
	 * @return Player[]
	 */
	public function getPlayers() : array{
		return $this->players;
	}

	public function getWorld() : Level{
		return $this->world;
	}

	/**
	 * @return Skin[]
	 */
	public function getSkins() : array{
		return $this->skins;
	}

	public function getPlugin() : MurderMain{
		return $this->plugin;
	}

	public function getName() : string{
		return $this->name;
	}

	public function __toString() : string{
		return $this->getName();
	}
}
