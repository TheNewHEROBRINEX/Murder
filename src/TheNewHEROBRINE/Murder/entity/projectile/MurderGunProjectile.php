<?php
declare(strict_types=1);

namespace TheNewHEROBRINE\Murder\entity\projectile;

use pocketmine\entity\projectile\Arrow;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\level\Level;
use pocketmine\level\particle\InstantEnchantParticle;
use pocketmine\utils\Color;

class MurderGunProjectile extends Arrow{

	/** @var int */
	protected $gravity = 0;

	/** @var int */
	protected $drag = 0;

	public function entityBaseTick(int $tickDiff = 1) : bool{
		if($this->closed){
			return false;
		}

		$hasUpdate = parent::entityBaseTick($tickDiff);

		if($this->ticksLived > 200 or $this->getOwningEntity() === null){
			$this->flagForDespawn();
			return true;
		}elseif($this->level instanceof Level){
			$this->level->addParticle(new InstantEnchantParticle($this, new Color(0xff, 0xff, 0xff)));
		}
		return $hasUpdate;
	}

	public function onHit(ProjectileHitEvent $event) : void{
		$this->flagForDespawn();
	}
}