<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('nickname')->nullable();
            $table->string('username')->unique();
            $table->string('phone');
            $table->string('country_code');
            $table->unique(['phone', 'country_code']);
            $table->string('full_phone')->storedAs('CONCAT(country_code, phone)');
            $table->string('code')->nullable();
            $table->string('image')->nullable();
            $table->string('gender')->nullable();
            $table->string('language', 2)->default('en');
            $table->string('fcm_token')->nullable();
            $table->boolean('notified')->default(true);
            $table->date('birthdate')->nullable();
            $table->string('status')->default(App\Enum\UserStatus::ONLINE->value);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
