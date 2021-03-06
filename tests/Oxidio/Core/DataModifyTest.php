<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Core;

use Php;
use Oxidio;
use Oxidio\Enum\Tables as T;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass DataModify
 */
class DataModifyTest extends TestCase
{
    public function testModify(): void
    {
        $modify = (new DataModify('view'))->withDb(static function () {
            return 1;
        });

        $values = static function () {
            return Php::map(['a' => 'A', 'b' => true, 'c' => false, 'd' => null]);
        };

        self::assertEquals(
            ["INSERT INTO view (\n  a, b, c, d\n) VALUES (\n  :a, :b, :c, :d\n)" => 1],
            $modify->insert($values)()
        );

        self::assertEquals(
            ["UPDATE view SET\n  a = :a,\n  b = :b,\n  c = :c,\n  d = :d\nWHERE (foo = 'bar' AND bar = 'foo')" => 1],
            $modify->update($values, ['foo' => 'bar', 'bar' => 'foo'])()
        );

        self::assertEquals(
            ["DELETE FROM view\nWHERE (foo = 'bar' AND bar = 'foo')" => 1],
            $modify->delete(['foo' => 'bar', 'bar' => 'foo'])()
        );

        self::assertEquals(
            [
                "INSERT INTO view (\n  a, b\n) VALUES (\n  :a, :b\n)" => 2,
                "INSERT INTO view (\n  a, b, c\n) VALUES (\n  :a, :b, :c\n)" => 1,
            ],
            $modify->insert(['a' => 1, 'b' => 1], ['a' => 2, 'b' => 2], ['a' => 3, 'b' => 3, 'c' => 3])()
        );

        self::assertEquals([
            "INSERT INTO view (\n  a, b\n) VALUES (\n  :a, ENCODE(:b, 'pass')\n)" => 1
        ], $modify->insert([
            'a' => 'A',
            'b' => function($column) {
                return ["ENCODE(:$column, 'pass')" => null];
            },
        ])());
    }

    public function testIntegration(): void
    {
        self::assertInstanceOf(Shop::class, $shop = Oxidio\Core\Shop::get());
        self::assertInstanceOf(DataModify::class, $modify = $shop->modify(T::COUNTRY));

        self::assertIsCallable($modify->delete([T\COUNTRY::ID => ['LIKE', 'test-%']]));
        self::assertCommit([['DELETE|LIKE' => 0]], $shop->commit());
        self::assertSame(0, $shop->query(T::COUNTRY, [T\COUNTRY::ID => ['LIKE', 'test-%']])->total);

        self::assertIsCallable(
            $modify->insert(
                [T\COUNTRY::ID => 'test-a', T\COUNTRY::TITLE => 'test-a'],
                [T\COUNTRY::ID => 'test-b', T\COUNTRY::TITLE => 'test-b'],
                [T\COUNTRY::ID => 'test-c', T\COUNTRY::TITLE => 'test-c', T\COUNTRY::ACTIVE => true]
            )
        );
        self::assertCommit([['INSERT INTO' => 2, 'INSERT INTO|active' => 1]], $shop->commit());
        self::assertCommit([], $shop->commit());
        self::assertIsCallable($modify->update([T\COUNTRY::SHORTDESC => 'test'], [T\COUNTRY::ID => ['LIKE', 'test-%']]));
        self::assertCommit([['UPDATE' => 3]], $shop->commit());

        self::assertIsCallable(
            $modify->map(['test-d' => 'test-D', 'test-c' => 'test-C'], static function (DataModify $modify, $value, $key) {
                yield $modify->insert([T\COUNTRY::ID => "$key-first", T\COUNTRY::TITLE => "$value-first"]);
                yield $modify->insert([T\COUNTRY::TITLE => "$value-second", T\COUNTRY::ID => "$key-second"]);
                yield $modify->update(
                    [T\COUNTRY::TITLE => function ($column) { return "UPPER($column)";}],
                    [T\COUNTRY::ID => ['LIKE', "$key-%"]]
                );
            })
        );
        self::assertCommit([
            ['INSERT|id,' => 1],
            ['INSERT|title,' => 1],
            ['UPDATE|= UPPER(' => 2],
            ['INSERT|id,' => 1],
            ['INSERT|title,' => 1],
            ['UPDATE|= UPPER(' => 2],
        ], $shop->commit());

        self::assertSame(1, $shop->query(T::COUNTRY, [T\COUNTRY::TITLE => 'TEST-C-FIRST'])->total);

        $modify->replace(static function () {
            yield 'test-a' => [T\COUNTRY::TITLE => 'test-a-replaced'];
            yield 'test-e' => [T\COUNTRY::TITLE => 'test-e-new', T\COUNTRY::ACTIVE => true];
            yield 'test-b' => null;
            yield 'test-b' => null;
            yield 'test-D-first' => null;
            yield [T\COUNTRY::TITLE => 'test-c-second'] => null;
            yield [T\COUNTRY::TITLE => 'test-c-first'] => [T\COUNTRY::ID => 'ignore', T\COUNTRY::LONGDESC => 'foobar'];
            yield [T\COUNTRY::TITLE => 'test-z'] => [T\COUNTRY::ID => 'test-z', T\COUNTRY::LONGDESC => 'foobar'];
        }, T\COUNTRY::ID);

        self::assertCommit([[
            'INSERT' => 2,
            'INSERT|active' => 1,
            'DELETE|id'  => 2,
            'DELETE|title' => 1,
            'INSERT|longdesc|UNION' => 3,
        ]], $shop->commit());

        self::assertEquals(
            ['test-c-first', 'test-z'],
            $shop->query(T::COUNTRY, function ($id) {
                return $id;
            }, [T\COUNTRY::LONGDESC => 'foobar'])->jsonSerialize()
        );

        $modify->delete([T\COUNTRY::ID => ['LIKE', 'test-%']]);
        self::assertCommit([['DELETE|LIKE' => 6]], $shop->commit());
    }

    private static function assertCommit(array $expected, iterable $commit): void
    {
        $commit = Php::traverse($commit, static function (array $counts) {
            return Php::map($counts, static function (int $count, string $sql) {
                return ['sql' => $sql, 'count' => $count];
            })->values;
        });
        self::assertCount(count($expected), $commit);
        foreach ($expected as $i => $counts) {
            $j = 0;
            foreach ($counts as $sql => $count) {
                self::assertSame($count, $commit[$i][$j]['count'] ?? null);
                foreach (explode('|', $sql) as $token) {
                    self::assertStringContainsString($token, $commit[$i][$j]['sql'] ?? null);
                }
                $j++;
            }
        }
    }
}
