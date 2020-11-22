<?php
declare(strict_types=1);

namespace TheNewHEROBRINE\Murder\entity\projectile;

use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AddItemActorPacket;
use pocketmine\Player;

class MurderKnifeProjectile extends Projectile{

	/** @var float */
	public $width = 0.25;

	/** @var float */
	public $height = 0.25;

	/** @var float */
	public $baseOffset = 0.125;

	/** @var float */
	protected $gravity = 0;

	/** @var float */
	protected $drag = 0;

	/** @var Item */
	protected $knife;

	public function canCollideWith(Entity $entity) : bool{
		return $entity instanceof Player and !$this->onGround;
	}

	public function __construct(Level $level, CompoundTag $nbt, Player $murderer = null){
		if($murderer !== null){
			parent::__construct($level, $nbt, $murderer);
			$this->knife = $murderer->getInventory()->getItemInHand();
		}else{
			$this->close();
		}
	}

	public function entityBaseTick(int $tickDiff = 1) : bool{
		if($this->closed){
			return false;
		}

		$hasUpdate = parent::entityBaseTick($tickDiff);

		if($this->getOwningEntity() === null){
			$this->flagForDespawn();
			return true;
		}

		return $hasUpdate;
	}

	public function onHit(ProjectileHitEvent $event) : void{
		$this->flagForDespawn();
		$this->getLevel()->dropItem($this, $this->knife, new Vector3(0, 0, 0));
	}

	protected function sendSpawnPacket(Player $player) : void{
		$pk = new AddItemActorPacket();
		$pk->entityRuntimeId = $this->getId();
		$pk->position = $this->asVector3();
		$pk->motion = $this->getMotion();
		$pk->item = $this->knife;
		$pk->metadata = $this->getDataPropertyManager()->getAll();
		$player->dataPacket($pk);
	}
}