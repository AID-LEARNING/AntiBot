<?php

namespace SenseiTarzan\AntiBot\Listener;

use pocketmine\event\EventPriority;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\Server;
use SenseiTarzan\ExtraEvent\Class\EventAttribute;

class BlackListListener
{
    #[EventAttribute(EventPriority::LOWEST)]
    public function onDataReceive(DataPacketReceiveEvent $event): void{
        $networkSession = $event->getOrigin();
        $packet = $event->getPacket();
        if ($packet instanceof InventoryTransactionPacket){
            if (count($packet->trData->getActions()) > 100){
                $event->cancel();
                Server::getInstance()->getNetwork()->blockAddress($networkSession->getIp(),3.154e+7 * 5);
            }
        }
        if ($packet instanceof PlayerAuthInputPacket){
            if ($packet->getBlockActions() !== null && count($packet->getBlockActions()) > 100){
                $event->cancel();
                Server::getInstance()->getNetwork()->blockAddress($networkSession->getIp(),3.154e+7 * 5);
            }
            if ($packet->getItemInteractionData() !== null && count($packet->getItemInteractionData()->getRequestChangedSlots()) > 100){
                $event->cancel();
                Server::getInstance()->getNetwork()->blockAddress($networkSession->getIp(),3.154e+7 * 5);
            }
        }
    }
}