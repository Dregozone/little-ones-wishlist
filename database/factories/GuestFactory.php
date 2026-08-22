<?php

namespace Database\Factories;

use App\Models\Guest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guest>
 */
class GuestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ip_hash' => Guest::hashIp(fake()->ipv4()),
            'has_consented' => true,
            'last_seen_at' => now(),
        ];
    }

    /**
     * A guest who declined the cookie, so has no durable identity beyond this session.
     */
    public function withoutConsent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'has_consented' => false,
        ]);
    }

    /**
     * A guest reachable by a token the test already knows.
     */
    public function withToken(string $token): static
    {
        return $this->afterCreating(fn (Guest $guest) => $guest->tokens()->create([
            'token_hash' => Guest::hashToken($token),
            'last_used_at' => now(),
        ]));
    }

    /**
     * A guest seen from a particular IP address.
     */
    public function fromIp(string $ip): static
    {
        return $this->state(fn (array $attributes): array => [
            'ip_hash' => Guest::hashIp($ip),
        ]);
    }
}
