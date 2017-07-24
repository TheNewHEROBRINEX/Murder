<?php

namespace TheNewHEROBRINE\Murder\entities\projectiles;

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
     * @param $currentTick
     * @return bool
     */
    public function onUpdate($currentTick): bool{
        if ($this->closed){
            return false;
        }

        $this->timings->startTiming();

        $hasUpdate = parent::onUpdate($currentTick);

        if ($this->age > 30 * 20 or $this->getOwningEntity() == null or $this->hadCollision){
            $this->kill();
            $hasUpdate = true;
        }
        else{
            for ($i = 0; $i < 20; $i++) {
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
    public function spawnTo(Player $player) {
        parent::spawnTo($player);
        //invisible, only particles
    }
}