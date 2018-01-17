<?php
declare(strict_types=1);

namespace TheNewHEROBRINE\Murder\entity\projectile;

use pocketmine\entity\projectile\Arrow;
use pocketmine\level\Level;
use TheNewHEROBRINE\Murder\particle\MobSpellParticle;

class MurderGunProjectile extends Arrow {

    /**
     * @return string
     */
    public function getName(): string {
        return "MurderGunProjectile";
    }

    /**
     * @param int $tickDiff
     * @return bool
     */
    public function entityBaseTick(int $tickDiff = 1): bool {
        if ($this->closed) {
            return false;
        }

        $hasUpdate = parent::entityBaseTick($tickDiff);

        if ($this->age > 200 or $this->getOwningEntity() == null or $this->hadCollision) {
            $this->flagForDespawn();
            return true;
        } elseif ($this->level instanceof Level) {
            $this->level->addParticle(new MobSpellParticle($this));
        }

        return $hasUpdate;
    }
}