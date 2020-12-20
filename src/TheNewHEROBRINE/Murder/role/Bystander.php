<?php
declare(strict_types=1);

namespace TheNewHEROBRINE\Murder\role;

use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\Sword;
use pocketmine\utils\TextFormat;
use TheNewHEROBRINE\Murder\game\NoBystandersLeft;
use TheNewHEROBRINE\Murder\player\MurderPlayer;

class Bystander extends MurderRole{
	public function onEmeraldPickup() : bool{
		// TODO: Implement onEmeraldPickup() method.
	}

	public function onSwordPickup(Sword $sword) : bool{
		return false;
	}

	public function onGameStart() : void{
		$this->getPlayer()->setFood(6);
		$this->sendStartTitle();
	}

	public function onDeath(MurderPlayer $killedBy) : void{

	}

	public function onGameOut(?MurderPlayer $killedBy = null) : void{
		if(count($this->getMurderArena()->getAliveBystanders()) === 0){
			$this->getMurderArena()->endGame(new NoBystandersLeft());
		}
	}

	public function getWeapon() : Item{
		return ItemFactory::get(ItemIds::WOODEN_HOE)->setCustomName($this->getPlugin()->translateString("game.gun"));
	}

	public function sendStartTitle() : void{
		$this->getPlayer()->sendTitle(TextFormat::BOLD . TextFormat::AQUA . $this->getName(), $this->getPlugin()->translateString("game.start.subtitle.bystander"));
	}

	public function hasWeaponFromStart() : bool{
		return false;
	}

	public function getName() : string{
		return $this->getPlugin()->translateString("game.bystander");
	}

	public function canSprint() : bool{
		return false;
	}
}