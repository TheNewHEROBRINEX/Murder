<?php
declare(strict_types=1);

namespace TheNewHEROBRINE\Murder\entity;

use pocketmine\entity\Human;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use TheNewHEROBRINE\Murder\player\MurderPlayer;

class Corpse extends Human{
	public function __construct(Level $level, CompoundTag $nbt, MurderPlayer $murderPlayer = null){
		if($murderPlayer !== null){
			$this->setSkin($murderPlayer->getIdentityNonNull()->getSkin());
			parent::__construct($level, $nbt);
			$this->propertyManager->setBlockPos(self::DATA_PLAYER_BED_POSITION, $murderPlayer->getPlayer()->floor());
			$this->setPlayerFlag(self::DATA_PLAYER_FLAG_SLEEP, true);
		}else{
			$this->close();
		}
	}

	public function canSaveWithChunk() : bool{
		return false;
	}
}