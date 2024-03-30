<?php

declare(strict_types=1);

namespace Nektria\Dto;

use Nektria\Service\SharedTenantCache;

/**
 * @phpstan-import-type WarehouseMetadataArray from SharedTenantCache
 */
class TenantMetadata
{
    public const GRID_MODE_DEFAULT = 'default';

    public const GRID_MODE_COMMON = 'common';

    public const ECHO_MODE_ALWAYS = 'always';

    public const ECHO_MODE_DEFAULT = 'default';

    /** @var mixed[] */
    private array $data;

    /**
     * @param mixed[] $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->autoDuplicateLastWeek();
        $this->availableTags();
        $this->blockWarehouse();
        $this->dayOffExtendsCutoff();
        $this->deliveryTime();
        $this->ecoMode();
        $this->expressGridDisabled();
        $this->extendPickingShiftsDisabled();
        $this->extraLongSpeed();
        $this->forceDriverAssignation();
        $this->forceTags();
        $this->gridMode();
        $this->gridVesion();
        $this->gridViewerOrdersPrefix();
        $this->ignoreRoutesOnLogsList();
        $this->importOrdersFromFileByProxy();
        $this->longSpeed();
        $this->nextStepEnabled();
        $this->parkingTime();
        $this->proxyHost();
        $this->proxyToken();
        $this->recoverCoords();
        $this->sendNewOrderToProxy();
        $this->sendRoutesAtPickingShiftClosesAt();
        $this->sendRoutesByProxy();
        $this->shortSpeed();
        $this->syncRMOrder();
        $this->syncRMShift();
        $this->syncRMWarehouse();
        $this->useAddressInsteadOfShopperCode();
        $this->configurationsChannel();
    }

    public function ecoMode(): string
    {
        $this->data['ecoMode'] ??= self::ECHO_MODE_DEFAULT;

        return $this->data['ecoMode'];
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

    public function deliveryTime(): ?int
    {
        $this->data['deliveryTime'] ??= null;

        return $this->data['deliveryTime'];
    }

    public function parkingTime(): int
    {
        $this->data['parkingTime'] ??= 3;

        return $this->data['parkingTime'];
    }

    public function autoDuplicateLastWeek(): bool
    {
        $this->data['autoDuplicateLastWeek'] ??= true;

        return $this->data['autoDuplicateLastWeek'];
    }

    public function blockWarehouse(): bool
    {
        $this->data['blockWarehouse'] ??= true;

        return $this->data['blockWarehouse'];
    }

    public function longSpeed(): int
    {
        $this->data['longSpeed'] ??= 720;

        return $this->data['longSpeed'];
    }

    public function extraLongSpeed(): int
    {
        $this->data['extraLongSpeed'] ??= 1440;

        return $this->data['extraLongSpeed'];
    }

    public function shortSpeed(): int
    {
        $this->data['shortSpeed'] ??= 240;

        return $this->data['shortSpeed'];
    }

    /** @return string[] */
    public function availableTags(): array
    {
        $this->data['availableTags'] ??= [];

        return $this->data['availableTags'];
    }

