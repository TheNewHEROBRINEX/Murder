<?php
declare(strict_types=1);

namespace TheNewHEROBRINE\Murder\player;

use BadMethodCallException;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;
use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\item\Sword;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\utils\AssumptionFailedError;
use TheNewHEROBRINE\Murder\entity\Corpse;
use TheNewHEROBRINE\Murder\MurderArena;
use TheNewHEROBRINE\Murder\role\MurderRole;
use function is_array;

class MurderPlayer{

	const STATUS_NOT_IN_GAME = 0;
	const STATUS_ALIVE = 1;
	const STATUS_SPECTATOR = 2;

	private ?MurderRole $role = null;

	private ?Position $spawn = null;

	private ?MurderIdentity $identity = null;

	private ?Skin $originalSkin = null;
	private MurderArena $murderArena;
	private Player $player;
	private int $status;

	private function __construct(
		Player $player,
		MurderArena $arena
	){
		$this->player = $player;
		$this->murderArena = $arena;
		$player->getInventory()->clearAll();
		$player->getArmorInventory()->clearAll();
		$player->getCursorInventory()->clearAll();
		$player->setHealth($player->getMaxHealth());
		$player->setFood($player->getMaxFood());
		$player->removeAllEffects();
	}

	public static function createPlayer(Player $player, MurderArena $arena) : self{
		$murderPlayer = new self($player, $arena);
		$murderPlayer->status = self::STATUS_NOT_IN_GAME;
		$player->setGamemode(Player::ADVENTURE);
		$player->teleport($arena->getWaitingLobby()->getSpawnLocation());
		$arena->broadcastMessage($arena->getPlugin()->translateString("game.join", [$player->getName()]));
		$arena->addPlayer($murderPlayer);
		return $murderPlayer;
	}

	public static function createSpectator(Player $player, MurderArena $arena, bool $autoTeleport) : self{
		$murderPlayer = new self($player, $arena);
		$murderPlayer->status = self::STATUS_SPECTATOR;
		$player->setGamemode(Player::SPECTATOR);
		if($autoTeleport){
			$player->teleport($arena->getWorld()->getSpawnLocation());
		}
		$arena->addPlayer($murderPlayer);
		return $murderPlayer;
	}

	public function onGameStart() : void{
		if(!$this->spawn instanceof Position){
			throw new BadMethodCallException("Tried to start game on Murder arena " . $this->murderArena->getName() . " but Murder player " . $this->player->getName() . " did not have a valid spawn position set");
		}
		if(!$this->identity instanceof MurderIdentity){
			throw new BadMethodCallException("Tried to start game on Murder arena " . $this->murderArena->getName() . " but Murder player " . $this->player->getName() . " did not have a valid secret identiy set");
		}
		$this->status = self::STATUS_ALIVE;
		$this->player->getInventory()->clearAll();
		$this->player->teleport($this->spawn);
		$role = $this->getRoleNonNull();
		$this->player->setFood($role->canSprint() ? 10 : 6);
		$role->sendStartTitle();
		$this->player->sendMessage($this->murderArena->getPlugin()->translateString("game.start.identity", [$this->identity->getUsername()]));
		if($role->hasWeaponFromStart()){
			$this->getPlayer()->getInventory()->setItem(0, $role->getWeapon());
			$this->getPlayer()->getInventory()->setHeldItemIndex(0);
		}
	}

	public function onDeath(MurderPlayer $killedBy) : void{
		$this->status = self::STATUS_SPECTATOR;
		//TODO: exceptions
		$player = $this->getPlayer();
		$this->murderArena->getWorld()->addParticle(new DestroyBlockParticle($player->add(0, 0.5, 0), BlockFactory::get(BlockIds::REDSTONE_BLOCK)));
		/** @var Corpse $corpse */
		$corpse = Entity::createEntity("Corpse", $this->murderArena->getWorld(), Entity::createBaseNBT($player, null, $player->yaw, $player->pitch), $this);
		$corpse->spawnToAll();
		$player->sendTitle($this->murderArena->getPlugin()->translateString("game.death.title"), $this->murderArena->getPlugin()->translateString("game.death.subtitle", [$killedBy->getIdentity()->getUsername()]));
		$player->setFood($player->getMaxFood());
		$player->removeAllEffects();
		$player->setGamemode(Player::SPECTATOR);
		$player->getInventory()->clearAll();
		$this->getRoleNonNull()->onGameOut($killedBy);
	}

