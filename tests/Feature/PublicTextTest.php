<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Notification;

/**
 * The company's text on the public pages, word for word.
 */
beforeEach(function () {
    Notification::fake();
});

it('shows the emergency line at the top of every public page, the number a phone link', function (string $route) {
    foreach (['ne' => '<a href="tel:100">तत्काल खतरामा हुनुहुन्छ भने प्रहरीलाई १०० मा फोन गर्नुहोस्।</a>', 'en' => '<a href="tel:100">In immediate danger? Call the police on 100.</a>'] as $locale => $line) {
        $html = $this->get(route($route, ['locale' => $locale]))->assertOk()->getContent();

        expect($html)->toContain("<p class=\"emergency\">{$line}</p>")
            ->and(strpos($html, 'class="emergency"'))->toBeLessThan(strpos($html, 'class="site-name"'))
            ->and(strpos($html, 'class="emergency"'))->toBeGreaterThan(strpos($html, '<header'));
    }
})->with(['home', 'status.lookup'])->group('U10');

it('titles the form and the status page in the company\'s words', function () {
    $this->get('/en')->assertSee('<title>Raise a grievance · Grievance handling</title>', false)->assertSee('<h1>Raise a grievance</h1>', false);
    $this->get('/ne')->assertSee('<h1>गुनासो दर्ता गर्नुहोस्</h1>', false);
    $this->get('/en/status')->assertSee('<h1>Check your grievance</h1>', false);
    $this->get('/ne/status')->assertSee('<h1>आफ्नो गुनासोको अवस्था हेर्नुहोस्</h1>', false);
})->group('U10');

/**
 * The company's notice, word for word: heading, four points, then two paragraphs.
 *
 * @return array<string, array{string, list<string>, list<string>}>
 */
function companyNotice(): array
{
    return [
        'en' => ['Your grievance is safe with us.', [
            'Only the trained staff handling your grievance can see it. We will not share your name or details without your consent, unless the law requires it.',
            'You can submit without giving your name. Keep your reference number and PIN safe. They are the only way to follow your grievance and receive our replies.',
            'NEEK does not allow anyone to be punished or treated unfairly for raising a concern in good faith. If this happens, report it, and we will act on it as a priority.',
            'Complaints of sexual harassment or abuse are seen only by a specially trained team, which includes a woman officer.',
        ], [
            'What happens next: We will tell you within 7 working days whether we can take up your grievance. Most cases are resolved within 15 to 45 working days, depending on the type. You can check progress at any time, and we will ask whether the solution worked for you.',
            'Free, and your rights stay with you: this service is free. Using it does not stop you from going to the police, the Labour Office, a national commission or the courts.',
        ]],
        'ne' => ['तपाईंको गुनासो हामीसँग सुरक्षित छ', [
            'तपाईंको गुनासो त्यसको व्यवस्थापन गर्ने तालिमप्राप्त कर्मचारीले मात्र हेर्न सक्छन्। कानूनले नतोकेसम्म तपाईंको सहमतिबिना तपाईंको नाम वा विवरण अरू कसैलाई दिइने छैन।',
            'तपाईं आफ्नो नाम नखुलाई पनि गुनासो दर्ता गर्न सक्नुहुन्छ। आफ्नो सन्दर्भ नम्बर र पिन सुरक्षित राख्नुहोस्। गुनासोको अवस्था हेर्न र हाम्रो जवाफ पाउन यही मात्र उपाय हो।',
            'इमानदारीपूर्वक गुनासो गरेबापत कसैलाई सजाय वा अनुचित व्यवहार गर्न नदिन NEEK प्रतिबद्ध छ। यदि त्यस्तो भएमा हामीलाई जानकारी गराउनुहोस्, हामी प्राथमिकताका साथ कारबाही गर्नेछौं।',
            'यौनजन्य दुर्व्यवहार वा हिंसासम्बन्धी गुनासो महिला अधिकृतसहितको विशेष तालिमप्राप्त टोलीले मात्र हेर्छ।',
        ], [
            'अब के हुन्छ: गुनासो दर्ता हुनासाथ तपाईंले सन्दर्भ नम्बर पाउनुहुनेछ। गुनासो अघि बढाउन सकिने वा नसकिने बारे ७ कार्यदिनभित्र जानकारी दिइनेछ। गुनासोको प्रकृतिअनुसार धेरैजसो गुनासो १५ देखि ४५ कार्यदिनभित्र समाधान गरिन्छ। तपाईं जुनसुकै बेला प्रगति हेर्न सक्नुहुन्छ, र समाधान चित्त बुझ्यो कि बुझेन भनेर हामी तपाईंसँग सोध्नेछौं।',
            'यो सेवा निःशुल्क हो। यसको प्रयोग गर्दा तपाईंलाई प्रहरी, श्रम कार्यालय, राष्ट्रिय आयोग वा अदालतमा जानबाट रोकिँदैन।',
        ]],
    ];
}

