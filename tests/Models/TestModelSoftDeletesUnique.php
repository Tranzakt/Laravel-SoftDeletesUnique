<?php

declare(strict_types=1);

/**
 * Model for use in SoftDeletesUnique model tests.
 *
 * @copyright 2022 Tranzakt
 * @author Sophist
 */

namespace Tranzakt\SoftDeletesUnique\Tests\Models;

use Hamcrest\Core\IsInstanceOf;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tranzakt\SoftDeletesUnique\Concerns\HasSoftDeletesUnique;

class TestModelSoftDeletesUnique extends Model
{
    use HasSoftDeletesUnique, SoftDeletes;

    protected $table;

    public function __construct(?string|array $attributes = [])
    {
        if (is_string($attributes)) {
            $this->table = $attributes;
            $attributes = [];
        } elseif (in_array('table', $attributes)) {
            $this->table = $attributes['table'];
            unset($attributes['table']);
        } else {
            $this->table = 'table_softDeletesUnique_ModelTests'
        }
        parent::__construct($attributes);
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