    public function recoverCoords(): bool
    {
        $this->data['recoverCoords'] ??= false;

        return $this->data['recoverCoords'];
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

    public function forceTags(): bool
    {
        $this->data['forceTags'] ??= false;

        return $this->data['forceTags'];
    }

    public function gridViewerOrdersPrefix(): string
    {
        $this->data['gridViewerOrdersPrefix'] ??= '';

        return $this->data['gridViewerOrdersPrefix'];
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

    public function forceDriverAssignation(): bool
    {
        $this->data['forceDriverAssignation'] ??= false;

        return $this->data['forceDriverAssignation'];
    }

    public function nextStepEnabled(): bool
    {
        $this->data['nextStepEnabled'] = $this->data['nextStepEnabled'] ?? false;

        return $this->data['nextStepEnabled'];
    }

    public function dayOffExtendsCutoff(): bool
    {
        $this->data['dayOffExtendsCutoff'] ??= false;

        return $this->data['dayOffExtendsCutoff'];
    }

    public function useAddressInsteadOfShopperCode(): bool
    {
        $this->data['useAddressInsteadOfShopperCode'] ??= false;

        return $this->data['useAddressInsteadOfShopperCode'];
    }

    public function proxyHost(): string
    {
        $this->data['proxyHost'] ??= '';

        return $this->data['proxyHost'];
    }

    public function proxyToken(): string
    {
        $this->data['proxyToken'] ??= '';

        return $this->data['proxyToken'];
    }

    public function sendRoutesByProxy(): bool
    {
        $this->data['sendRoutesByProxy'] ??= false;

        return $this->data['sendRoutesByProxy'];
    }

    public function importOrdersFromFileByProxy(): bool
    {
        $this->data['importOrdersFromFileByProxy'] ??= false;

        return $this->data['importOrdersFromFileByProxy'];
    }

    /**
     * @return string[]
     */
    public function ignoreRoutesOnLogsList(): array
    {
        $this->data['ignoreRoutesOnLogsList'] ??= [];

        return $this->data['ignoreRoutesOnLogsList'];
    }

    public function sendNewOrderToProxy(): bool
    {
        $this->data['sendNewOrderToProxy'] ??= false;

        return $this->data['sendNewOrderToProxy'];
    }

    public function extendPickingShiftsDisabled(): bool
    {
        $this->data['extendPickingShiftsDisabled'] ??= true;

        return $this->data['extendPickingShiftsDisabled'];
    }

    public function sendRoutesAtPickingShiftClosesAt(): bool
    {
        $this->data['sendRoutesAtPickingShiftClosesAt'] ??= true;

        return $this->data['sendRoutesAtPickingShiftClosesAt'];
    }

    public function configurationsChannel(): ?string
    {
        $this->data['configurationsChannel'] ??= null;

        return $this->data['configurationsChannel'];
    }

    /**
     * @return WarehouseMetadataArray
     */
    public function toArray(): array
    {
        return [
            'autoDuplicateLastWeek' => $this->autoDuplicateLastWeek(),
            'availableTags' => $this->availableTags(),
            'blockWarehouse' => $this->blockWarehouse(),
            'dayOffExtendsCutoff' => $this->dayOffExtendsCutoff(),
            'deliveryTime' => $this->deliveryTime(),
            'ecoMode' => $this->ecoMode(),
            'expressGridDisabled' => $this->expressGridDisabled(),
            'extendPickingShiftsDisabled' => $this->extendPickingShiftsDisabled(),
            'extraLongSpeed' => $this->extraLongSpeed(),
            'forceDriverAssignation' => $this->forceDriverAssignation(),
            'forceTags' => $this->forceTags(),
            'gridMode' => $this->gridMode(),
            'gridVersion' => $this->gridVesion(),
            'gridViewerOrdersPrefix' => $this->gridViewerOrdersPrefix(),
            'ignoreRoutesOnLogsList' => $this->ignoreRoutesOnLogsList(),
            'importOrdersFromFileByProxy' => $this->importOrdersFromFileByProxy(),
            'longSpeed' => $this->longSpeed(),
            'nextStepEnabled' => $this->nextStepEnabled(),
            'parkingTime' => $this->parkingTime(),
            'proxyHost' => $this->proxyHost(),
            'proxyToken' => $this->proxyToken(),
            'recoverCoords' => $this->recoverCoords(),
            'sendNewOrderToProxy' => $this->sendNewOrderToProxy(),
            'sendRoutesAtPickingShiftClosesAt' => $this->sendRoutesAtPickingShiftClosesAt(),
            'sendRoutesByProxy' => $this->sendRoutesByProxy(),
            'shortSpeed' => $this->shortSpeed(),
            'syncRMOrder' => $this->syncRMOrder(),
            'syncRMShift' => $this->syncRMShift(),
            'syncRMWarehouse' => $this->syncRMWarehouse(),
            'useAddressInsteadOfShopperCode' => $this->useAddressInsteadOfShopperCode(),
            'configurationsChannel' => $this->configurationsChannel(),
        ];
    }
}
