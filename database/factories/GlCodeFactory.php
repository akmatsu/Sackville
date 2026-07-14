<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Division;
use App\Models\Fund;
use App\Models\GlCode;
use App\Models\ObjectCode;
use App\Models\SubObjectCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GlCode>
 */
class GlCodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $department = Department::factory()->create();
        $division = Division::factory()->create(['department_code' => $department->code]);
        $objectCode = ObjectCode::factory()->create();
        $subObjectCode = SubObjectCode::factory()->create(['object_code' => $objectCode->code]);

        return [
            'fund_code' => Fund::factory(),
            'department_code' => $department->code,
            'division_id' => $division->id,
            'object_code' => $objectCode->code,
            'sub_object_code_id' => $subObjectCode->id,
            'label' => fake()->sentence(3),
            'active' => true,
        ];
    }

    /**
     * A division-level rollup GL code (no object or sub-object segment).
     */
    public function divisionRollup(): self
    {
        return $this->state(fn (array $attributes): array => [
            'object_code' => null,
            'sub_object_code_id' => null,
        ]);
    }

    /**
     * A budget-line rollup GL code (no sub-object segment).
     */
    public function budgetLineRollup(): self
    {
        return $this->state(fn (array $attributes): array => [
            'sub_object_code_id' => null,
        ]);
    }
}
