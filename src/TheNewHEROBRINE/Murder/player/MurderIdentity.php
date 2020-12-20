<?php
declare(strict_types=1);

namespace TheNewHEROBRINE\Murder\player;

use pocketmine\entity\Skin;

class MurderIdentity{
	private string $username;
	private Skin $skin;

	public function __construct(
		string $username,
		Skin $skin
	){
		$this->skin = $skin;
		$this->username = $username; //PHP8: promotion
	}

	public function getUsername() : string{
		return $this->username;
	}

	public function getSkin() : Skin{
		return $this->skin;
	}
}