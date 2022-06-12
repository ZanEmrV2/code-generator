<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('users');
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('username', 100)->unique();
            $table->string('email', 100)->unique();
            $table->string('password_hash', 60);
            $table->boolean('activated')->nullable();
            $table->string('activation_key', 20)->nullable();
            $table->string('title', 25)->nullable();
            $table->string('mobile_number', 25)->nullable();
            $table->dateTime('resert_date')->nullable();
            $table->string('reset_key', 20)->nullable();
            $table->integer('login_attempts')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 100)->unique();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('display_name', 100)->unique();
            $table->string('name', 100)->unique();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->integer('role_id')->unsigned();
            $table->integer('permission_id')->unsigned();
            $table->primary(['role_id','permission_id']);
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('RESTRICT')->onUpdate('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('RESTRICT')->onUpdate('cascade');
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->integer('user_id')->unsigned();
            $table->integer('role_id')->unsigned();
            $table->primary(['user_id','role_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('RESTRICT')->onUpdate('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('RESTRICT')->onUpdate('cascade');
        });

        Schema::create('groups', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable(false)->unique();
            $table->string('created_by');
            $table->string('updated_by')->nullable(true);
            $table->timestamps();
        });

        Schema::create('user_groups', function (Blueprint $table) {
            $table->integer('user_id')->unsigned()->nullable(false);
            $table->integer('group_id')->unsigned()->nullable(false);
            $table->primary(['user_id','group_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('RESTRICT')->onUpdate('cascade');
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('RESTRICT')->onUpdate('cascade');
        });

        Schema::create('menus', function (Blueprint $table) {
            $table->increments('id');
            $table->string('label')->nullable(false);
            $table->string('icon', 50)->nullable(true);
            $table->boolean('separator')->default(false);
            $table->string('router_link')->nullable(true);
            $table->integer('parent_id')->unsigned()->nullable(true);
            $table->integer('sort_order')->unsigned();
            $table->string('code')->unique();
            $table->string('created_by');
            $table->string('updated_by')->nullable(true);
            $table->timestamps();
            $table->foreign('parent_id')->references('id')->on('menu')
                ->onUpdate('cascade')->onDelete('restrict');
        });

        DB::statement('ALTER TABLE menus ADD CONSTRAINT id_is_not_parent_check CHECK (id <> parent_id)');

        Schema::create('menu_permissions', function (Blueprint $table) {
            $table->integer('menu_id')->unsigned()->nullable(false);
            $table->integer('permission_id')->unsigned()->nullable(false);
            $table->primary(['menu_id','permission_id']);
            $table->foreign('menu_id')->references('id')->on('menu')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('permission_id')->references('id')->on('permissions')->onUpdate('cascade')->onDelete('restrict');
        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('menu_permissions');
        Schema::drop('menus');
        Schema::drop('user_groups');
        Schema::drop('groups');
        Schema::drop('user_roles');
        Schema::drop('role_permissions');
        Schema::drop('permissions');
        Schema::drop('roles');
        Schema::drop('users');
    }
}
