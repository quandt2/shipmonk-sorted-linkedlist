# ShipMonk SortedLinkedList

A tiny, well‑typed PHP library implementing a sorted singly linked list that stores either integers or strings (but never a mix). The list maintains ascending order on every insertion.

- PHP 8.1+
- Iteration (`IteratorAggregate`), counting (`Countable`), JSON export (`JsonSerializable`)
- Generic PHPDoc for precise static analysis (`@template T of int|string`)
- Enforced homogenous element type: either all `int` or all `string`

## Installation

```bash
composer require quand/shipmonk-sorted-linkedlist
```

## Run locally (after git clone)

- Prerequisites:
  - PHP 8.1+ with mbstring extension
  - Composer v2
  - Git

- Clone and install dependencies:

```bash
# replace <repo-url> with your Git repository URL
git clone <repo-url>
cd shipmonk-sorted-linkedlist
composer install
```

- Run the quality gates:

```bash
# Coding standards (PHPCS)
composer lint

# Static analysis (PHPStan level 8)
composer stan

# PHPUnit test suite
composer test

# Or run everything
composer ci
```

- Tips:
  - You can run a single PHPUnit test using Composer’s script passthrough:
    - POSIX:
      ```bash
      composer test -- --filter testIntInsertAndOrder
      ```
    - Windows (PowerShell/CMD):
      ```powershell
      composer test -- --filter testIntInsertAndOrder
      ```
  - If you prefer calling binaries directly:
    - POSIX: `vendor/bin/phpunit`
    - Windows: `vendor\bin\phpunit.bat`

## API overview

- Construction
  - `new SortedLinkedList(?'int'|'string' $type = null)` — if `$type` is `null`, the list infers type on first `add()`.
  - `SortedLinkedList::ofInt()` / `SortedLinkedList::ofString()` — force element type.
  - `SortedLinkedList::fromArray(list<int|string> $values, ?string $type = null): self`
- Mutation
  - `add(T $value): void` — insert into sorted position; allows duplicates.
  - `addAll(iterable<int|string> $values): void`
  - `remove(T $value): bool` — remove first occurrence.
  - `removeAll(T $value): int` — remove all occurrences, returns number removed.
  - `clear(): void`
- Query
  - `contains(T $value): bool`
  - `first(): T|null` / `min(): T|null`
  - `last(): T|null` / `max(): T|null`
  - `toArray(): list<T>`
  - `isEmpty(): bool`
  - `size(): int` (alias for `count()`)
  - `equals(SortedLinkedList<T> $other): bool` — structural equality (same type and same ordered sequence)
- Iteration
  - Implements `IteratorAggregate<int, T>`.
  - Iterator yields values in ascending order.
  - Keys are numeric (0..n−1) when collected via `iterator_to_array($list, false)`; when using `foreach`, the loop index is independent of internal keys.

## Duplicate semantics

When inserting a value equal to the current head, the new value is placed before existing equal values (stable towards the front). For example, inserting another `2` into `[2, 2, 3]` yields `[2, 2, 2, 3]`.

If you prefer “append after equals” semantics, change the head insertion condition inside `add()` from `<= 0` to `< 0` and adjust tests accordingly.

## Complexity

- `add`: O(n)
- `remove` (first occurrence): O(n) with early exits thanks to ordering
- `removeAll`: O(n)
- `contains`: O(n) with early exits
- `first`/`min`: O(1)
- `last`/`max`: O(1) — tail pointer maintained
- Iteration: O(n)

## Static analysis

This library uses generics in PHPDoc:

```php
/** @template T of int|string */
final class SortedLinkedList implements IteratorAggregate, Countable, JsonSerializable { /* ... */ }
```

- Methods like `add`, `remove`, `contains` are annotated with `@param T`.
- Accessors like `first`, `last`, `min`, `max` are annotated `@return T|null`.
- `toArray()`/`jsonSerialize()` return `list<T>`.

PHPStan is configured at level 8. If you want stricter behavior for `src/`, consider setting `treatPhpDocTypesAsCertain: true` for production code while keeping tests relaxed.

## Coding standards & CI

- PHPCS is configured via `phpcs.xml`.
- GitHub Actions workflow runs PHPUnit, PHPStan, and PHPCS on PHP 8.1–8.4.

