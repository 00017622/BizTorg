<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddTsvectorToProducts extends Migration
{
    public function up()
    {
        // Add a tsvector column for the name
        DB::statement('ALTER TABLE products ADD COLUMN name_tsvector tsvector');
        // Create a GIN index for faster searches
        DB::statement('CREATE INDEX products_name_tsvector_idx ON products USING GIN(name_tsvector)');
        // Populate the tsvector column
        DB::statement("UPDATE products SET name_tsvector = to_tsvector('russian', name)");
        // Add a trigger to keep the tsvector column updated
        DB::statement(<<<SQL
            CREATE TRIGGER tsvectorupdate
            BEFORE INSERT OR UPDATE ON products
            FOR EACH ROW EXECUTE FUNCTION
            tsvector_update_trigger(name_tsvector, 'pg_catalog.russian', name);
        SQL);
    }

    public function down()
    {
        DB::statement('DROP TRIGGER IF EXISTS tsvectorupdate ON products');
        DB::statement('DROP INDEX IF EXISTS products_name_tsvector_idx');
        DB::statement('ALTER TABLE products DROP COLUMN name_tsvector');
    }
}