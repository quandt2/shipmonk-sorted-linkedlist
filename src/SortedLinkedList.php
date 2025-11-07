<?php

declare(strict_types=1);

namespace ShipMonk\SortedLinkedList;

use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;
use InvalidArgumentException;

/**
 * Sorted linked list that accepts either only ints or only strings.
 * Inserts elements into their sorted position (ascending).
 * @template T of int|string
 * @implements IteratorAggregate<int, T>
 */
final class SortedLinkedList implements IteratorAggregate, Countable, JsonSerializable
{
    /** @var Node<T>|null */
    private ?Node $head = null;
    /** @var Node<T>|null */
    private ?Node $tail = null;
    private int $count = 0;

    /** @var 'int'|'string'|null */
    private ?string $type = null;

    /**
     * @param 'int'|'string'|null $type Force the list type; if null it will be inferred from first add().
     */
    public function __construct(?string $type = null)
    {
        if ($type !== null && $type !== 'int' && $type !== 'string') {
            throw new InvalidArgumentException('Type must be "int" or "string" or null.');
        }
        $this->type = $type;
    }

    /**
     * Create a list constrained to integers.
     * @return SortedLinkedList<int|string>
     */
    public static function ofInt(): self
    {
        $list = new self('int');
        return $list;
    }

    /**
     * Create a list constrained to strings.
     * @return SortedLinkedList<int|string>
     */
    public static function ofString(): self
    {
        $list = new self('string');
        return $list;
    }

    /**
     * Create a list from given values. Type must be consistent (int or string).
     * If $type is null, it will be inferred from the first element (if any).
     *
     * @template U of int|string
     * @param list<U> $values
     * @param 'int'|'string'|null $type
     * @return self
     * @phpstan-return SortedLinkedList<U>
     */
    public static function fromArray(array $values, ?string $type = null): self
    {
        $list = new self($type);
        foreach ($values as $v) {
            if (!is_int($v) && !is_string($v)) {
                throw new InvalidArgumentException('Values must be int or string.');
            }
            $list->add($v);
        }
        /** @phpstan-var SortedLinkedList<U> $list */
        return $list;
    }

    /**
     * Add many values (in any order).
     *
     * @param iterable<T> $values
     */
    public function addAll(iterable $values): void
    {
        foreach ($values as $v) {
            /** @var T $v */
            if (!is_int($v) && !is_string($v)) {
                throw new InvalidArgumentException('Values must be int or string.');
            }
            $this->add($v);
        }
    }

    /** Add a value in correct sorted position. Duplicates are allowed.
     * @param T $value
     */
    public function add(int|string $value): void
    {
        $this->ensureType($value);
        $new = new Node($value);
        /** @var Node<T> $new */

        if ($this->head === null) {
            $this->head = $new;
            $this->tail = $new;
            $this->count++;
            return;
        }

        // Insert at head if new value <= head (duplicates go before equals)
        if ($this->compare($value, $this->head->value) <= 0) {
            $new->next = $this->head;
            $this->head = $new;
            $this->count++;
            return;
        }

        // Walk until we find insertion spot
        $cur = $this->head;
        while ($cur->next !== null && $this->compare($cur->next->value, $value) < 0) {
            $cur = $cur->next;
        }

        $new->next = $cur->next;
        $cur->next = $new;
        if ($new->next === null) {
            // inserted at the end
            $this->tail = $new;
        }
        $this->count++;
    }

    /** Remove the first occurrence of value. Returns true if removed.
     * @param T $value
     */
    public function remove(int|string $value): bool
    {
        if ($this->head === null) {
            return false;
        }

        if ($this->head->value === $value) {
            // removing head; if it was also tail, update tail
            if ($this->tail === $this->head) {
                $this->tail = $this->head->next; // will be null
            }
            $this->head = $this->head->next;
            $this->count--;
            if ($this->count === 0) {
                $this->tail = null;
            }
            return true;
        }

        $cur = $this->head;
        while ($cur->next !== null && $cur->next->value !== $value) {
            // early exit if next is already greater (sorted list)
            if ($this->compare($cur->next->value, $value) > 0) {
                return false;
            }
            $cur = $cur->next;
        }

        if ($cur->next === null) {
            return false;
        }

        // if removing the tail, update tail to current
        if ($cur->next === $this->tail) {
            $this->tail = $cur;
        }
        $cur->next = $cur->next->next;
        $this->count--;
        return true;
    }

