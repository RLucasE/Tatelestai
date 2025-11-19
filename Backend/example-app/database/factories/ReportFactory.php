<?php

namespace Database\Factories;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Models\FoodEstablishment;
use App\Models\Offer;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Report>
 */
class ReportFactory extends Factory
{
    protected $model = Report::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Por defecto, reportamos una oferta
        $reportableType = $this->faker->randomElement([
            Offer::class,
            FoodEstablishment::class,
            User::class,
        ]);

        $reportableId = match ($reportableType) {
            Offer::class => Offer::inRandomOrder()->first()?->id ?? Offer::factory()->create()->id,
            FoodEstablishment::class => FoodEstablishment::inRandomOrder()->first()?->id ?? FoodEstablishment::factory()->create()->id,
            User::class => User::inRandomOrder()->first()?->id ?? User::factory()->create()->id,
        };

        return [
            'reportable_type' => $reportableType,
            'reportable_id' => $reportableId,
            'reported_by' => User::inRandomOrder()->first()?->id ?? User::factory()->create()->id,
            'reason' => $this->faker->randomElement(ReportReason::cases())->value,
            'status' => ReportStatus::PENDING->value,
            'description' => $this->faker->paragraph(),
            'admin_notes' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ];
    }

    /**
     * Estado: En revisión
     */
    public function reviewing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReportStatus::REVIEWING->value,
        ]);
    }

    /**
     * Estado: Resuelto
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReportStatus::RESOLVED->value,
            'reviewed_by' => User::inRandomOrder()->first()?->id ?? User::factory()->create()->id,
            'reviewed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'admin_notes' => $this->faker->sentence(),
        ]);
    }

    /**
     * Estado: Descartado
     */
    public function dismissed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReportStatus::DISMISSED->value,
            'reviewed_by' => User::inRandomOrder()->first()?->id ?? User::factory()->create()->id,
            'reviewed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'admin_notes' => $this->faker->sentence(),
        ]);
    }

    /**
     * Reportar una oferta específica
     */
    public function forOffer(Offer $offer): static
    {
        return $this->state(fn (array $attributes) => [
            'reportable_type' => Offer::class,
            'reportable_id' => $offer->id,
        ]);
    }

    /**
     * Reportar un establecimiento específico
     */
    public function forEstablishment(FoodEstablishment $establishment): static
    {
        return $this->state(fn (array $attributes) => [
            'reportable_type' => FoodEstablishment::class,
            'reportable_id' => $establishment->id,
        ]);
    }

    /**
     * Reportar un usuario específico
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'reportable_type' => User::class,
            'reportable_id' => $user->id,
        ]);
    }

    /**
     * Con un usuario reportador específico
     */
    public function byUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'reported_by' => $user->id,
        ]);
    }

    /**
     * Con una razón específica
     */
    public function withReason(ReportReason $reason): static
    {
        return $this->state(fn (array $attributes) => [
            'reason' => $reason->value,
        ]);
    }
}

