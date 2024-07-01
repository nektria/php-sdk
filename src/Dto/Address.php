<?php

declare(strict_types=1);

namespace Nektria\Dto;

use Nektria\Util\StringUtil;
use Symfony\Component\String\Slugger\AsciiSlugger;

readonly class Address
{
    public function __construct(
        public string $addressLine1,
        public string $addressLine2,
        public bool $elevator,
        public string $postalCode,
        public string $city,
        public string $countryCode,
        public float $latitude,
        public float $longitude,
    ) {
    }

    public function slug(): string
    {
        if ($this->addressLine1 === '') {
            return '';
        }

        $slugger = new AsciiSlugger();

        return strtolower($slugger->slug(
            StringUtil::trim(
                "{$this->addressLine1} {$this->addressLine2} {$this->postalCode} {$this->city} {$this->countryCode}",
            ),
        )->toString());
    }

    /**
     * @return array{
     *     addressLine1: string,
     *     addressLine2: string,
     *     elevator: bool,
     *     postalCode: string,
     *     city: string,
     *     countryCode: string,
     *     latitude: float,
     *     longitude: float,
     * }
     */
    public function toArray(): array
    {
        return [
            'addressLine1' => $this->addressLine1,
            'addressLine2' => $this->addressLine2,
            'elevator' => $this->elevator,
            'postalCode' => $this->postalCode,
            'city' => $this->city,
            'countryCode' => $this->countryCode,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
