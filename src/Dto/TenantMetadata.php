<?php

declare(strict_types=1);

namespace Nektria\Dto;

use Nektria\Service\SharedTenantCache;

/**
 * @phpstan-import-type TenantMetadataArray from SharedTenantCache
 */
class TenantMetadata
{
    public const string ECHO_MODE_ALWAYS = 'always';

    public const string ECHO_MODE_DEFAULT = 'default';

    public const string GRID_MODE_COMMON = 'common';

    public const string GRID_MODE_DEFAULT = 'default';

    /** @var mixed[] */
    private array $data;

    /**
     * @param mixed[] $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function autoDuplicateLastWeek(): bool
    {
        $this->data['autoDuplicateLastWeek'] ??= true;

        return $this->data['autoDuplicateLastWeek'];
    }

    /** @return string[] */
    public function availableTags(): array
    {
        $this->data['availableTags'] ??= [];

        return $this->data['availableTags'];
    }

    public function blockWarehouse(): bool
    {
        $this->data['blockWarehouse'] ??= true;

        return $this->data['blockWarehouse'];
    }

    /**
     * @return string[]
     */
    public function capacitiesEmailRecipients(): array
    {
        $this->data['capacitiesEmailRecipients'] ??= [];

        return $this->data['capacitiesEmailRecipients'];
    }

    public function countComplementaryOrders(): bool
    {
        $this->data['countComplementaryOrders'] ??= true;

        return $this->data['countComplementaryOrders'];
    }

    public function data(): mixed
    {
        return $this->data;
    }

    public function dayOffExtendsCutoff(): bool
    {
        $this->data['dayOffExtendsCutoff'] ??= false;

        return $this->data['dayOffExtendsCutoff'];
    }

    public function deliveryTime(): ?int
    {
        $this->data['deliveryTime'] ??= null;

        return $this->data['deliveryTime'];
    }

    public function ecoMode(): string
    {
        $this->data['ecoMode'] ??= self::ECHO_MODE_DEFAULT;

        return $this->data['ecoMode'];
    }

    public function expressGridDisabled(): bool
    {
        $this->data['expressGridDisabled'] ??= false;

        return $this->data['expressGridDisabled'];
    }

    public function expressGridEnabled(): bool
    {
        return !$this->expressGridDisabled();
    }

    public function extendPickingShiftsDisabled(): bool
    {
        $this->data['extendPickingShiftsDisabled'] ??= true;

        return $this->data['extendPickingShiftsDisabled'];
    }

    public function extraLongSpeed(): int
    {
        $this->data['extraLongSpeed'] ??= 1440;

        return $this->data['extraLongSpeed'];
    }

    public function forceDriverAssignation(): bool
    {
        $this->data['forceDriverAssignation'] ??= false;

        return $this->data['forceDriverAssignation'];
    }

    public function forceTags(): bool
    {
        $this->data['forceTags'] ??= false;

        return $this->data['forceTags'];
    }

    public function freeSlotPriceForincentivizedShoppers(): bool
    {
        $this->data['freeSlotPriceForincentivizedShoppers'] ??= false;

        return $this->data['freeSlotPriceForincentivizedShoppers'];
    }

    public function getDiscordChannelFor(string $channel): ?string
    {
        return $this->data["{$channel}Channel"] ?? null;
    }

    public function gridMode(): string
    {
        $this->data['gridMode'] ??= self::GRID_MODE_DEFAULT;

        return $this->data['gridMode'];
    }

    public function gridVesion(): int
    {
        $this->data['gridVersion'] ??= 1;

        return $this->data['gridVersion'];
    }

    public function gridViewerOrdersPrefix(): string
    {
        $this->data['gridViewerOrdersPrefix'] ??= '';

        return $this->data['gridViewerOrdersPrefix'];
    }

