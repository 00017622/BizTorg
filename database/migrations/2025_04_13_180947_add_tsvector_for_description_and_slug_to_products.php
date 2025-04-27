<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddTsvectorForDescriptionAndSlugToProducts extends Migration
{
    public function up()
    {
        // Add tsvector column for description
        DB::statement('ALTER TABLE products ADD COLUMN description_tsvector tsvector');
        // Create a GIN index for description_tsvector
        DB::statement('CREATE INDEX products_description_tsvector_idx ON products USING GIN(description_tsvector)');
        // Populate description_tsvector
        DB::statement("UPDATE products SET description_tsvector = to_tsvector('russian', COALESCE(description, ''))");
        // Add a trigger to keep description_tsvector updated
        DB::statement(<<<SQL
            CREATE TRIGGER tsvectorupdate_description
            BEFORE INSERT OR UPDATE ON products
            FOR EACH ROW EXECUTE FUNCTION
            tsvector_update_trigger(description_tsvector, 'pg_catalog.russian', description);
        SQL);

        // Add tsvector column for slug
        DB::statement('ALTER TABLE products ADD COLUMN slug_tsvector tsvector');
        // Create a GIN index for slug_tsvector
        DB::statement('CREATE INDEX products_slug_tsvector_idx ON products USING GIN(slug_tsvector)');
        // Populate slug_tsvector
        DB::statement("UPDATE products SET slug_tsvector = to_tsvector('russian', COALESCE(slug, ''))");
        // Add a trigger to keep slug_tsvector updated
        DB::statement(<<<SQL
            CREATE TRIGGER tsvectorupdate_slug
            BEFORE INSERT OR UPDATE ON products
            FOR EACH ROW EXECUTE FUNCTION
            tsvector_update_trigger(slug_tsvector, 'pg_catalog.russian', slug);
        SQL);
    }

    public function down()
    {
        // Drop trigger and index for description
        DB::statement('DROP TRIGGER IF EXISTS tsvectorupdate_description ON products');
        DB::statement('DROP INDEX IF EXISTS products_description_tsvector_idx');
        DB::statement('ALTER TABLE products DROP COLUMN description_tsvector');

        // Drop trigger and index for slug
        DB::statement('DROP TRIGGER IF EXISTS tsvectorupdate_slug ON products');
        DB::statement('DROP INDEX IF EXISTS products_slug_tsvector_idx');
        DB::statement('ALTER TABLE products DROP COLUMN slug_tsvector');
    }
}