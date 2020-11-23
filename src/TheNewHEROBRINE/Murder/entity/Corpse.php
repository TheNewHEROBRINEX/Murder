<?php
declare(strict_types=1);

namespace TheNewHEROBRINE\Murder\entity;

use pocketmine\entity\Human;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;

class Corpse extends Human{
	public function __construct(Level $level, CompoundTag $nbt, Human $deadHuman = null){
		if($deadHuman instanceof Human){
			$this->setSkin($deadHuman->getSkin());
			parent::__construct($level, $nbt);
			$this->getInventory()->setItemInHand($deadHuman->getInventory()->getItemInHand());
			$this->propertyManager->setBlockPos(self::DATA_PLAYER_BED_POSITION, $deadHuman->floor());
			$this->setPlayerFlag(self::DATA_PLAYER_FLAG_SLEEP, true);
		}else{
			$this->close();
		}
	}

	public function canSaveWithChunk() : bool{
		return false;
	}
}