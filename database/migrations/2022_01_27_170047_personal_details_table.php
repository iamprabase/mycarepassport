<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PersonalDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('personal_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('communication_types')->nullable();
            $table->string('communication_devices')->nullable();
            $table->json('support_peoples')->nullable();
            $table->string('eating_drinking')->nullable();
            $table->json('eating_drinking_description')->nullable();
            $table->string('hygiene_toileting')->nullable();
            $table->json('emotional_behavioural_need')->nullable();
            $table->json('cultural_religious_need')->nullable();
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
        //
    }
}
