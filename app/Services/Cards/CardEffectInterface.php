<?php

namespace App\Services\Cards;

interface CardEffectInterface
{
    public function apply(array &$room, string $playerId, array $data): ?array;
}
