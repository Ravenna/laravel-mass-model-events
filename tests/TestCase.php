<?php

namespace Ravenna\MassModelEvents\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Ravenna\MassModelEvents\Tests\Models\User;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected string $connection;

    protected function setUp(): void
    {
        $this->connection = getenv('DB_CONNECTION') ?: 'sqlite';

        parent::setUp();

        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        Model::unguard();

        User::query()->insert([
            ['name' => 'TJ'],
            ['name' => 'James'],
            ['name' => 'Dominic'],
        ]);

        Model::reguard();
    }

    protected function tearDown(): void
    {
        DB::connection()->disconnect();

        parent::tearDown();
    }

    protected function getEnvironmentSetUp($app): void
    {
        $config = require __DIR__ . '/config/database.php';

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', $config[$this->connection]);
    }
}