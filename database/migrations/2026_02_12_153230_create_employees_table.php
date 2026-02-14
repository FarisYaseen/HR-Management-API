<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();

            $table->boolean('is_founder')->default(false);

            $table->foreignId('manager_id')
                ->nullable()
                ->constrained('employees')
                ->restrictOnDelete();

            // Founder gets founder_key = 1. Non-founders keep NULL.
            // Unique allows many NULLs, but only one value 1.
            $table->unsignedTinyInteger('founder_key')->nullable()->unique();

            $table->timestamps();
        });

        // SQLite does not support adding CHECK constraints with ALTER TABLE.
        // Enforce business rules with triggers.
        DB::unprepared(<<<'SQL'
CREATE TRIGGER employees_validate_insert
BEFORE INSERT ON employees
FOR EACH ROW
BEGIN
    SELECT CASE
        WHEN NEW.is_founder = 1 AND NEW.manager_id IS NOT NULL
            THEN RAISE(ABORT, 'Founder cannot have a manager')
        WHEN NEW.is_founder = 1 AND NEW.founder_key != 1
            THEN RAISE(ABORT, 'Founder must have founder_key = 1')
        WHEN NEW.is_founder = 0 AND NEW.manager_id IS NULL
            THEN RAISE(ABORT, 'Non-founder must have a manager')
        WHEN NEW.is_founder = 0 AND NEW.founder_key IS NOT NULL
            THEN RAISE(ABORT, 'Non-founder must have founder_key = NULL')
    END;
END;
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER employees_validate_update
BEFORE UPDATE ON employees
FOR EACH ROW
BEGIN
    SELECT CASE
        WHEN NEW.is_founder = 1 AND NEW.manager_id IS NOT NULL
            THEN RAISE(ABORT, 'Founder cannot have a manager')
        WHEN NEW.is_founder = 1 AND NEW.founder_key != 1
            THEN RAISE(ABORT, 'Founder must have founder_key = 1')
        WHEN NEW.is_founder = 0 AND NEW.manager_id IS NULL
            THEN RAISE(ABORT, 'Non-founder must have a manager')
        WHEN NEW.is_founder = 0 AND NEW.founder_key IS NOT NULL
            THEN RAISE(ABORT, 'Non-founder must have founder_key = NULL')
    END;
END;
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS employees_validate_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS employees_validate_update');

        Schema::dropIfExists('employees');
    }
};
