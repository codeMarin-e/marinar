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
        Schema::create('add_vars', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('addvariable_id');
            $table->string('addvariable_type');
            $table->unsignedSmallInteger('site_id');
            $table->string('language', 2);
            $table->string('var_name');
            $table->longText('var_value')->nullable();
            $table->timestamps();

            $table->unique([
                'addvariable_id', 'addvariable_type', 'site_id', 'language', 'var_name'
            ], 'add_var_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('add_vars');
    }
};
