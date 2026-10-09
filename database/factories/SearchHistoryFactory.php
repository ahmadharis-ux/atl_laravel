<?php

namespace Database\Factories;

use App\Models\SearchHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SearchHistory>
 */
class SearchHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'query' => Str::lower(fake()->unique()->words(2, true)),
            'hits' => 1,
            'last_searched_at' => now(),
        ];
    }

    /**
     * Jumlah berapa kali kata kunci ini dicari.
     */
    public function hits(int $hits): static
    {
        return $this->state(fn () => ['hits' => $hits]);
    }

    /**
     * Waktu pencarian dilakukan.
     */
    public function at(\DateTimeInterface $when): static
    {
        return $this->state(fn () => ['last_searched_at' => $when]);
    }
}
