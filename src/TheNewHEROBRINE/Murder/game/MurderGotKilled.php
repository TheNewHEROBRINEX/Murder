<?php
declare(strict_types=1);

namespace TheNewHEROBRINE\Murder\game;

use pocketmine\utils\TextFormat;
use TheNewHEROBRINE\Murder\player\MurderPlayer;

class MurderGotKilled implements BystandersWinCause{
	private MurderPlayer $killedBy;

	public function __construct(MurderPlayer $killedBy){
		$this->killedBy = $killedBy; //PHP8: promotion
	}

	public function getMessage() : string{ //TODO: translation
		return TextFormat::BOLD . TextFormat::GREEN . "The murderer has been killed by " . $this->killedBy->getIdentityNonNull()->getUsername() . " " . TextFormat::BOLD . TextFormat::GREEN . "!";
	}
}