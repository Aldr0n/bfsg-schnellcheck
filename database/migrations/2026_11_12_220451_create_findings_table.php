<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('findings', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('audit_id')->constrained()->cascadeOnDelete();
            $table->string('accessibility_rule');
            $table->text('snippet');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('findings');
    }
};
