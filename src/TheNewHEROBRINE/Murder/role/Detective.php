<?php
declare(strict_types=1);

namespace TheNewHEROBRINE\Murder\role;

use pocketmine\utils\TextFormat;

class Detective extends Bystander{

	public function sendStartTitle() : void{
		$this->getPlayer()->sendTitle(TextFormat::BOLD . TextFormat::AQUA . $this->getName(), $this->getPlugin()->translateString("game.start.subtitle.detective"));
	}

	public function hasWeaponFromStart() : bool{
		return true;
	}
}