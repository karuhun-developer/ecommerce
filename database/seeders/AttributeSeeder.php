<?php

namespace Database\Seeders;

use App\Models\Attribute\Attribute;
use App\Models\Attribute\AttributeGroup;
use Illuminate\Database\Seeder;

class AttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $attributeGroups = [
            [
                'name' => 'Color',
                'description' => 'Different colors available for products',
                'attributes' => [
                    [
                        'name' => 'Red',
                        'value' => 'red',
                        'description' => 'Red color',
                    ],
                    [
                        'name' => 'Blue',
                        'value' => 'blue',
                        'description' => 'Blue color',
                    ],
                    [
                        'name' => 'Green',
                        'value' => 'green',
                        'description' => 'Green color',
                    ],
                ],
            ],
            [
                'name' => 'Size',
                'description' => 'Different sizes available for products',
                'attributes' => [
                    [
                        'name' => 'Small',
                        'value' => 'small',
                        'description' => 'Small size',
                    ],
                    [
                        'name' => 'Medium',
                        'value' => 'medium',
                        'description' => 'Medium size',
                    ],
                    [
                        'name' => 'Large',
                        'value' => 'large',
                        'description' => 'Large size',
                    ],
                ],
            ],
        ];

        foreach ($attributeGroups as $groupData) {
            $attributes = $groupData['attributes'];
            unset($groupData['attributes']);

            $group = AttributeGroup::create($groupData);

            foreach ($attributes as $attributeData) {
                $attributeData['attribute_group_id'] = $group->id;
                Attribute::create($attributeData);
            }
        }
    }
}
