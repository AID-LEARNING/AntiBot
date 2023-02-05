<?php

namespace SenseiTarzan\AntiBot\Listener;

use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\Server;
use SenseiTarzan\AntiBot\Component\BlackListManager;
use SenseiTarzan\ExtraEvent\Class\EventAttribute;

class BlackListListener
{
    #[EventAttribute(EventPriority::LOWEST)]
    public function onPreLogin(PlayerPreLoginEvent $event): void{
        if (BlackListManager::getInstance()->isBlackListed($event->getPlayerInfo()->getExtraData()['DeviceId'])){
            $event->setKickReason(PlayerPreLoginEvent::KICK_REASON_PLUGIN, "T'inquete pas mon reuf");
            $event->setAuthRequired(true);
        }
    }
    #[EventAttribute(EventPriority::LOWEST)]
    public function onDataReceive(DataPacketReceiveEvent $event): void{
        $networkSession = $event->getOrigin();
        $packet = $event->getPacket();
        if ($packet instanceof InventoryTransactionPacket){
            if (count($packet->trData->getActions()) > 100){
                $event->cancel();
                BlackListManager::getInstance()->setBlackList($networkSession->getPlayerInfo()->getExtraData()['DeviceId']);
                (function(){
                    $this->actions = [];
                })->call($packet->trData);
                $event->cancel();
                (function(){
                    $this->requestChangedSlots = [];
                })->call($packet);
                Server::getInstance()->getNetwork()->blockAddress($networkSession->getIp(),3.154e+7);
            }
        }
        if ($packet instanceof PlayerAuthInputPacket){
            if ($packet->getBlockActions() !== null && count($packet->getBlockActions()) > 100){
                $event->cancel();
                BlackListManager::getInstance()->setBlackList($networkSession->getPlayerInfo()->getExtraData()['DeviceId']);
                (function(){
                    $this->blockActions = [];
                })->call($packet);
                Server::getInstance()->getNetwork()->blockAddress($networkSession->getIp(),3.154e+7);
            }
            if ($packet->getItemInteractionData() !== null && count($packet->getItemInteractionData()->getRequestChangedSlots()) > 100){
                $event->cancel();
                BlackListManager::getInstance()->setBlackList($networkSession->getPlayerInfo()->getExtraData()['DeviceId']);
                (function(){
                    $this->itemInteractionData = null;
                })->call($packet);
                Server::getInstance()->getNetwork()->blockAddress($networkSession->getIp(),3.154e+7);
            }
        }
    }
}