it('shows the company\'s notice word for word in both languages until the Administrator changes it', function () {
    foreach (companyNotice() as $locale => [$heading, $points, $paragraphs]) {
        $html = $this->get("/{$locale}")->getContent();
        preg_match('/<section class="notice".*?<\/section>/s', $html, $notice);
        preg_match_all('/<li>(.*?)<\/li>/s', $notice[0], $shownPoints);
        preg_match_all('/<p>(.*?)<\/p>/s', $notice[0], $shownParagraphs);

        expect($notice[0])->toContain("<h2 id=\"notice-heading\">{$heading}</h2>")
            ->and($shownPoints[1])->toBe($points)
            ->and($shownParagraphs[1])->toBe($paragraphs)
            ->and(substr_count($html, $locale === 'en' ? 'Call the police' : 'प्रहरीलाई १००'))->toBe(1); // Once, at the top.
    }
})->group('U10', 'P3');

it('lets the Administrator\'s notice use a heading, points and paragraphs, escaped', function () {
    Setting::put('notice.en', "Kept <b>safe</b>\n\n- One - with a dash inside\n- Two\n\nThen a paragraph.");
    expect($this->get('/en')->getContent())->toContain('<h2 id="notice-heading">Kept &lt;b&gt;safe&lt;/b&gt;</h2>')
        ->toMatch('/<ul>\s*<li>One - with a dash inside<\/li>\s*<li>Two<\/li>\s*<\/ul>/')
        ->toContain('<p>Then a paragraph.</p>');

    // A line may end in a letter whose last byte is also a line break in another encoding (अ).
    Setting::put('notice.ne', "शीर्षक\n\nक ख अ\n\n- एक अ\n- दुई");
    expect($this->get('/ne')->getContent())->toContain('<p>क ख अ</p>')->toMatch('/<li>एक अ<\/li>\s*<li>दुई<\/li>/u');
})->group('U10', 'P3');

it('offers the other ways to raise a grievance below the form', function () {
    $html = $this->get('/en')->getContent();

    expect($html)->toContain('<h2 id="other-ways-heading">Other ways to raise a grievance</h2>')
        ->toContain('<p>Phone, email, drop boxes at the head office and factory, or in person with the focal person.</p>')
        ->and(strpos($html, 'other-ways-heading'))->toBeGreaterThan(strpos($html, '</form>'));

    $this->get('/ne')->assertSee('गुनासो दर्ता गर्ने अन्य तरिका')
        ->assertSee('फोन, इमेल, केन्द्रीय कार्यालय र उत्पादन कार्यस्थलमा राखिएका उजुरी पेटिका, वा गुनासो सम्पर्क व्यक्तिसँग प्रत्यक्ष।');
})->group('U10');

it('thanks the complainant and says what happens next, keeping the advice that protects the PIN', function () {
    $this->post('/en', formInput());
    $this->get('/en/received')->assertOk()
        ->assertSee('<h1>Thank you. Your grievance has been received.</h1>', false)
        ->assertSee('<p>Keep your reference number and PIN safe: you need both to check progress and reply.</p>', false)
        ->assertSee('<p class="advice">Write both down now, or take a photo of this screen. The PIN cannot be shown again.</p>', false)
        ->assertSee('What happens next: We will tell you within 7 working days whether we can take up your grievance.')
        ->assertSee('Free, and your rights stay with you: this service is free. Using it does not stop you from going to the police, the Labour Office, a national commission or the courts.')
        ->assertSee('<a href="tel:100">In immediate danger? Call the police on 100.</a>', false)
        ->assertSee('so the next person using this phone cannot see them');

    $this->post('/en/received');
    $this->post('/ne', formInput());
    $this->get('/ne/received')->assertOk()
        ->assertSee('<h1>धन्यवाद। तपाईंको गुनासो प्राप्त भयो।</h1>', false)
        ->assertSee('आफ्नो सन्दर्भ नम्बर र पिन सुरक्षित राख्नुहोस्। प्रगति हेर्न र जवाफ दिन दुवै चाहिन्छ।')
        ->assertSee('अब के हुन्छ: गुनासो दर्ता हुनासाथ तपाईंले सन्दर्भ नम्बर पाउनुहुनेछ।')
        ->assertSee('यो सेवा निःशुल्क हो। यसको प्रयोग गर्दा तपाईंलाई प्रहरी, श्रम कार्यालय, राष्ट्रिय आयोग वा अदालतमा जानबाट रोकिँदैन।');
})->group('U10', 'P7');
