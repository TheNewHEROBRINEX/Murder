<?php

namespace TheNewHEROBRINE\Murder\entity\projectile;

use pocketmine\Player;
use TheNewHEROBRINE\Murder\particle\MobSpellParticle;

class MurderGunProjectile extends MurderProjectile {

    /**
     * @return string
     */
    public function getName(): string{
        return "MurderGunProjectile";
    }

    /**
     * @param int $currentTick
     * @return bool
     */
    public function onUpdate(int $currentTick): bool{
        if ($this->closed){
            return false;
        }

        $this->timings->startTiming();

        $hasUpdate = parent::onUpdate($currentTick);

        if ($this->age > 30 * 20 or $this->getOwningEntity() == null or $this->hadCollision){
            $this->flagForDespawn();
            $hasUpdate = true;
        }
        else{
            for ($i = 0; $i < 30; $i++) {
                $this->level->addParticle(new MobSpellParticle($this->add(
                    $this->width / 2 + mt_rand(-100, 100) / 500,
                    $this->height / 2 + mt_rand(-100, 100) / 500,
                    $this->width / 2 + mt_rand(-100, 100) / 500),
                    mt_rand(0, 255),
                    mt_rand(0, 255),
                    mt_rand(0, 255)));
            }
        }

        $this->timings->startTiming();

        return $hasUpdate;
    }

    /**
     * @param Player $player
     */
    public function sendSpawnPacket(Player $player): void {
        //invisible, only particles
    }
}