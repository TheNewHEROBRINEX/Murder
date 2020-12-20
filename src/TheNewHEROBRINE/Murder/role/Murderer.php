<?php
declare(strict_types=1);

namespace TheNewHEROBRINE\Murder\role;

use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\Sword;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\TextFormat;
use TheNewHEROBRINE\Murder\game\MurderGotKilled;
use TheNewHEROBRINE\Murder\player\MurderPlayer;
use function array_rand;
use function count;

class Murderer extends MurderRole{
	public function onEmeraldPickup() : bool{
		// TODO: Implement onEmeraldPickup() method.
	}

	public function onSwordPickup(Sword $sword) : bool{
		return true;
	}

	public function onDeath(MurderPlayer $killedBy) : void{
		// TODO: Implement onDeath() method.
	}

	public function onGameOut(?MurderPlayer $killedBy = null) : void{
		if($killedBy !== null){
			if(!$killedBy->getRole() instanceof Bystander){
				throw new AssumptionFailedError("Murderer can only be killed a bystander");
			}
			$this->getMurderArena()->endGame(new MurderGotKilled($killedBy));
		}else{
			$aliveBystanders = $this->getMurderArena()->getAliveBystanders();
			if(count($aliveBystanders) === 1){
				$this->getMurderArena()->endGame(new MurderGotKilled($aliveBystanders[0]));
			}else{
				//TODO: message
				$newMurderer = $aliveBystanders[array_rand($aliveBystanders)];
				$newMurderer->getPlayer()->getInventory()->removeItem($newMurderer->getRoleNonNull()->getWeapon());
				$newMurderer->setRole(new Murderer($newMurderer));
				$newMurderer->getPlayer()->getInventory()->setItem(0, $newMurderer->getRoleNonNull()->getWeapon());
			}
		}
	}

	public function sendStartTitle() : void{
		$this->getPlayer()->sendTitle(TextFormat::BOLD . TextFormat::RED . $this->getName(), $this->getPlugin()->translateString("game.start.subtitle.murderer"));
	}

	public function getWeapon() : Item{
		return ItemFactory::get(ItemIds::WOODEN_SWORD)->setCustomName($this->getPlugin()->translateString("game.gun"));
	}

	public function hasWeaponFromStart() : bool{
		return true;
	}

	public function canSprint() : bool{
		return true;
	}

	public function getName() : string{
		return$this->getPlugin()->translateString("game.murderer");
	}
}