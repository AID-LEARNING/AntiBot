<?php

namespace SenseiTarzan\AntiBot\Listener;

use pocketmine\event\EventPriority;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\Server;
use SenseiTarzan\ExtraEvent\Class\EventAttribute;

class BlackListListener
{

    const MAX_INVENTORY_TRANSACTION = 100;
    const MAX_BLOCK_ACTION = 100;
    const MAX_ITEM_INTERACTION = 100;
    const MAX_METADATA = 130;
    const MAX_TEXT_PARAMETERS = 100;
    #[EventAttribute(EventPriority::LOWEST)]
    public function onDataReceive(DataPacketReceiveEvent $event): void{
        $packet = $event->getPacket();
        if ($packet instanceof InventoryTransactionPacket){
            if (count($packet->trData->getActions()) > self::MAX_INVENTORY_TRANSACTION){
                $this->blockEvent($event);
            }
        }
        if ($packet instanceof PlayerAuthInputPacket){
            if (($packet->getBlockActions() !== null && count($packet->getBlockActions()) > self::MAX_BLOCK_ACTION) || ($packet->getItemInteractionData() !== null && count($packet->getItemInteractionData()->getRequestChangedSlots()) > self::MAX_ITEM_INTERACTION)){
                $this->blockEvent($event);
            }
        }

        if ($packet instanceof SetActorDataPacket){
            if (count($packet->metadata) > self::MAX_METADATA){
                $this->blockEvent($event);
            }
        }

        if ($packet instanceof TextPacket){
            if (count($packet->parameters) > self::MAX_TEXT_PARAMETERS){
                $this->blockEvent($event);
            }
        }
    }

    public function blockEvent(DataPacketReceiveEvent $event): void
    {
        $networkSession = $event->getOrigin();
        $event->cancel();
        Server::getInstance()->getNetwork()->blockAddress($networkSession->getIp(), PHP_INT_MAX);
    }
}