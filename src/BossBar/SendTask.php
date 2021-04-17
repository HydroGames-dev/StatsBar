<?php

namespace BossBar;

use pocketmine\plugin\Plugin;
use pocketmine\scheduler\ClosureTask;
use pocketmine\event\HandlerList;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\scheduler\TaskHandler;
use pocketmine\scheduler\Task;
use pocketmine\{Player, Server};
use BossBar\Main;
use BossBar\API;

class SendTask extends Task{

    private $plugin;

	public function __construct(Main $plugin){
        $this->plugin = $plugin;
		$this->server = $plugin->getServer();
    }

	public function getPlugin() : Plugin {
			return $this->plugin;
		}
	
		public function getServer(){
			return $this->server;
		}

	public function onRun(int $currentTick){
		$this->plugin->sendBossBar();
	}

	public function cancel(){
		$this->getHandler()->cancel();
	}
}