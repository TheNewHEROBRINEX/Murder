<?php

namespace TheNewHEROBRINE\Murder;

use pocketmine\entity\Arrow;
use pocketmine\level\format\Chunk;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\protocol\AddItemEntityPacket;
use pocketmine\Player;

class MurderKnifeProjectile extends Arrow {

    public $width = 0.5;
    public $length = 0.5;
    public $height = 0.5;

    protected $gravity = 0;
    protected $drag = 0;
    
    protected $knife;

    public function __construct(Chunk $chunk, CompoundTag $nbt, Player $murderer) {
        $this->knife = $murderer->getInventory()->getItemInHand();
        parent::__construct($chunk, $nbt, $murderer);
    }

    public function spawnTo(Player $player){
        $pk = new AddItemEntityPacket();
        $pk->eid = $this->getId();
        $pk->x = $this->x;
        $pk->y = $this->y;
        $pk->z = $this->z;
        $pk->speedX = $this->motionX;
        $pk->speedY = $this->motionY;
        $pk->speedZ = $this->motionZ;
        $pk->item = $this->knife;
        $player->dataPacket($pk);

        $this->sendData($player);

        parent::spawnTo($player);
    }
}