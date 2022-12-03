<?php

declare(strict_types=1);

/**
 * Test HasSoftDeletesUnique functionality
 *
 * @copyright 2022 Tranzakt
 * @author Sophist
 */

namespace Tranzakt\SoftDeletesUnique\Tests\Unit;

use Illuminate\Support\Facades\DB;
use Tranzakt\SoftDeletesUnique\Tests\Models\TestModelSoftDeletesUnique;
use Tranzakt\SoftDeletesUnique\Tests\Testcases\TestCaseSoftDeletesUnique;

class SoftDeletesUniqueModelTests extends TestCaseSoftDeletesUnique
{
    protected string $tableName = "table_softDeletesUnique_ModelTests";

    /**
     * Helper function to check row counts for all/normal/trashed records.
     */
    private function checkRowCounts(int $total, int $normal, int $deleted): void
    {
        $rows = TestModelSoftDeletesUnique::withTrashed()->get();
        $this->assertEquals(count($rows), $total);
        $rows = TestModelSoftDeletesUnique::all();
        $this->assertEquals(count($rows), $normal);
        $rows = TestModelSoftDeletesUnique::onlyTrashed()->get();
        $this->assertEquals(count($rows), $deleted);
    }

    /**
     * Test the model's basic functionality.
     *
     * 1. Create a record, check deleted_at_uniqueable was set correctly.
     * 2. Soft delete that record, check deleted_at_uniqueable was set correctly.
     * 3. Create a second record, check deleted_at_uniqueable was set correctly.
     * 4. Hard delete the second record.
     * 5. Restore the original record, check deleted_at_uniqueable was set correctly.
     */
    public function testSoftDeletesUniqueModelBasics()
    {
        $this->createTable($this->tableName);

        // Create basic record
        $model = TestModelSoftDeletesUnique::create([
            'field' => 'value1'
        ]);
        $this->assertEquals($model->deleted_at_uniqueable, '');
        $this->checkRowCounts(1, 1, 0);

        $row = TestModelSoftDeletesUnique::first();
        $this->assertEquals($row->deleted_at_uniqueable, '');

        // Delete the basic record
        $row->delete();
        $this->assertNotEquals($row->deleted_at_uniqueable, '');
        $this->checkRowCounts(1, 0, 1);

        // Create a new non-deleted record
        $model = TestModelSoftDeletesUnique::create([
            'field' => 'value1'
        ]);
        $this->assertEquals($model->deleted_at_uniqueable, '');
        $this->checkRowCounts(2, 1, 1);
        $row = TestModelSoftDeletesUnique::first();
        $this->assertEquals($row->deleted_at_uniqueable, '');

        // Hard delete the new row
        $row = TestModelSoftDeletesUnique::first();
        $row->forceDelete();
        $this->checkRowCounts(1, 0, 1);

        // Restore the original record
        $row = TestModelSoftDeletesUnique::onlyTrashed()->first();
        $row->restore();
        $this->assertEquals($row->deleted_at_uniqueable, '');
        $this->checkRowCounts(1, 1, 0);
        $row = TestModelSoftDeletesUnique::first();
        $this->assertEquals($row->deleted_at_uniqueable, '');
    }

    /**
     * Check that the unique constraint which is the purpose of this package is working correctly
     */
    public function testSoftDeletesUniqueModelUniqueness()
    {
        $this->createTable($this->tableName);

        // Create basic record
        $model = TestModelSoftDeletesUnique::create([
            'field' => 'value1'
        ]);
        $this->assertEquals($model->deleted_at_uniqueable, '');
        $this->checkRowCounts(1, 1, 0);

        // Attempt to create duplicate record
        $this->expectException(\Illuminate\Database\QueryException::class);
        $model = TestModelSoftDeletesUnique::create([
            'field' => 'value1'
        ]);

    }
}