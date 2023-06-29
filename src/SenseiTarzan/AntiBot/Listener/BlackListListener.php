<?php

namespace SenseiTarzan\AntiBot\Listener;

use pocketmine\event\EventPriority;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ClientCacheBlobStatusPacket;
use pocketmine\network\mcpe\protocol\CraftingEventPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\ItemStackRequestPacket;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\Server;
use SenseiTarzan\ExtraEvent\Class\EventAttribute;

class BlackListListener
{

    const MAX_INVENTORY_TRANSACTION = 100;
    const MAX_BLOCK_ACTION = 100;
    const MAX_ITEM_INTERACTION = 100;
    const MAX_METADATA = 130;
    const MAX_TEXT_PARAMETERS = 100;
    const MAX_ITEM_STACK_REQUEST = 100; //NOT USE by PMMP4
    const MAX_CRAFTING_INPUT = 0;
    const MAX_CRAFTING_OUTPUT = 1;
    const MAX_HIT_HASHES = 0; //NOT USE by PMMP4
    const MAX_MISS_HASHES = 0; //NOT USE by PMMP4
    const MAX_ITEM_CAN_DESTROY = 0; //NOT USE by PMMP
    const MAX_ITEM_CAN_PLACE = 0; //NOT USE by PMMP

    #[EventAttribute(EventPriority::LOWEST)]
    public function onDataReceive(DataPacketReceiveEvent $event): void
    {
        $packet = $event->getPacket();
        if ($packet instanceof InventoryTransactionPacket) {
            if (count($packet->trData->getActions()) >= self::MAX_INVENTORY_TRANSACTION) {
                $this->blockEvent($event);
                return;
            }
            foreach ($packet->trData->getActions() as $action) {
                if (
                    count(($itemOldStack = $action->oldItem->getItemStack())->getCanDestroy()) > self::MAX_ITEM_CAN_DESTROY
                    || count(($itemNewStack = $action->newItem->getItemStack())->getCanDestroy()) > self::MAX_ITEM_CAN_DESTROY
                    || count($itemOldStack->getCanPlaceOn()) > self::MAX_ITEM_CAN_PLACE
                    || count($itemNewStack->getCanPlaceOn()) > self::MAX_ITEM_CAN_PLACE
                ) {
                    $this->blockEvent($event);
                    break;
                }
            }
        } else if ($packet instanceof PlayerAuthInputPacket) {
            if (($packet->getBlockActions() !== null && count($packet->getBlockActions()) >= self::MAX_BLOCK_ACTION) || ($packet->getItemInteractionData() !== null && count($packet->getItemInteractionData()->getRequestChangedSlots()) >= self::MAX_ITEM_INTERACTION)) {
                $this->blockEvent($event);
            }
        } else if ($packet instanceof SetActorDataPacket) {
            if (count($packet->metadata) >= self::MAX_METADATA) {
                $this->blockEvent($event);
            }
        } else if ($packet instanceof ItemStackRequestPacket) {
            if (count($packet->getRequests()) > self::MAX_ITEM_STACK_REQUEST) {
                $this->blockEvent($event);
            }
        } else if ($packet instanceof CraftingEventPacket) {
            if (count($packet->input) > self::MAX_CRAFTING_INPUT || count($packet->output) > self::MAX_CRAFTING_OUTPUT) {
                $this->blockEvent($event);
                return;
            }
            /**
             * @var $itemStackWrapper ItemStackWrapper
             */
            foreach (array_merge($packet->input, $packet->output) as $itemStackWrapper) {
                if (count(($itemStack = $itemStackWrapper->getItemStack())->getCanDestroy()) > self::MAX_ITEM_CAN_DESTROY || count($itemStack->getCanPlaceOn()) > self::MAX_ITEM_CAN_PLACE) {
                    $this->blockEvent($event);
                    break;
                }
            }
        } else if ($packet instanceof TextPacket) {
            if (count($packet->parameters) >= self::MAX_TEXT_PARAMETERS) {
                $this->blockEvent($event);
            }
        } else if ($packet instanceof ClientCacheBlobStatusPacket) {
            if (count($packet->getHitHashes()) > self::MAX_HIT_HASHES || count($packet->getMissHashes()) > self::MAX_MISS_HASHES) {
                $this->blockEvent($event);
            }
        } else if ($packet instanceof MobArmorEquipmentPacket) {
            foreach (["head", "chest", "legs", "feet"] as $slot) {
                if (count(($itemStack = $packet->{$slot}->getItemStack())->getCanDestroy()) > self::MAX_ITEM_CAN_DESTROY
                    || count($itemStack->getCanPlaceOn()) > self::MAX_ITEM_CAN_PLACE) {
                    $this->blockEvent($event);
                    break;
                }
            }
        }else if ($packet instanceof MobEquipmentPacket) {
            if (count(($itemStack = $packet->item->getItemStack())->getCanDestroy()) > self::MAX_ITEM_CAN_DESTROY
                || count($itemStack->getCanPlaceOn()) > self::MAX_ITEM_CAN_PLACE) {
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