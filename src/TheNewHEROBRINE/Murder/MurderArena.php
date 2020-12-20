<?php
declare(strict_types=1);

namespace TheNewHEROBRINE\Murder;

//use JetBrains\PhpStorm\Pure;
use BadMethodCallException;
use InvalidArgumentException;
use pocketmine\entity\Entity;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\level\Level;
use pocketmine\level\LevelException;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\TextFormat;
use SebastianBergmann\CodeCoverage\Report\Text;
use TheNewHEROBRINE\Murder\entity\Corpse;
use TheNewHEROBRINE\Murder\game\GameEndCause;
use TheNewHEROBRINE\Murder\player\MurderIdentity;
use TheNewHEROBRINE\Murder\player\MurderPlayer;
use TheNewHEROBRINE\Murder\role\Bystander;
use TheNewHEROBRINE\Murder\role\Detective;
use TheNewHEROBRINE\Murder\role\Murderer;
use function array_filter;
use function array_key_exists;
use function array_rand;
use function array_shift;
use function count;
use function get_class;
use function shuffle;
use function strtolower;
use function usort;

class MurderArena{

	const GAME_IDLE = 0;
	const GAME_STARTING = 1;
	const GAME_RUNNING = 2;

	/** @var MurderMain */
	private MurderMain $plugin;

	/** @var string */
	private string $name;

	/** @var int */
	private int $countdown;

	// /** @var int */
	// private $maxTime;

	/** @var int */
	/** @phpstan-var self::GAME_IDLE|self::GAME_STARING|self::GAME_RUNNING */
	private int $state = self::GAME_IDLE;

	/** @var MurderPlayer[] */
	private array $murderPlayers = [];

	/** @var int[][]
	 * @phpstan-var list<array{int, int, int}>
	 */
	private array $spawns;

	/** @var int[][] */
	private array $espawns;

	/** @var int */
	private int $spawnEmerald = 10;

	/**
	 * @param int[][] $spawns
	 * @phpstan-param list<array{int, int, int}> $spawns
	 *
	 * @param int[][] $espawns
	 * @phpstan-param list<array{int, int, int}> $espawns
	 */
	public function __construct(MurderMain $plugin, string $name, array $spawns, array $espawns){
		$this->spawns = $spawns;
		$this->espawns = $espawns;
		$this->plugin = $plugin;
		$this->name = $name;
		$this->countdown = $this->getPlugin()->getCountdown();
	}

