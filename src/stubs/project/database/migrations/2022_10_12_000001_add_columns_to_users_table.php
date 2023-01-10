<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        /**
         * Run the migrations.
         *
         * @return void
         */
        public function up()
        {
            Schema::table('users', function (Blueprint $table) {
                $table->string('type')->nullable()->after('id');
                $table->unsignedSmallInteger('site_id')->after('id');
                $table->string('email_for_confirm')->nullable()->after('email');
                $table->unsignedTinyInteger('active')->default(0)->after('remember_token');
            });
        }

        /**
         * Reverse the migrations.
         *
         * @return void
         */
        public function down()
        {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['type', 'email_for_confirm', 'site_id', 'type']);
            });
        }
    };