    /**
     * @return string[]
     */
    public function ignoreRoutesOnLogsList(): array
    {
        $this->data['ignoreRoutesOnLogsList'] ??= [];

        return $this->data['ignoreRoutesOnLogsList'];
    }

    public function longSpeed(): int
    {
        $this->data['longSpeed'] ??= 720;

        return $this->data['longSpeed'];
    }

    public function nextStepEnabled(): bool
    {
        $this->data['nextStepEnabled'] = $this->data['nextStepEnabled'] ?? false;

        return $this->data['nextStepEnabled'];
    }

    public function parkingTime(): int
    {
        $this->data['parkingTime'] ??= 3;

        return $this->data['parkingTime'];
    }

    public function proxyHost(): ?string
    {
        $this->data['proxyHost'] ??= null;

        if ($this->data['proxyHost'] === '') {
            $this->data['proxyHost'] = null;
        }

        return $this->data['proxyHost'];
    }

    public function recoverCoords(): bool
    {
        $this->data['recoverCoords'] ??= false;

        return $this->data['recoverCoords'];
    }

    public function shortSpeed(): int
    {
        $this->data['shortSpeed'] ??= 240;

        return $this->data['shortSpeed'];
    }

    /**
     * @return string[]
     */
    public function specialClients(): array
    {
        $this->data['specialClients'] ??= [];

        return $this->data['specialClients'];
    }

    public function syncRMOrder(): bool
    {
        $this->data['syncRMOrder'] ??= false;

        return $this->data['syncRMOrder'];
    }

    public function syncRMShift(): bool
    {
        $this->data['syncRMShift'] ??= false;

        return $this->data['syncRMShift'];
    }

    public function syncRMWarehouse(): bool
    {
        $this->data['syncRMWarehouse'] ??= false;

        return $this->data['syncRMWarehouse'];
    }

    /**
     * @return TenantMetadataArray
     */
    public function toArray(): array
    {
        return [
            'autoDuplicateLastWeek' => $this->autoDuplicateLastWeek(),
            'availableTags' => $this->availableTags(),
            'blockWarehouse' => $this->blockWarehouse(),
            'countComplementaryOrders' => $this->countComplementaryOrders(),
            'dayOffExtendsCutoff' => $this->dayOffExtendsCutoff(),
            'deliveryTime' => $this->deliveryTime(),
            'ecoMode' => $this->ecoMode(),
            'expressGridDisabled' => $this->expressGridDisabled(),
            'extendPickingShiftsDisabled' => $this->extendPickingShiftsDisabled(),
            'extraLongSpeed' => $this->extraLongSpeed(),
            'forceDriverAssignation' => $this->forceDriverAssignation(),
            'forceTags' => $this->forceTags(),
            'freeSlotPriceForincentivizedShoppers' => $this->freeSlotPriceForincentivizedShoppers(),
            'gridMode' => $this->gridMode(),
            'gridVersion' => $this->gridVesion(),
            'gridViewerOrdersPrefix' => $this->gridViewerOrdersPrefix(),
            'ignoreRoutesOnLogsList' => $this->ignoreRoutesOnLogsList(),
            'longSpeed' => $this->longSpeed(),
            'nextStepEnabled' => $this->nextStepEnabled(),
            'parkingTime' => $this->parkingTime(),
            'proxyHost' => $this->proxyHost(),
            'recoverCoords' => $this->recoverCoords(),
            'shortSpeed' => $this->shortSpeed(),
            'syncRMOrder' => $this->syncRMOrder(),
            'syncRMShift' => $this->syncRMShift(),
            'syncRMWarehouse' => $this->syncRMWarehouse(),
            'useAddressInsteadOfShopperCode' => $this->useAddressInsteadOfShopperCode(),
        ];
    }

    public function useAddressInsteadOfShopperCode(): bool
    {
        $this->data['useAddressInsteadOfShopperCode'] ??= false;

        return $this->data['useAddressInsteadOfShopperCode'];
    }
}
