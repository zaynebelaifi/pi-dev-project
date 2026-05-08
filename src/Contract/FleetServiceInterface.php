<?php

namespace App\Contract;

interface FleetServiceInterface
{
    /**
     * Returns a summary of the current fleet state.
     * 
     * @return array{drivers: array, cars: array, active: int}
     */
    public function getFleetStatus(): array;
}
