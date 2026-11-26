<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_successfully(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee(config('app.name'));
        $response->assertSee('Boracay Lost and Found System');
    }

    public function test_application_uses_manila_timezone(): void
    {
        $this->assertSame('Asia/Manila', config('app.timezone'));
        $this->assertSame('Asia/Manila', date_default_timezone_get());
    }

    public function test_application_connects_to_mysql(): void
    {
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $this->assertNotEmpty(DB::scalar('select version()'));
    }

    public function test_application_tables_use_innodb_engine(): void
    {
        $nonInnoDbTables = DB::table('information_schema.tables')
            ->where('table_schema', DB::connection()->getDatabaseName())
            ->where('engine', '!=', 'InnoDB')
            ->pluck('table_name');

        $this->assertSame('InnoDB', config('database.connections.mysql.engine'));
        $this->assertEmpty($nonInnoDbTables);
    }
}
