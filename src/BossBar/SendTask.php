<?php

namespace BossBar;

use pocketmine\plugin\Plugin;
use pocketmine\scheduler\PluginTask;

class SendTask extends PluginTask{

	public function __construct(Plugin $owner){
		parent::__construct($owner);
	}

	public function onRun(int $currentTick){
		$this->getOwner()->sendBossBar();
	}

	public function cancel(){
		$this->getHandler()->cancel();
	}
}