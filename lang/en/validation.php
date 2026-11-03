<?php

// Messages for the public form (P1, S9) that name the problem and the fix. Laravel's own English
// messages cover every other rule; this file only overrides these.
return [
    'uploaded' => 'The file could not be used. Choose another photo or PDF, up to 5 MB.',

    'custom' => [
        'reference' => ['required' => 'Write the reference number, for example GHM-2026-0001.'],
        'pin' => ['required' => 'Write the PIN.'],
        'body' => ['required' => 'Write your message.', 'max' => 'Shorten the message to 5,000 characters or fewer.'],
        'is_anonymous' => ['required' => 'Choose whether to give your name.'],
        'name' => ['required' => 'Write your name, or choose to stay anonymous.'],
        'phone' => ['required_if' => 'Write the phone number we should call.'],
        'email' => ['required_if' => 'Write the email address we should write to.', 'email' => 'Write the email address like name@example.com.'],
        'preferred_contact' => ['required' => 'Choose how we should contact you.'],
        'category_id' => ['required' => 'Choose what the grievance is about.', 'exists' => 'Choose one of the listed topics.'],
        'location_id' => ['required' => 'Choose where it happened.', 'exists' => 'Choose one of the listed places.'],
        'incident_date' => [
            'required' => 'Choose when it happened: the day, or "I don\'t remember the day", then the month and the year.',
            'date' => 'That date does not exist. Check the day and the month.',
            'before_or_equal' => 'That date is still to come. Choose the day it happened.',
        ],
        'recurrence' => ['required' => 'Choose how often it happened.'],
        'description' => ['required' => 'Describe what happened.', 'max' => 'Shorten the description to 5,000 characters or fewer.'],
        'attachment' => [
            'max' => 'The file is larger than 5 MB. Choose a smaller photo or PDF.',
            'mimes' => 'Choose a photo (JPG, PNG or WebP) or a PDF.',
            'uploaded' => 'The file could not be sent. It may be larger than 5 MB.',
        ],
    ],

    'attributes' => [
        'complainant_type' => 'who you are',
        'sex' => 'sex',
        'age_group' => 'age group',
        'ethnicity' => 'ethnic group',
        'disability' => 'disability',
    ],
];
