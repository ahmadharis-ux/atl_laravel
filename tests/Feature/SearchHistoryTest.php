<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SearchHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_page_search_records_history(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/produk?q=telkomsel');

        $this->assertDatabaseHas('search_histories', [
            'user_id' => $user->id,
            'query' => 'telkomsel',
        ]);
    }

    public function test_ajax_search_does_not_record_history(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/produk?q=telkomsel', ['X-Requested-With' => 'XMLHttpRequest']);

        $this->assertDatabaseMissing('search_histories', [
            'user_id' => $user->id,
            'query' => 'telkomsel',
        ]);
    }

    public function test_history_is_pruned_to_ten_per_user(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 11; $i++) {
            SearchHistory::remember($user->id, sprintf('kata kunci %d', $i));
        }

        $queries = SearchHistory::recentQueries($user->id);

        $this->assertCount(10, $queries);
        $this->assertSame('kata kunci 11', $queries[0]);
        $this->assertNotContains('kata kunci 1', $queries);
        $this->assertSame(10, SearchHistory::where('user_id', $user->id)->count());
    }

    public function test_same_query_is_not_duplicated(): void
    {
        $user = User::factory()->create();

        SearchHistory::remember($user->id, 'pulsa');
        SearchHistory::remember($user->id, 'pulsa');

        $this->assertSame(1, SearchHistory::where('user_id', $user->id)->where('query', 'pulsa')->count());
        $this->assertSame(['pulsa'], SearchHistory::recentQueries($user->id));
    }

    public function test_blank_or_too_long_query_is_ignored(): void
    {
        $user = User::factory()->create();

        SearchHistory::remember($user->id, '   ');
        SearchHistory::remember($user->id, str_repeat('a', 61));

        $this->assertDatabaseCount('search_histories', 0);
    }

    public function test_clear_endpoint_only_removes_own_history(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        SearchHistory::remember($user->id, 'pulsa');
        SearchHistory::remember($other->id, 'token');

        $this->actingAs($user)->deleteJson('/search-history')->assertOk();

        $this->assertDatabaseMissing('search_histories', ['user_id' => $user->id]);
        $this->assertDatabaseHas('search_histories', ['user_id' => $other->id, 'query' => 'token']);
    }

    public function test_guest_cannot_clear_search_history(): void
    {
        $this->delete('/search-history')->assertRedirect(route('login'));
    }

    public function test_dashboard_renders_recent_history(): void
    {
        $user = User::factory()->create();
        SearchHistory::remember($user->id, 'diamond ml');

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('diamond ml');
    }

    public function test_remember_increments_hits(): void
    {
        $user = User::factory()->create();

        SearchHistory::remember($user->id, 'pulsa');
        SearchHistory::remember($user->id, 'pulsa');
        SearchHistory::remember($user->id, 'pulsa');

        $this->assertSame(3, SearchHistory::where('user_id', $user->id)->where('query', 'pulsa')->value('hits'));
    }

    public function test_top_queries_are_ordered_by_most_searched(): void
    {
        $user = User::factory()->create();

        SearchHistory::factory()->for($user)->create(['query' => 'token', 'hits' => 1]);
        SearchHistory::factory()->for($user)->create(['query' => 'pulsa', 'hits' => 4]);
        SearchHistory::factory()->for($user)->create(['query' => 'diamond', 'hits' => 9]);

        $this->assertSame(['diamond', 'pulsa', 'token'], SearchHistory::topQueries($user->id));
    }

    public function test_dashboard_recommends_products_matching_top_searches(): void
    {
        $user = User::factory()->create();
        Product::factory()->create(['name' => 'Pulsa Telkomsel 50.000', 'status' => true]);
        SearchHistory::factory()->for($user)->create(['query' => 'telkomsel', 'hits' => 3]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Sesuai Pencarianmu')
            ->assertSee('Pulsa Telkomsel 50.000');
    }

    public function test_dashboard_recommendations_survive_clearing_search_history(): void
    {
        $user = User::factory()->create();
        Product::factory()->create(['name' => 'Pulsa Telkomsel 50.000', 'status' => true]);
        SearchHistory::factory()->for($user)->create(['query' => 'telkomsel', 'hits' => 3]);

        $this->actingAs($user)->get(route('dashboard'))->assertSee('Sesuai Pencarianmu');

        $this->actingAs($user)->deleteJson(route('search.history.clear'))->assertOk();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Sesuai Pencarianmu')
            ->assertSee('Pulsa Telkomsel 50.000');
    }

    public function test_dashboard_hides_recommendations_without_search_history(): void
    {
        $user = User::factory()->create();
        Product::factory()->create(['name' => 'Pulsa Telkomsel 50.000', 'status' => true]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertDontSee('Sesuai Pencarianmu');
    }
}
