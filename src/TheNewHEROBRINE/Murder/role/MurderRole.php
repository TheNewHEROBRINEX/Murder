<?php
declare(strict_types=1);

namespace TheNewHEROBRINE\Murder\role;

//use JetBrains\PhpStorm\Pure;
use pocketmine\item\Item;
use pocketmine\item\Sword;
use pocketmine\Player;
use TheNewHEROBRINE\Murder\MurderArena;
use TheNewHEROBRINE\Murder\MurderMain;
use TheNewHEROBRINE\Murder\player\MurderPlayer;

abstract class MurderRole{
	protected MurderPlayer $murderPlayer;

	public function __construct(MurderPlayer $murderPlayer){
		$this->murderPlayer = $murderPlayer; //PHP8: promotion
	}

	abstract public function onEmeraldPickup() : bool;

	abstract public function onSwordPickup(Sword $sword) : bool;

	abstract public function onDeath(MurderPlayer $killedBy) : void;

	abstract public function onGameOut(?MurderPlayer $killedBy = null) : void;

	abstract public function getWeapon() : Item;

	abstract public function sendStartTitle() : void;

	abstract public function hasWeaponFromStart() : bool;

	abstract public function getName() : string;

	abstract public function canSprint() : bool;

	//#[Pure]
	public function getMurderPlayer() : MurderPlayer{
		return $this->murderPlayer;
	}

	//#[Pure]
	public function getMurderArena() : MurderArena{
		return $this->murderPlayer->getMurderArena();
	}

	//#[Pure]
	public function getPlayer() : Player{
		return $this->murderPlayer->getPlayer();
	}

	//#[Pure]
	public function getPlugin() : MurderMain{
		return $this->getMurderArena()->getPlugin();
	}
}