	public function tick() : void{
		if($this->isStarting()){
			if($this->countdown === 0){
				$this->start();
			}else{
				$this->broadcastPopup($this->getPlugin()->translateString("game.starting", [(string)$this->countdown]));
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
			if($this->plugin->findMurderPlayer($player) === null){
				if(count($this->murderPlayers) < count($this->spawns)){
					MurderPlayer::createPlayer($player, $this);
					if(count($this->murderPlayers) >= 2 and $this->isIdle()){
						$this->state = self::GAME_STARTING;
					}
				}else{
					$player->sendMessage($this->getPlugin()->translateString("game.full"));
				}
			}else{
				$player->sendMessage($this->getPlugin()->translateString("game.alreadyIn"));
			}
		}else{
			MurderPlayer::createSpectator($player, $this, true);
			$player->sendMessage($this->getPlugin()->translateString("game.running")); //TODO: change message
		}
	}

	/*public function onMurderPlayerQuit(MurderPlayer $murderPlayer) : void{
		if($this->inArena($player)){
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
				}elseif(count($this->getMurderPlayers()) === 1){
					$this->stop($this->getPlugin()->translateString("game.win.murderer", [$this->getMurderer()->getName(), $this->getName()]));
				}
			}
		}
	}*/

	public function start() : void{
		$this->state = self::GAME_RUNNING;
		$spectators = $this->getWorld()->getPlayers();
		$spawns = $this->spawns;
		shuffle($spawns);
		$shuffledPlayers = $this->murderPlayers;
		shuffle($shuffledPlayers);
		/** @phpstan-var string[] $randomPlayers */
		$randomPlayers = array_rand($this->murderPlayers, 2);
		$murderer = $this->murderPlayers[$randomPlayers[0]];
		$detective = $this->murderPlayers[$randomPlayers[1]];
		foreach($this->murderPlayers as $murderPlayer){
			if($murderPlayer === $murderer){
				$murderPlayer->setRole(new Murderer($murderPlayer));
			}elseif($murderPlayer === $detective){
				$murderPlayer->setRole(new Detective($murderPlayer));
			}else{
				$murderPlayer->setRole(new Bystander($murderPlayer));
			}
			/** @phpstan-var MurderPlayer $randomPlayer */
			$randomPlayer = array_shift($shuffledPlayers);
			$murderPlayer->setIdentity(new MurderIdentity($randomPlayer->getPlayer()->getName(), $randomPlayer->getPlayer()->getSkin()));
			/** @phpstan-var array{int, int, int} $spawn */
			$spawn = array_shift($spawns); //PHP8: positional unpacking
			$murderPlayer->setSpawn(new Position($spawn[0], $spawn[1], $spawn[2], $this->getWorld()));
			$murderPlayer->onGameStart();
		}
		foreach($spectators as $spectator){
			MurderPlayer::createSpectator($spectator, $this, false);
		}

		foreach($this->espawns as $espawn){
			$this->spawnEmerald($espawn);
		}
	}

	public function addPlayer(MurderPlayer $murderPlayer) : void{
		$playerName = $murderPlayer->getPlayer()->getName();
		if($this->isRunning()){
			if(!$murderPlayer->isSpectator()){
				throw new InvalidArgumentException("Tried to add player " . $playerName . " as non-spectator to arena " . $this->getName() . " while it is running");
			}elseif($murderPlayer->getRole() !== null){
				throw new InvalidArgumentException("Tried to add player " . $playerName . " with non-null role to arena " . $this->getName() . " while it is running");
			}
		}

		if(array_key_exists(strtolower($playerName), $this->murderPlayers)){
			if($this->murderPlayers[strtolower($playerName)] === $murderPlayer){
				throw new InvalidArgumentException("Player " . $playerName . " is already being tracked by arena " . $this->getName());
			}else{
				throw new AssumptionFailedError("Found two different Murder sessions for the same player " . $playerName);
			}
		}
		$this->murderPlayers[strtolower($murderPlayer->getPlayer()->getName())] = $murderPlayer;

		if($this->isIdle() and count($this->murderPlayers) >= 2){
			$this->state = self::GAME_STARTING;
		}
	}

	public function removePlayer(MurderPlayer $murderPlayer) : void{
		$playerName = $murderPlayer->getPlayer()->getName();
		if($this->isRunning() and $murderPlayer->getRole() !== null){
			throw new InvalidArgumentException("Tried to remove player " . $playerName . "  with non-null role from arena " . $this->getName() . " while it is running");
		}elseif(!array_key_exists(strtolower($playerName), $this->murderPlayers)){
			throw new InvalidArgumentException("Tried to remove player " . $playerName . " from arena " . $this->getName() . " but that player was not being tracked by this arena");
		}
		unset($this->murderPlayers[strtolower($playerName)]);

		if($this->isStarting() and count($this->murderPlayers) < 2){
			$this->state = self::GAME_IDLE;
		}
	}

	/**
	 * @param int[] $espawn
	 */
	public function spawnEmerald(array $espawn) : void{
		$this->getWorld()->dropItem(new Vector3($espawn[0], $espawn[1], $espawn[2]), ItemFactory::get(ItemIds::EMERALD));
	}

	//#[Pure]
	public function inArena(Player $player) : bool{
		return isset($this->murderPlayers[$player->getName()]);
	}

	//#[Pure]
	public function getMurderPlayer(Player $player) : ?MurderPlayer{
		return $this->murderPlayers[$player->getName()] ?? null;
	}

	//#[Pure]
	public function getFullName(Player $player) : string{
		return ($player->getDisplayName() !== $player->getName()) ? ($player->getDisplayName() . " (" . $player->getName() . ")") : $player->getName();
	}

	public function broadcastMessage(string $msg) : void{
		foreach($this->murderPlayers as $murderPlayer){
			if($murderPlayer->isInGame()){
				$murderPlayer->getPlayer()->sendMessage($msg);
			}
		}
	}

	public function broadcastPopup(string $msg) : void{
		foreach($this->murderPlayers as $murderPlayer){
			if($murderPlayer->isInGame()){
				$murderPlayer->getPlayer()->sendPopup($msg);
			}
		}
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

	/**
	 * @return MurderPlayer[]
	 */
	public function getMurderPlayers() : array{
		return $this->murderPlayers;
	}

	public function getWorld() : Level{
		$worldName = $this->name;
		if(!$this->plugin->getServer()->isLevelLoaded($worldName)){
			$this->plugin->getServer()->loadLevel($worldName);
		}
		$world = $this->plugin->getServer()->getLevelByName($worldName);
		if($world === null){
			throw new LevelException("World for Murder arena " . $worldName . " cannot be loaded");
		}
		return $world;
	}

	public function getPlugin() : MurderMain{
		return $this->plugin;
	}

	public function getName() : string{
		return $this->name;
	}

	public function getWaitingLobby() : Level{
		$world = $this->getPlugin()->getWaitingLobby(); //TODO: per-arena waiting lobby
		if($world === null){
			throw new LevelException("Waiting lobby for Murder arena " . $this->name . " cannot be loaded");
		}
		return $world;
	}

	/**
	 * @return MurderPlayer[]
	 * @phpstan-return list<MurderPlayer>
	 */
	public function getAliveBystanders() : array{
		return array_filter($this->murderPlayers, function(MurderPlayer $murderPlayer) : bool{
			return $murderPlayer->getRole() instanceof Bystander and $murderPlayer->isAlive();
		}); //PHP8: arrow function
	}

	/**
	 * @return MurderPlayer[]
	 * @phpstan-return list<MurderPlayer>
	 */
	public function getOriginalPlayers() : array{
		return array_filter($this->murderPlayers, function(MurderPlayer $murderPlayer) : bool{
			return $murderPlayer->getRole() !== null;
		});
	}

	public function getMurderer() : MurderPlayer{
		if(!$this->isRunning()){
			throw new BadMethodCallException("Tried to get murderer while game is not running in arena " . $this->getName());
		}

		$murderer = null;
		foreach($this->murderPlayers as $murderPlayer){
			if($murderPlayer->isInGame() and $murderPlayer->getRole() instanceof Murderer){
				if($murderer === null){
					$murderer = $murderPlayer;
				}else{
					throw new AssumptionFailedError("There is more than one in-game murderer in arena " . $this->getName());
				}
			}
		}

		if($murderer === null or !$murderer->isAlive()){
			throw new AssumptionFailedError("There is no alive murderer in arena " . $this->getName());
		}

		return $murderer;
	}

	public function endGame(GameEndCause $cause) : void{
		$originalPlayers = $this->getOriginalPlayers();
		$priorities = [
			Murderer::class => 0,
			Detective::class => 1,
			Bystander::class => 2
		];
		usort($originalPlayers, function(MurderPlayer $murderPlayer1, MurderPlayer $murderPlayer2) use ($priorities) : int{
			return $priorities[get_class($murderPlayer1->getRoleNonNull())] <=> $priorities[get_class($murderPlayer2->getRoleNonNull())];
		});
		foreach($originalPlayers as $murderPlayer){
			$this->broadcastMessage($murderPlayer->getIdentity()->getUsername() . " » " . $murderPlayer->getPlayer()->getName() . " " . (!$murderPlayer->isAlive() ? TextFormat::STRIKETHROUGH : "") . "(" . strtolower($murderPlayer->getRoleNonNull()->getName()) . ")");
		}
		$this->broadcastMessage($cause->getMessage());
		foreach($this->murderPlayers as $murderPlayer){
			if($murderPlayer->isInGame()){
				$murderPlayer->onLeave();
			}
		}
		$this->murderPlayers = [];
		$this->countdown = $this->getPlugin()->getCountdown();
		$this->spawnEmerald = 10;
		$this->state = self::GAME_IDLE;
		foreach($this->getWorld()->getEntities() as $entity){
			$entity->flagForDespawn();
		}
	}
}
