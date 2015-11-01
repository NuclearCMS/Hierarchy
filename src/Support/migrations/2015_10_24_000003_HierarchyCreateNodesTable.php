<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class HierarchyCreateNodesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('nodes', function (Blueprint $table)
        {
            $table->increments('id');
            $table->integer('node_type_id')->unsigned()->nullable();

            $table->integer('parent_id')->nullable();
            $table->integer('lft')->nullable();
            $table->integer('rgt')->nullable();
            $table->integer('depth')->nullable();

            $table->boolean('visible')->default(1);
            $table->boolean('locked')->default(0);
            $table->integer('status')->default(30);
            $table->boolean('hides_nodes')->default(0);
            $table->double('priority')->unsigned()->default(1);

            $table->string('children_order')->default('lft');
            $table->string('children_order_direction', 4)->default('asc');

            $table->timestamps();

            $table->foreign('node_type_id')
                ->references('id')
                ->on('node_types')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('nodes');
    }
}