	public function onLeave() : void{
		if($this->role === null){
			$this->murderArena->removePlayer($this); //removing before teleport to prevent triggering an infinite loop with EntityChangeLevelEvent
		}
		$this->player->getInventory()->clearAll();
		$this->player->setGamemode($this->murderArena->getPlugin()->getServer()->getDefaultGamemode());
		$this->player->teleport($this->murderArena->getPlugin()->getServer()->getDefaultLevel()->getSpawnLocation());
		if($this->role !== null){
			$this->status = self::STATUS_NOT_IN_GAME;
			$this->restoreIdentity();
			$this->player->setFood($this->player->getMaxFood());
			$this->player->removeAllEffects();
			$this->role->onGameOut();
		}
	}

	public function onQuit() : void{
		if($this->role === null){
			$this->getMurderArena()->removePlayer($this);
			if(!$this->murderArena->isRunning()){
				$this->getMurderArena()->broadcastMessage($this->murderArena->getPlugin()->translateString("game.quit", [$this->player->getName()]));
			}
		}else{
			$this->status = self::STATUS_NOT_IN_GAME;
			$this->role->onGameOut();
		}
	}

	public function onEmeraldPickup() : bool{
		return $this->isAlive() and $this->role->onEmeraldPickup();
	}

	public function onSwordPickup(Sword $sword) : bool{
		return $this->isAlive() and $this->role->onSwordPickup($sword);
	}

	public function getPlayer() : Player{
		return $this->player;
	}

	public function getMurderArena() : MurderArena{
		return $this->murderArena;
	}

	public function getRole() : ?MurderRole{
		return $this->role;
	}

	/**
	 * @throws AssumptionFailedError
	 */
	public function getRoleNonNull() : MurderRole{
		$role = $this->role;
		if($role === null){
			throw new AssumptionFailedError("Role of MurderPlayer " . $this->player->getName() . " is null");
		}
		return $role;
	}

	public function setRole(?MurderRole $role) : void{
		$this->role = $role;
	}

	public function getStatus() : int{
		return $this->status;
	}

	public function isAlive() : bool{
		return $this->status === self::STATUS_ALIVE;
	}

	public function isInGame() : bool{
		return $this->status !== self::STATUS_NOT_IN_GAME;
	}

	public function isSpectator() : bool{
		return $this->status === self::STATUS_SPECTATOR;
	}

	public function getIdentity() : ?MurderIdentity{
		return $this->identity;
	}

	public function setIdentity(MurderIdentity $identity) : void{
		$this->identity = $identity;
		$this->player->setDisplayName($identity->getUsername());
		$this->player->setNameTag($identity->getUsername());
		$this->player->setNameTagAlwaysVisible(false);
		if($this->originalSkin === null){
			$this->originalSkin = $this->player->getSkin();
		}
		$this->player->setSkin($identity->getSkin());
		$this->player->sendSkin();
	}

	public function restoreIdentity() : void{
		$this->player->setDisplayName($this->player->getName());
		$this->player->setNameTag($this->player->getName());
		$this->player->setNameTagAlwaysVisible(false);
		if($this->originalSkin !== null){
			$this->player->setSkin($this->originalSkin);
			$this->player->sendSkin();
		}
	}

	/**
	 * @return Position|null
	 */
	public function getSpawn() : ?Position{
		return $this->spawn;
	}

	/**
	 * @param Position $spawn
	 */
	public function setSpawn(Position $spawn) : void{
		$this->spawn = $spawn;
	}
}