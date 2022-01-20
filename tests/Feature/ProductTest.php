<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use WithFaker;

    public function setUp():void {
        parent::setUp();
        Artisan::call('migrate:refresh');
        Artisan::call('passport:install',['--uuids' => true, '--no-interaction' => true]);
    }

    /**
     * @test
     */
    public function a_user_can_view_all_the_products()
    {
        Category::factory(10)->create();

        $this->seed(RoleSeeder::class);
        $this->seed(UserSeeder::class);
        $this->seed(CategorySeeder::class);

        $user = (new User())->whereHas('role', function($query) {
            $query->where('name', 'Admin');
        })->first();

        $response = $this->actingAs($user, 'api')->withHeaders([
            "Content-Type" => "application/json",
            "Accept" => "application/json"
        ])->post(route('api.products'),[]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }
}
