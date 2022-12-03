# Laravel-SoftDeletesUnique

The purpose of this extension is to make SQL unique constraints (Unique Indexes)
work when you are using softDeletes on a table.

Suppose you have a model with id and name fields -
and you want name to be unique,
so you create a Unique Index on the "name" field.
If you create a record for 'Pete' and then (hard) delete it,
then you can (of course) create a new record for 'Pete' without the name being duplicated.

Then you decide to use Soft Deletes,
with a `deleted_at` field which is null for non-deleted records,
and has a timestamp if the record is deleted.

The idea, of course,
is that models with Soft Delete work the same as it would without it,
except that you can restore the deleted records.
But if you create a record for 'Pete' and then soft delete it,
then you want the model to behave the same as if you had hard deleted it
and still allow you to create a new (non-deleted) record for 'Pete'
alongside the deleted version.
You therefore want the combination of `deleted_at` and `name` to be unique,
so you create a unique index on `['deleted_at', 'name']` expecting it to prevent duplicates,
i.e. in your migration...

``` php
$table->string('email')->unique();
```

is replaced with:

``` php
$table->string('email');
$table->softDeletes();
$table->unique(['deleted_at', 'email']);
```

**However there is a gotcha here just waiting to getcha!**
(and you probably won't explicitly test for this and it will be a problem waiting to happen).

Unfortunately most (but not all) SQL RDBMS follow the SQL standard
which defines every NULL value as being different from every other NULL value.
Yes `NULL != NULL` (and that is NOT a typo!!),
and that means that the unique index bizarrely allows you
to have multiple rows [null, 'Pete']!!!

**This is the problem that this package solves.**

It does it by creating a new column `deleted_at_uniqueable`
which is maintained as a string version of the `deleted_at` column;
using the empty string `''` if the `deleted_at` column is null.

Your code now needs to look as follows:

``` php
$table->string('email');
$table->softDeletes();
$table->softDeletesUnique();
$table->unique(['deleted_at_uniqueable', 'email']);
```

**Note:** When you select (non-deleted) records using a SoftDeletes model,
i.e. you don't use the `withTrash()` or `onlyTrash()` modifiers,
Laravel's Eloquent automatically adds `WHERE deleted_at IS NULL` to the SELECT query.
In many cases, to enable the database optimiser to avoid a full table scan,
you will likely still need some sort of index on `deleted_at`.
Since we are now using `deleted_at_uniqueable` for the unique index,
you may need to create a non-unique index on the `deleted_at` field as well,
i.e. your migration would need to look like...

``` php
$table->string('email');
$table->softDeletes()->index();
$table->softDeletesUnique();
$table->unique(['deleted_at_uniqueable', 'email']);
```

## Installation & Usage

### Installation

``` bash
composer require Tranzakt/Laravel-SoftDeletesUnique
```

Once installed, softDeletesUnique support is automatically added to migration Blueprint objects.

### Usage

**In your Migrations...**

1. Add `$table->softDeleteUnique()->after('deleted_at');`
2. Replace `$table->unique(['deleted_at', 'column']);` with `$table->unique(['deleted_at_uniqueable', 'column']);`
3. Add a non-unique index on `deleted_at` with `$table->softDeletes()->index();` or `$table->index('deleted_at');`.

``` php
public function up()
{
    Schema::create('table_name', function (Blueprint $table) {
        ...

        $table->string('email');
        $table->softDeletes()->index();
        $table->softDeletesUnique();
        $table->unique(['deleted_at_uniqueable', 'email']);
    });
}
```

**In your Models...**

1. Add `use Tranzakt\softDeletesUnique\Concerns\HasSoftDeletesUnique;` to the header
and `use HasSoftDeletesUnique;` to the top of the class.

``` php
use Tranzakt\softDeletesUnique\Concerns\HasSoftDeletesUnique;

class TableName extends Model {
    use HasSoftDeletesUnique;
}
```

As normal, you can use a parameter on the `softDeletesUnique('deleted_at_str')` to create the column with a different name,
and use `CONST DELETED_AT_UNIQUEABLE = 'deleted_at_str';` in your model to tell the model what the column name is.

## How it works

This package has been written to use the standard Laravel Eloquent facilities as fully as possible.

The `softDeletesUnique` and `dropSoftDeletesUnique` methods are macroed into Blueprint.

`$table->softDeletesUnique();` creates a new non-nullable string column
`deleted_at_uniqueable` of up to 24 characters
(format 'YYYY-MM-DD HH:MM:SS.xxxxxx'),
that contains either '' when `deleted_at` is null, or a string representation if it is not null.

The `HasSoftDeletesUnique` trait creates observers on the creating, updating, deleting and restoring Eloquent actions
and ensures that the `deleted_at_uniqueable` column is set appropriately.

The softDelete functionality also has a method to turn it off,
and SoftDeletesUnique respects this

And that's all folks.

## Alternatives

This package is only one way to fix this database unique constraint issue,
but it is believed to be the only common way of fixing it
that works with all the Laravel supported RDBMS without change,
and which doesn't require the coder to do any special DB:raw commands
in the migrations.

However, depending on the RDBMS you are using, there are alternative solutions
(including where necessary an additional index to support the `WHERE deleted_at IS NULL`
added by softDeletes):

### PostgreSQL / SQLite

Use 2 partial (filtered) indexes as follows:

``` SQL
CREATE UNIQUE INDEX active_email_unique ON MyTable (`email`) WHERE `deleted_at` IS NULL;
CREATE UNIQUE INDEX deleted_email_unique ON MyTable (`deleted_at`, `email`) WHERE `deleted_at` IS NOT NULL;
```

Laravel Schema objects do **not** include the ability to define `WHERE` clauses on indexes,
so you will need to use DB::raw to create and execute the above SQL data definition statements.

Because we have separate partial indexes when `deleted_at` is both NULL and NOT NULL,
the database should be able to use one of these indexes when Eloquent's softDelete functionality
adds `WHERE deleted_at IS NULL` to the select statement.

### Microsoft SQL Server

Microsoft SQL Server considers NULL===NULL so that no special treatment is needed.

### MySQL / MariaDB

Unfortunately neither MySQL nor MariaDB support indexes with `WHERE` clauses,
and we need to use a "virtual column" instead.

The raw SQL needed to create a virtual column and index it is as follows:

``` SQL
ALTER TABLE MyTable
ADD COLUMN deleted_at_unique VARCHAR(19) GENERATED ALWAYS AS
IF(`deleted_at` IS NULL, '-', `deleted_at`) VIRTUAL;
CREATE UNIQUE INDEX email_unique_index ON MyTable(`deleted_at_unique`, `email`);
```

I haven't tested this,
however I am doubtful whether this index would be used for the `WHERE deleted_at IS NULL` clause,
so a non-unique index on `deleted_at` will likely also be needed for performance
(with other columns if the index would be more useful with them added).

The Laravel code for the above is:

``` PHP
$table->string('deleted_at_unique')->virtualAs('IF(`deleted_at` IS NULL, '-', `deleted_at`)');
$table->unique('deleted_at_unique', 'email');
```

## License

This package is Licensed under the MIT open-source License.

## Acknowledgements

This package has been built by standing on the shoulders of others who have
done the hard work of identifying both the issue and the solution.

This package was originally authored by Sophist,
with additional contributions from: .

If you submit a PR, please add your name to the above list as part of your PR.
