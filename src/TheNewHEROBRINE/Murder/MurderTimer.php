<?php
declare(strict_types=1);

namespace TheNewHEROBRINE\Murder;

use pocketmine\scheduler\Task;

class MurderTimer extends Task{
	private MurderMain $plugin;

	public function __construct(MurderMain $plugin){
		$this->plugin = $plugin;
	}

	public function onRun(int $currentTick) : void{
		if($this->plugin->isEnabled()){
			foreach($this->plugin->getArenas() as $arena){
				$arena->tick();
			}
		}
	}
}