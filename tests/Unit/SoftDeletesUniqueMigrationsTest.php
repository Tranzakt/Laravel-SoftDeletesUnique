<?php

declare(strict_types=1);

/**
 * Test that SoftDeleteUnique columns can be created and that unique constraints work as expected.
 *
 * @copyright 2022 Tranzakt
 * @author Sophist
 */

namespace Tranzakt\SoftDeletesUnique\Tests\Unit;

use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tranzakt\SoftDeletesUnique\Tests\Testcases\TestCaseSoftDeletesUnique;

final class SoftDeletesUniqueModelsTest extends TestCaseSoftDeletesUnique
{
    /**
     * Test basic softDeleteUnique creation and deletion.
     */
    public function testSoftDeleteUnique_simplest(): void
    {
        $tableName = 'table_softdeleteunique_simplest';
        $columns = $this->createTable($tableName);

        $this->assertContains('deleted_at', $columns);
        $this->assertContains('deleted_at_uniqueable', $columns);

        Schema::table($tableName, function(Blueprint $table): void {
            $table->dropSoftDeletesUnique();
        });

        $columns = Schema::getColumnlisting($tableName);

        $this->assertContains('deleted_at', $columns);
        $this->assertNotContains('deleted_at_uniqueable', $columns);
    }

    /**
     * Test bespoke column names creation and deletion.
     */
    public function testSoftDeleteUnique_bespokeColumnNames(): void
    {
        $tableName = 'table_softdeleteunique_bespokeNames';
        $columns = $this->createTable($tableName,
            deleted_at: 'my_deleted_at',
            deleted_at_uniqueable: 'my_deleted_at_uniqueable'
        );

        $this->assertNotContains('deleted_at', $columns);
        $this->assertNotContains('deleted_at_uniqueable', $columns);
        $this->assertContains('my_deleted_at', $columns);
        $this->assertContains('my_deleted_at_uniqueable', $columns);

        Schema::table($tableName, function(Blueprint $table): void {
            $table->dropSoftDeletesUnique('my_deleted_at_uniqueable');
        });

        $columns = Schema::getColumnlisting($tableName);

        $this->assertContains('my_deleted_at', $columns);
        $this->assertNotContains('my_deleted_at_uniqueable', $columns);
    }

    /**
     * Test invalid precision.
     */
    public function testSoftDeleteUnique_negativePrecision(): void
    {
        // Negative precision should fail
        $tableName = 'table_softdeleteunique_invalidprecision';
        $e = null;
        try {
            $columns = $this->createTable($tableName, precision: -1);
        } catch (\Exception | \Error $e) {}
        $this->assertInstanceOf(\ValueError::class, $e);
    }

    /**
     * Test that unique / non-unique indexes allow duplicates without this
     * and duplicates are disallowed with a unique index on deleted_at_uniqueable.
     */
    public function testSoftDeletesUnique_uniqueIndexes(): void
    {
        $tableName = 'table_softdeleteunique_test_indexes';
        $columns = $this->createTable($tableName);

        $now = now();

        // Base entry
        DB::table($tableName)->insert([
            'field'=> 'value1',
            'deleted_at' => null,
            'deleted_at_uniqueable' => ''
        ]);
        $rows = DB::table($tableName)
            ->get();
        $this->assertEquals(count($rows), 1);

        $now = now();
        // Deleted version
        DB::table($tableName)->insert([
            'field'=> 'value1',
            'deleted_at' => $now,
            'deleted_at_uniqueable' => (string) $now
        ]);
        $rows = DB::table($tableName)
            ->get();
        $this->assertEquals(count($rows), 2);

        // deleted_at same null, so should insert
        // This is EXACTLY what this package is fixing
        DB::table($tableName)->insert([
            'field'=> 'value1',
            'deleted_at' => null,
            'deleted_at_uniqueable' => 'different'
        ]);
        $rows = DB::table($tableName)
            ->get();
        $this->assertEquals(count($rows), 3);

        // Insert of duplicate empty uniqueable should fail
        $e = null;
        try {
            DB::table($tableName)->insert([
                'field'=> 'value1',
                'deleted_at' => null,
                'deleted_at_uniqueable' => ''
            ]);
        } catch (\Exception $e) {}
        $this->assertInstanceOf(QueryException::class, $e);
    }
}