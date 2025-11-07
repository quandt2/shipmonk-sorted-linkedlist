<?php

declare(strict_types=1);

namespace ShipMonk\SortedLinkedList\Tests;

use PHPUnit\Framework\TestCase;
use ShipMonk\SortedLinkedList\SortedLinkedList;
use InvalidArgumentException;

final class SortedLinkedListTest extends TestCase
{
    public function testIntInsertAndOrder(): void
    {
        $list = new SortedLinkedList('int');
        $list->add(5);
        $list->add(1);
        $list->add(3);
        $list->add(3);

        $this->assertSame([1, 3, 3, 5], $list->toArray());
        $this->assertTrue($list->contains(3));
        $this->assertFalse($list->contains(4));
        $this->assertSame(1, $list->first());
        $this->assertSame(5, $list->last());
        $this->assertCount(4, $list);
    }

    public function testStringInsertAndOrder(): void
    {
        $list = new SortedLinkedList('string');
        $list->add('delta');
        $list->add('alpha');
        $list->add('charlie');
        $list->add('bravo');

        $this->assertSame(['alpha', 'bravo', 'charlie', 'delta'], $list->toArray());
    }

    public function testTypeInferenceAndMismatch(): void
    {
        $list = new SortedLinkedList(); // infer type from first add
        $list->add(10);
        $this->expectException(InvalidArgumentException::class);
        $list->add('oops'); // mixing types should throw
    }

    public function testTypeInferenceWithStringFirst(): void
    {
        $list = new SortedLinkedList();
        $list->add('a');
        $this->expectException(InvalidArgumentException::class);
        $list->add(1);
    }

    public function testRemove(): void
    {
        $list = new SortedLinkedList('int');
        foreach ([10, 20, 30] as $v) {
            $list->add($v);
        }
        $this->assertTrue($list->remove(20));
        $this->assertFalse($list->remove(25));
        $this->assertSame([10, 30], $list->toArray());
        $this->assertCount(2, $list);
    }

    public function testDuplicatePolicyNewBeforeEqualsAtHead(): void
    {
        $list = new SortedLinkedList('int');
        $list->add(3);
        $list->add(2);
        $list->add(2); // should insert before existing 2 at head
        $this->assertSame([2, 2, 3], $list->toArray());
    }

    public function testLastAndTailUpdatesOnRemoveAndAdd(): void
    {
        $list = new SortedLinkedList('int');
        $list->addAll([1, 2, 3]);
        $this->assertSame(3, $list->last());
        $this->assertTrue($list->remove(3));
        $this->assertSame(2, $list->last());
        $list->add(4);
        $this->assertSame(4, $list->last());
    }

    public function testClearPreservesType(): void
    {
        $list = new SortedLinkedList('int');
        $list->addAll([1, 2, 3]);
        $list->clear();
        $this->assertTrue($list->isEmpty());
        $this->expectException(InvalidArgumentException::class);
        $list->add('x'); // still int-only after clear
    }

    public function testIteration(): void
    {
        $list = new SortedLinkedList('int');
        foreach ([4, 1, 3, 2] as $v) {
            $list->add($v);
        }
        $collected = [];
        foreach ($list as $v) {
            $collected[] = $v;
        }
        $this->assertSame([1, 2, 3, 4], $collected);
    }

    public function testIterationEmpty(): void
    {
        $list = new SortedLinkedList('int');
        $collected = [];
        foreach ($list as $v) {
            $collected[] = $v;
        }
        $this->assertSame([], $collected);
        $this->assertNull($list->first());
        $this->assertNull($list->last());
    }

    public function testHelpersOfIntOfStringAndFromArray(): void
    {
        $intList = SortedLinkedList::ofInt();
        $intList->addAll([5, 1, 3]);
        $this->assertSame([1, 3, 5], $intList->toArray());

        $strList = SortedLinkedList::ofString();
        $strList->addAll(['b', 'a', 'c']);
        $this->assertSame(['a', 'b', 'c'], $strList->toArray());

        $from = SortedLinkedList::fromArray([3, 1, 2]);
        $this->assertSame([1, 2, 3], $from->toArray());

        $this->expectException(InvalidArgumentException::class);
        SortedLinkedList::fromArray([1, 'x']);
    }

    public function testIsEmptyAndSize(): void
    {
        $list = SortedLinkedList::ofInt();
        $this->assertTrue($list->isEmpty());
        $this->assertSame(0, $list->size());
        $list->add(1);
        $this->assertFalse($list->isEmpty());
        $this->assertSame(1, $list->size());
    }

    public function testRemoveEdgeCasesHeadMiddleTailAndEarlyExit(): void
    {
        $list = SortedLinkedList::ofInt();
        $list->addAll([1, 2, 3, 4, 5]);
        $this->assertTrue($list->remove(1)); // head
        $this->assertSame([2, 3, 4, 5], $list->toArray());
        $this->assertTrue($list->remove(3)); // middle
        $this->assertSame([2, 4, 5], $list->toArray());
        $this->assertTrue($list->remove(5)); // tail
        $this->assertSame([2, 4], $list->toArray());
        $this->assertFalse($list->remove(6)); // early exit (greater than tail)
        $this->assertSame([2, 4], $list->toArray());
    }

    public function testRemoveAll(): void
    {
        $list = SortedLinkedList::ofInt();
        $list->addAll([2, 2, 2, 3, 3, 4, 2]);
        $removed = $list->removeAll(2);
        $this->assertSame(4, $removed);
        $this->assertSame([3, 3, 4], $list->toArray());

        $unchanged = $list->removeAll(99);
        $this->assertSame(0, $unchanged);
        $this->assertSame([3, 3, 4], $list->toArray());

        $empty = SortedLinkedList::ofInt();
        $this->assertSame(0, $empty->removeAll(1));
    }

    public function testMinMax(): void
    {
        $list = SortedLinkedList::ofString();
        $this->assertNull($list->min());
        $this->assertNull($list->max());
        $list->addAll(['b', 'a', 'c']);
        $this->assertSame('a', $list->min());
        $this->assertSame('c', $list->max());
    }

    public function testEquals(): void
    {
        $a = SortedLinkedList::fromArray([3, 1, 2], 'int');
        $b = SortedLinkedList::fromArray([1, 2, 3], 'int');
        $this->assertTrue($a->equals($b));

        $c = SortedLinkedList::fromArray(['1', '2'], 'string');
        $d = SortedLinkedList::fromArray([1, 2], 'int');
        $this->assertFalse($c->equals($d)); // different type

        $e = SortedLinkedList::fromArray([1, 2, 4], 'int');
        $this->assertFalse($b->equals($e)); // different contents

        $this->assertTrue($b->equals($b)); // same instance
    }
}
