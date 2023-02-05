<?php

namespace SenseiTarzan\AntiBot\Component;

use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use SenseiTarzan\AntiBot\Main;
use Symfony\Component\Filesystem\Path;

class BlackListManager
{

    use SingletonTrait;

    private Config $dataBase;

    public function __construct(Main $main)
    {
        self::setInstance($this);
        $this->dataBase = new Config(Path::join($main->getDataFolder(), "data.json"), Config::JSON);
    }

    public function isBlackListed(string $deviceId): bool
    {
        return $this->dataBase->exists($deviceId);
    }

    public function setBlackList(string $deviceId): void
    {
        $this->dataBase->set($deviceId, "");
        $this->dataBase->save();
    }

}