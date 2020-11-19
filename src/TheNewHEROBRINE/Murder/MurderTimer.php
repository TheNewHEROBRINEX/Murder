<?php
declare(strict_types=1);

namespace TheNewHEROBRINE\Murder;

use pocketmine\scheduler\Task;

class MurderTimer extends Task{

	/** @var MurderMain $plugin */
	private $plugin;

	public function __construct(MurderMain $plugin){
		$this->plugin = $plugin;
	}

	public function onRun(int $tick) : void{
		if($this->plugin instanceof MurderMain){
			foreach($this->plugin->getArenas() as $arena){
				$arena->tick();
			}
		}
	}
}