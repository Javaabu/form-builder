<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected function table(string $name): string
    {
        return config("form-builder.tables.{$name}", $name);
    }

    public function up(): void
    {
        Schema::create($this->table('forms'), function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->boolean('accepts_entries')->default(true);
            $table->unsignedBigInteger('published_version_id')->nullable()->index();

            // Translations (javaabu/translatable). Harmless without it.
            $table->json('translations')->nullable();
            $table->string('lang')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($this->table('form_versions'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained($this->table('forms'))->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->string('layout')->default('sections');
            $table->json('settings')->nullable();
            $table->string('hash', 64)->nullable();
            $table->nullableMorphs('author');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['form_id', 'number']);
        });

        Schema::create($this->table('form_sections'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_version_id')->constrained($this->table('form_versions'))->cascadeOnDelete();
            $table->string('key');
            $table->json('title')->nullable();
            $table->json('description')->nullable();
            $table->unsignedTinyInteger('columns')->nullable();
            $table->json('conditions')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['form_version_id', 'key']);
        });

        Schema::create($this->table('form_fields'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_version_id')->constrained($this->table('form_versions'))->cascadeOnDelete();
            $table->foreignId('form_section_id')->constrained($this->table('form_sections'))->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained($this->table('form_fields'))->cascadeOnDelete();
            $table->string('key');
            $table->string('type');
            $table->json('label')->nullable();
            $table->json('help')->nullable();
            $table->json('placeholder')->nullable();
            $table->boolean('is_required')->default(false);
            $table->string('span')->nullable();
            $table->json('settings')->nullable();
            $table->json('conditions')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['form_version_id', 'key']);
        });

        Schema::create($this->table('form_entries'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained($this->table('forms'))->cascadeOnDelete();
            $table->foreignId('form_version_id')->constrained($this->table('form_versions'))->restrictOnDelete();
            $table->nullableMorphs('submitter');
            $table->nullableMorphs('subject');
            $table->string('status')->default('submitted')->index();
            $table->json('data')->nullable();
            $table->string('locale', 10)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create($this->table('form_entry_values'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_entry_id')->constrained($this->table('form_entries'))->cascadeOnDelete();
            $table->foreignId('form_field_id')->nullable()->constrained($this->table('form_fields'))->nullOnDelete();
            $table->string('field_key')->index();
            $table->string('path');
            $table->text('value')->nullable();
            $table->decimal('number', 30, 10)->nullable();
            $table->dateTime('date')->nullable();
            $table->timestamps();

            $table->index(['form_entry_id', 'field_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table('form_entry_values'));
        Schema::dropIfExists($this->table('form_entries'));
        Schema::dropIfExists($this->table('form_fields'));
        Schema::dropIfExists($this->table('form_sections'));
        Schema::dropIfExists($this->table('form_versions'));
        Schema::dropIfExists($this->table('forms'));
    }
};
