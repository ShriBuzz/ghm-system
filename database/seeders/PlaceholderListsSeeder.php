<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Location;
use Illuminate\Database\Seeder;

/**
 * Placeholder categories and locations until NBI sends the real lists
 * (clarification question 2). The Administrator edits them in Settings;
 * the Nepali names still need a native speaker's check.
 */
class PlaceholderListsSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['Wages and benefits', 'तलब र सुविधा', false],
            ['Working conditions and safety', 'काम गर्ने अवस्था र सुरक्षा', false],
            ['Discrimination', 'भेदभाव', false],
            ['Sexual harassment or abuse', 'यौन दुर्व्यवहार वा दुरुपयोग', true],
            ['Other', 'अन्य', false],
        ];

        foreach ($categories as $sort => [$en, $ne, $restricted]) {
            Category::firstOrCreate(['name_en' => $en], ['name_ne' => $ne, 'is_restricted' => $restricted, 'sort' => $sort]);
        }

        foreach ([['Head office', 'केन्द्रीय कार्यालय'], ['Production plant', 'उत्पादन प्लान्ट'], ['Other', 'अन्य']] as $sort => [$en, $ne]) {
            Location::firstOrCreate(['name_en' => $en], ['name_ne' => $ne, 'sort' => $sort]);
        }
    }
}
