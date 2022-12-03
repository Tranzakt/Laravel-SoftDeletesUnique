<?php

declare(strict_types=1);

/**
 * Model for use in SoftDeletesUnique model tests.
 *
 * @copyright 2022 Tranzakt
 * @author Sophist
 */

namespace Tranzakt\SoftDeletesUnique\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tranzakt\SoftDeletesUnique\Concerns\HasSoftDeletesUnique;

class TestModelSoftDeletesUnique extends Model
{
    use HasSoftDeletesUnique, SoftDeletes;

    protected $table;

    public function __construct(?string $table = 'table_softDeletesUnique_ModelTests')
    {
        $this->table = $table;
        parent::__construct();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'field',
    ];
}