    /** @return list<T> */
    public function toArray(): array
    {
        $out = [];
        foreach ($this as $v) {
            $out[] = $v;
        }
        return $out;
    }

    /** @param T $value */
    public function contains(int|string $value): bool
    {
        $cur = $this->head;
        while ($cur !== null) {
            if ($cur->value === $value) {
                return true;
            }
            if ($this->compare($cur->value, $value) > 0) {
                return false; // we passed where it would be
            }
            $cur = $cur->next;
        }
        return false;
    }

    /** @return T|null */
    public function first(): int|string|null
    {
        return $this->head?->value;
    }

    /** @return T|null */
    public function last(): int|string|null
    {
        return $this->tail?->value;
    }

    public function clear(): void
    {
        $this->head = null;
        $this->tail = null;
        $this->count = 0;
    }

    /** @return int */
    public function count(): int
    {
        return $this->count;
    }

    /** @return Traversable<int, T> */
    public function getIterator(): Traversable
    {
        $cur = $this->head;
        while ($cur !== null) {
            yield $cur->value;
            $cur = $cur->next;
        }
    }

    /** Whether the list has no elements. */
    public function isEmpty(): bool
    {
        return $this->count === 0;
    }

    /** Alias for count() for readability. */
    public function size(): int
    {
        return $this->count;
    }

    /** @return list<T> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Remove all occurrences of a value. Returns the number of removed nodes.
     *
     * Note: For performance and ergonomics, this method does not enforce type via ensureType().
     * Passing a value of a different type will simply remove nothing.
     *
     * @param T $value
     */
    public function removeAll(int|string $value): int
    {
        if ($this->head === null) {
            return 0;
        }

        $removed = 0;

        // Remove matching head nodes
        while ($this->head !== null && $this->head->value === $value) {
            if ($this->tail === $this->head) {
                $this->tail = $this->head->next; // will be null
            }
            $this->head = $this->head->next;
            $this->count--;
            $removed++;
        }

        if ($this->head === null) {
            // list is now empty
            $this->tail = null;
            return $removed;
        }

        // Walk list and remove subsequent matches
        $cur = $this->head;
        while ($cur->next !== null) {
            if ($cur->next->value === $value) {
                if ($cur->next === $this->tail) {
                    $this->tail = $cur;
                }
                $cur->next = $cur->next->next;
                $this->count--;
                $removed++;
                continue; // check next after removal without advancing
            }
            if ($this->compare($cur->next->value, $value) > 0) {
                // next values are already greater; no more matches possible
                break;
            }
            $cur = $cur->next;
        }

        return $removed;
    }

    /** @return T|null */
    public function min(): int|string|null
    {
        return $this->first();
    }

    /** @return T|null */
    public function max(): int|string|null
    {
        return $this->last();
    }

    /**
     * Check structural equality: same type constraint and same ordered sequence of values.
     *
     * @template U of int|string
     * @param SortedLinkedList<U> $other
     */
    public function equals(SortedLinkedList $other): bool
    {
        if ($this === $other) {
            return true;
        }
        if ($this->count !== $other->count) {
            return false;
        }
        if ($this->type !== $other->type) {
            return false;
        }
        $a = $this->head;
        $b = $other->head;
        while ($a !== null && $b !== null) {
            if ($a->value !== $b->value) {
                return false;
            }
            $a = $a->next;
            $b = $b->next;
        }
        return $a === null && $b === null;
    }

    private function ensureType(int|string $value): void
    {
        if ($this->type === null) {
            $this->type = is_int($value) ? 'int' : 'string';
        }

        if ($this->type === 'int' && !is_int($value)) {
            throw new InvalidArgumentException('This list accepts only integers.');
        }
        if ($this->type === 'string' && !is_string($value)) {
            throw new InvalidArgumentException('This list accepts only strings.');
        }
    }

    /** Compare two values according to list type. */
    private function compare(int|string $a, int|string $b): int
    {
        if ($this->type === 'int') {
            /** @var int $a */
            /** @var int $b */
            return $a <=> $b;
        }
        /** @var string $a */
        /** @var string $b */
        return $a <=> $b; // lexicographic, case-sensitive
    }
}
