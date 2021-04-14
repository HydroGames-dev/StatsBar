<?php

namespace BossBar;

use BossBar\Main;
use BossBar\API;
use pocketmine\entity\Attribute;
use pocketmine\entity\AttributeMap;
use pocketmine\entity\DataPropertyManager;
use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\network\mcpe\protocol\RemoveEntityPacket;
use pocketmine\network\mcpe\protocol\SetEntityDataPacket;
use pocketmine\network\mcpe\protocol\UpdateAttributesPacket;
use pocketmine\event\server\DataPacketReceiveEvent;

class BossBarValues extends Attribute{

	public $min, $max, $value, $name;

	public function __construct($min, $max, $value, $name){
		$this->min = $min;
		$this->max = $max;
		$this->value = $value;
		$this->name = $name;
	}

	public function getMinValue(): float{
		return $this->min;
	}

	public function getMaxValue(): float{
		return $this->max;
	}

	public function getValue(): float{
		return $this->value;
	}

	public function getName(): string{
		return $this->name;
	}

	public function getDefaultValue(): float{
		return $this->min;
	}
}