<?php

declare(strict_types=1);

namespace ShipMonk\SortedLinkedList;

/**
 * @template T of int|string
 * @internal
 */
final class Node
{
    public function __construct(
        /** @var T */
        public int|string $value,
        /** @var Node<T>|null */
        public ?Node $next = null,
    ) {
    }
}
