<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Location;
use Illuminate\Database\Seeder;

/**
 * The company's grievance categories and locations, two categories restricted by default.
 * They fill empty lists only, so seeding again never undoes what the Administrator has
 * since changed in Settings. Per-category target days are not set yet.
 */
class CompanyListsSeeder extends Seeder
{
    public function run(): void
    {
        if (! Category::exists()) {
            $categories = [
                ['Wages and benefits', 'तलब तथा सुविधा', false],
                ['Working hours and leave', 'काम गर्ने समय तथा बिदा', false],
                ['Occupational health and safety', 'पेशागत स्वास्थ्य तथा सुरक्षा', false],
                ['Unfair treatment or dismissal', 'अनुचित व्यवहार वा कामबाट निष्कासन', false],
                ['Discrimination', 'भेदभाव', false],
                ['Sexual harassment, exploitation and abuse', 'यौनजन्य दुर्व्यवहार, शोषण तथा हिंसा', true],
                ['Bullying, threats and violence', 'धम्की, हैरानी तथा हिंसा', false],
                ['Retaliation', 'बदलाको कारबाही', false],
                ['Reporting wrongdoing (fraud, corruption, breach of law)', 'गलत कामको सूचना (ठगी, भ्रष्टाचार, कानून उल्लङ्घन)', true],
                ['Child labour, forced labour or trafficking', 'बालश्रम, जबरजस्ती श्रम वा मानव बेचबिखन', false],
                ['Contract and supply-chain workers', 'करार तथा आपूर्ति शृंखलाका कामदार', false],
                ['Environment', 'वातावरण', false],
                ['Community relations and culture', 'समुदायसँगको सम्बन्ध तथा संस्कृति', false],
                ['Land and resources', 'भूमि तथा प्राकृतिक स्रोत', false],
                ['Privacy and personal data', 'गोपनीयता तथा व्यक्तिगत विवरण', false],
                ['Suppliers and vendors', 'आपूर्तिकर्ता तथा विक्रेता', false],
                ['Customers', 'ग्राहक', false],
                ['Handling of a grievance', 'गुनासो व्यवस्थापनसम्बन्धी', false],
                ['Other', 'अन्य', false],
            ];

            foreach ($categories as $sort => [$en, $ne, $restricted]) {
                Category::create(['name_en' => $en, 'name_ne' => $ne, 'is_restricted' => $restricted, 'sort' => $sort]);
            }
        }

        if (! Location::exists()) {
            foreach ([['Head office, Thapathali', 'केन्द्रीय कार्यालय, थापाथली'], ['Factory, Hetauda', 'उत्पादन कार्यस्थल, हेटौंडा'], ['Other', 'अन्यत्']] as $sort => [$en, $ne]) {
                Location::create(['name_en' => $en, 'name_ne' => $ne, 'sort' => $sort]);
            }
        }
    }
}
