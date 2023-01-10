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
            Schema::create('sites', function (Blueprint $table) {
                $table->smallIncrements('id');
                $table->char('domain', 255);
                $table->char('language', 2);
                $table->boolean('testing')->default(0);
                $table->tinyInteger('seo', false, true); //not autoincrement - unsigned
                $table->timestamps();
            });
        }

        /**
         * Reverse the migrations.
         *
         * @return void
         */
        public function down()
        {
            Schema::dropIfExists('sites');
        }
    };
