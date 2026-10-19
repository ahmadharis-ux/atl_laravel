<?php

namespace Tests\Feature;

use App\Enums\UserTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTierTest extends TestCase
{
    use RefreshDatabase;

    public function test_tier_is_silver_below_gold_threshold(): void
    {
        $user = User::factory()->create(['points' => 99]);

        $this->assertSame(UserTier::Silver, $user->tier());
    }

    public function test_tier_is_gold_at_gold_threshold(): void
    {
        $user = User::factory()->create(['points' => (int) config('loyalty.gold_points')]);

        $this->assertSame(UserTier::Gold, $user->tier());
    }

    public function test_tier_is_platinum_at_platinum_threshold(): void
    {
        $user = User::factory()->create(['points' => (int) config('loyalty.platinum_points')]);

        $this->assertSame(UserTier::Platinum, $user->tier());
    }

    public function test_tier_is_platinum_above_platinum_threshold(): void
    {
        $user = User::factory()->create(['points' => (int) config('loyalty.platinum_points') + 250]);

        $this->assertSame(UserTier::Platinum, $user->tier());
    }

    public function test_tier_css_class_matches_value(): void
    {
        $this->assertSame('tier-silver', UserTier::Silver->cssClass());
        $this->assertSame('tier-gold', UserTier::Gold->cssClass());
        $this->assertSame('tier-platinum', UserTier::Platinum->cssClass());
    }

    public function test_next_threshold_returns_null_at_max_tier(): void
    {
        $this->assertNull(UserTier::Platinum->nextThreshold());
        $this->assertSame((int) config('loyalty.platinum_points'), UserTier::Gold->nextThreshold());
    }
}
