# RFQ NBI/GHM/RFQ/01: requirements

Copied word for word from the client's RFQ (Request for Quotation: Development of an Online Grievance Handling System for a Private Company), sections 2 and 3. Contact details, pricing, evaluation and contract terms are deliberately left out of this repo.

This file is the source for requirement wording. The company's changes after the RFQ (2026-11-12) are at the end, as U1 to U16; where one changes an RFQ row, it wins.

## From section 2: what the system is

The system needs to be purpose-built web application with two parts:

- a public side, where anyone can submit a grievance (named or anonymous) and later check its status with a reference number and PIN
- an admin side, where a few trained staff (about 3–5 users) manage cases, add notes, reply to complainants and export data

Technical guidance

- Use a common, well-supported web framework so that another developer can maintain it later.
- It must run on ordinary shared or low-cost hosting with a database (e.g. MySQL/PostgreSQL), preferably the company's existing hosting.
- Link the system from the company's website, e.g. on a subdomain such as [grievance.companydomain] or a page with a button.
- Keep public pages light and simple, with no heavy scripts, so they load fast on low-cost phones.
- Use free, open-source libraries wherever possible.

## Section 3: Requirements

All items are Mandatory (M) unless marked Desirable (D). In the quotation, confirm each ID and note anything you would do differently.

### 3.1 Public side

| ID | Requirement | M/D |
| --- | --- | --- |
| P1 | Grievance form, mobile-first, opened from the company's website; no account needed | M |
| P2 | Nepali and English, with a language switch | M |
| P3 | Short notice above the form on confidentiality, no retaliation and what happens next | M |
| P4 | Choice to submit anonymously or with a name; if named: name, phone and/or email, preferred contact method | M |
| P5 | Fields: category, location, date of incident, whether it happened once, repeatedly or is ongoing, description, one optional attachment (photo or PDF, up to 5 MB) | M |
| P6 | Optional fields, each with "prefer not to say": complainant type, sex, age group, ethnicity, disability status | M |
| P7 | On submission, show a reference number (e.g. GHM-2026-0001) and a random PIN, with advice to note them down; email them too if an email was given | M |
| P8 | Status page: enter reference number and PIN to see current status and any replies from the company, and add more information or a reply | M |
| P9 | Spam protection on the form | M |
| P10 | QR code to the form for posters | D |

### 3.2 Admin side

| ID | Requirement | M/D |
| --- | --- | --- |
| A1 | Secure login for admin users, with three roles: Administrator (all cases and settings), Focal person (all non-restricted cases), Restricted-case officer (restricted cases) | M |
| A2 | Case list with search by reference number and filters for status, category, location and date; shows days open, and highlights cases open more than 15 working days | M |
| A3 | Case page: view the submission; set status and priority; assign to a user; add internal notes (dated, with author, never shown to the complainant); send a reply that the complainant sees on the status page; upload documents | M |
| A4 | Statuses: Received, Under assessment, Under investigation, Referred / Out of scope, Resolved, Closed, Reopened. Priorities: Low, Medium, High, Critical | M |
| A5 | Restricted cases: any case in a restricted category (e.g. sexual harassment or abuse), or marked restricted by a user, is visible only to Restricted-case officers and the Administrator | M |
| A6 | Staff can enter grievances received offline (phone, email, drop box, in person), with a "channel" field | M |
| A7 | Simple settings page where the Administrator can manage users, categories (and which are restricted) and locations, without a developer | M |

### 3.3 Notifications and reports

| ID | Requirement | M/D |
| --- | --- | --- |
| N1 | Email alert to focal persons for each new case and each new complainant message, with no grievance details (only "log in to view"). For restricted cases, the alert goes only to Restricted-case officers | M |
| N2 | Email acknowledgement to the complainant, if they gave an email | M |
| N3 | Summary page: number of cases by status and by category, for a chosen date range (a simple table or a chart) | M |
| N4 | Export to Excel/CSV of cases in a date range: reference, dates received and closed, channel, category, location, priority, status, complainant type, sex, age group, ethnicity and disability status. Names and contact details are left out unless the Administrator chooses to include them | M |

### 3.4 Security and data

| ID | Requirement | M/D |
| --- | --- | --- |
| S1 | HTTPS on all pages | M |
| S2 | Passwords stored hashed; account locked for 15 minutes after 5 failed logins; automatic logout after 30 minutes idle | M |
| S3 | Sensitive fields (name, contact details, description, messages) encrypted in the database, using the framework's built-in encryption | M |
| S4 | Uploaded files stored outside the public web folder, and viewable only by logged-in users with access to that case | M |
| S5 | No IP address or device data stored for anonymous submissions | M |
| S6 | Activity log: who changed status, added notes, viewed or exported each case, and when | M |
| S7 | Automatic daily database backup, kept for at least 14 days, with a short restore instruction | M |
| S8 | Vendor keeps all data confidential, accesses live data only with the company's permission, and reports any suspected breach to NBI and the company within 24 hours, in line with Nepal's Individual Privacy Act, 2075 | M |
| S9 | Basic accessibility on public pages: labelled fields, readable contrast, works with a screen reader and keyboard | M |
| S10 | Two-factor login for admin users | D |

## Not from the RFQ: Nepali labels for A4

The RFQ gives A4's values in English only. The company confirmed the Nepali labels on 2026-11-12. Four differ from our drafts, noted in the last column. Keep the enum keys in English and the Nepali labels in `lang/ne.json`, so a wording change needs no code change.

| Enum key | English (RFQ wording) | Nepali (confirmed) | Our draft, if different |
| --- | --- | --- | --- |
| `received` | Received | प्राप्त | |
| `under_assessment` | Under assessment | प्रारम्भिक मूल्याङ्कनको क्रममा | मूल्याङ्कनमा |
| `under_investigation` | Under investigation | छानबिनको क्रममा | छानबिनमा |
| `referred_out_of_scope` | Referred / Out of scope | अन्यत्र सिफारिश गरिएको / दायराबाहिर | अन्यत्र पठाइएको / दायराबाहिर |
| `resolved` | Resolved | समाधान भएको | |
| `closed` | Closed | बन्द गरिएको | बन्द |
| `reopened` | Reopened | पुनः खोलिएको | |
| `low` | Low | न्यून | |
| `medium` | Medium | मध्यम | |
| `high` | High | उच्च | |
| `critical` | Critical | अति गम्भीर | |

## After the RFQ: the company's updates U1 to U16

On 2026-11-12 the company sent three documents:

- its answers to our questions;
- a brief on the updates its GHM Policy and SOP make, as approved by UNDP;
- Policy Annex A, which lists the grievance types.

The RFQ rows above stay word for word. Where an update below changes an RFQ row, the update wins.

- U1 to U11 are the brief's own table "Updates since the RFQ", word for word.
- U12 to U16 quote the brief's later sections and the answers.
- Questions about differences between the documents are with the company; until they answer, the "Open" notes below say what is unsettled.
- Nepali strings were first retyped from the documents' page images, because their text does not copy correctly. On 2026-11-12, every one was checked against the company's Word files of the brief and of the answers, and all match. Where the answers and the brief differ, both versions are shown below; both are the company's own wording.

| ID | Change (brief, section 1, word for word) | Where it applies | Brief section |
| --- | --- | --- | --- |
| U1 | Target time per category (15, 30 or 45 working days) instead of one 15-day limit; a case is highlighted when it passes its own target | Settings, case list | 3, 4 |
| U2 | Administrator can extend a case's due date, with a reason logged | Case page | 3 |
| U3 | Clock pauses at Resolved or Closed, and restarts from the reopening date when Reopened | Case logic | 3 |
| U4 | Feedback step before closure: complainant can answer feedback questions on the status page after a case is Resolved* | Status page, case page | 6 |
| U5 | Two categories restricted by default (sexual harassment; reporting wrongdoing); any user can mark a case restricted; only the Administrator can un-restrict, with a reason | Settings, case page | 2, 4 |
| U6 | Alerts: new unassigned cases alert all relevant users; Administrator alerted only for three events | Notifications | 2 |
| U7 | Export limits: users export only cases they can see; every export logged | Export | 2 |
| U8 | Option lists and Nepali labels for statuses, priorities, categories and form fields | Public form, case page | 3–5 |
| U9 | Dates: BS on the Nepali form, AD on the English form; stored in AD; BS and AD shown on the dashboard and in exports | Form, dashboard, export | 5 |
| U10 | Public text: confidentiality notice and messages | Public pages | 7 |
| U11 | Groups under 5 hidden in summary reports | Summary page | 8 |
| U12 | Status rules (brief 3.1, 3.3): "Referred / Out of scope: Complainant must be told the reason through a reply"; "Reopened: Clock restarts from the reopening date; reason recorded in an internal note"; "Appeal: Lodged within 15 working days of the outcome (as a complainant message, after which staff set the case to Reopened); decided within 15 working days" | Case page | 3 |
| U13 | Dashboard indicators (by quarter, with a date filter): "Number of grievances by category, location, channel and complainant type; Breakdown by sex and age group; Average resolution time; share resolved within target; number of extensions; number reopened; Feedback: share fully, partly or not resolved; average satisfaction; reports of negative treatment; Any breakdown with fewer than 5 cases shows '<5' so individuals cannot be identified." | Summary page | 8 |
| U14 | Export: "reference number; date received (AD and BS); channel; location; category; priority; status; date of each status change; resolved date; days taken (working days); within target (yes/no); extension (yes/no); reopened (yes/no); 'About you' fields; feedback answers if the structured form is built. Restricted cases are exported only by the Administrator and are marked as restricted. No complainant names or contact details in the standard export." | Export | 8 |
| U15 | "Keep case records for 5 years after closure. Deletion is manual, by the Administrator, after the Senior Manager's approval; the audit log records it." | Case data | 8 |
| U16 | Staff screens (answers): "English is fine for the internal screens, but a Nepali toggle is preferable. Please make sure that: the complainant's own text is shown exactly as they entered it, in Nepali or English; statuses and categories show the Nepali name next to the English on the case page, so staff know what the complainant sees; staff can reply to complainants in Nepali (Unicode) without any display problems" | Case page | Answers |

\* The brief marks the structured feedback form as optional.

### Details the updates rely on

- **U1, U2 time frames (brief 3.3).** All in working days, counted from receipt, excluding Saturdays and the holidays in the Administrator's holiday list. Resolution: the category's target time. Extension: "Once, up to 15 working days, set by the Administrator with a reason; further extensions also by the Administrator, recorded as approved by the Grievance Redressal Committee." Feedback: within 30 calendar days after the case is Resolved. Acknowledgement: immediately online; within 3 working days for offline channels. Initial assessment: 7 working days from receipt.
- **Weekly holiday and public holidays (answers).** Saturday is the only weekly holiday. Public holidays are the government's; Sundays are working days. Each year the focal person enters the new list at the start of the year and the Administrator checks it. The Administrator can edit the holiday list without a developer.
- **Priorities (brief 3.2).** Low and Medium: standard time frames. High: acknowledge within 1 working day, assess within 2. Critical: same-day action, alerts the Administrator. Categories 3, 6, 7, 10 and 17 can need same-day action; staff mark them Critical when there is immediate danger.
- **U4 feedback (brief 6, system behaviour).** "After the status is set to Resolved, the case shows a 'Feedback due' date 30 days later on the staff dashboard. Minimum: staff record the answers as an internal note headed 'Feedback'. The complainant can answer by a reply on the status page (the only route for anonymous complainants). Optional: a short structured feedback form on the status page, shown only when the case is Resolved. If built, answers are saved to the case and included in the export. Closure rule: the status can change to Closed only after a Feedback note exists, or a note records two failed contact attempts at least 5 working days apart. If the answers show the remedy was not carried out, the concern was not resolved, or negative treatment, staff set the status to Reopened."
- **U5, U7 roles (brief 2).** Administrator: all cases, including restricted; manages users, categories, locations, holiday list and target times; un-restricts cases (with reason); extends due dates (with reason); exports names and contact details. Focal person: all non-restricted cases; logs offline grievances; assesses, assigns, updates, replies, adds notes; marks a case restricted; exports non-sensitive data for cases they can see. Restricted-case officer: restricted cases; as focal person, for restricted cases. No login: the IT contact (hosting and server access only) and investigation panel members, whose findings the focal person records as internal notes. Open: the brief's section 8 says the export is "Administrator only", which section 2 and the answers do not; asked.
- **U6 alerts (brief 2, answers).** No alert contains grievance details, only a prompt to log in.

  | Event | Who is alerted |
  | --- | --- |
  | New case, not restricted | All Focal person users |
  | New case, restricted | All Restricted-case officers |
  | New complainant message on an assigned case | The assigned user |
  | Restricted case arrives and no active Restricted-case officer exists | Administrator |
  | Case set to Critical priority | Administrator (same day) |
  | Case passes its target time without being resolved | Administrator (escalation) |

- **U9 dates (answers).** Open the public page in Nepali by default, with English one click away. Bikram Sambat for the incident date on the Nepali form, AD on the English form. Store all dates in AD. On the staff screens, BS with AD alongside, for example 2083-07-22 (2026-11-08). Excel exports include both BS and AD date columns.
- **U15 data and security (brief 8).** No complainant IP addresses in application or server logs. No third-party analytics, chat widgets or tracking scripts on the public page. Referrals to outside services are recorded as internal notes; no separate referral module. All data stays on the company's server and domain; the company owns the data.

### U8: the company's lists

All "About you" questions are optional, each with "Prefer not to say": भन्न चाहँदिन in the answers, भन्न नचाहने in the brief (open). Keep "Under 18" separate: it helps to identify possible child labour.

Who are you? (तपाईं को हुनुहुन्छ?), as the answers list it:

| English | Nepali |
| --- | --- |
| Permanent employee | स्थायी कर्मचारी |
| Temporary or probationary employee | अस्थायी वा परीक्षणकालीन कर्मचारी |
| Contract worker, e.g. security, cleaning, driver | करार कामदार, जस्तै सुरक्षाकर्मी, सरसफाई, चालक आदि |
| Former employee | पूर्व कर्मचारी |
| Supplier or supply-chain worker | आपूर्तिकर्ता वा आपूर्ति शृंखलामा आबद्ध कामदार |
| Customer | ग्राहक |
| Community member | समुदायको सदस्य |
| Trade union | ट्रेड युनियन |
| Worker representative | कामदार प्रतिनिधि |
| Civil society organisation | नागरिक समाज |
| Other | अन्य |

Open: the brief merges trade union, worker representative and civil society organisation into one option (ट्रेड युनियन, कामदार प्रतिनिधि वा नागरिक समाज), and gives contract worker in Nepali as करार कामदार alone.

| Field | English options | Nepali options |
| --- | --- | --- |
| Sex (लिङ्ग) | Female; Male; Other | महिला; पुरुष; अन्य |
| Age group (उमेर समूह) | Under 18; 18–24; 25–34; 35–44; 45–59; 60 and above | १८ वर्षभन्दा कम; १८–२४; २५–३४; ३५–४४; ४५–५९; ६० वर्ष वा माथि |
| Caste/ethnicity (जाति/जातीयता) | Brahmin/Chhetri; Janajati / Indigenous Nationalities; Dalit; Madhesi; Tharu; Muslim; Other | ब्राह्मण/क्षेत्री; आदिवासी जनजाति; दलित; मधेशी; थारु; मुस्लिम; अन्य |
| Disability (अपाङ्गता) | Do you have a disability? Yes; No | Answers: के तपाईं अपाङ्गता भएको व्यक्ति हुनुहुन्छ? हो / होइन. Brief: के तपाईंमा अपाङ्गता छ? छ; छैन (open) |
| Submission type (गुनासोको प्रकार) | Without my name (anonymous); With my name | नाम नखुलाई (बेनामी); नाम सहित |
| Preferred contact (सम्पर्क माध्यम) | Phone; Email; Do not contact me | फोन; इमेल; मलाई सम्पर्क नगर्नुहोस् |
| How often did it happen? (घटना कतिपटक भएको हो? in the answers; कति पटक in the brief) | Once; Repeatedly; Still happening | एक पटक; पटक-पटक; अझै भइरहेको |
| Date of incident (घटना भएको मिति) | AD on the English form | विक्रम संवत (BS) on the Nepali form |
| Location (घटना भएको स्थान) | Head office, Thapathali; Factory, Hetauda; Other | केन्द्रीय कार्यालय, थापाथली; उत्पादन कार्यस्थल, हेटौंडा; अन्यत् in the answers, अन्य in the brief (open) |

The brief refers to a "section 9" for caste/ethnicity and disability that it does not include (asked).

Channel: staff choose it for offline grievances; website submissions are set to "Online form" automatically.

| English | Nepali |
| --- | --- |
| Online form | अनलाइन फाराम |
| Phone / hotline | फोन / हटलाइन |
| Email | इमेल |
| Drop box | उजुरी पेटिका |
| In person | प्रत्यक्ष |
| Letter | पत्र |
| Meeting, e.g. Labour Relations Committee or community meeting | बैठक |
| Referred by another organisation, e.g. Labour Office, NHRC or a union | अन्य निकायबाट सिफारिश गरिएको |
| Other | अन्य |

Categories, with target time in working days, and R for restricted by default. The Administrator can edit names, target days and the restricted flag. Complainants see the names; target days are internal only.

| # | English | Nepali | Target days | R |
| --- | --- | --- | --- | --- |
| 1 | Wages and benefits | तलब तथा सुविधा | 15 | |
| 2 | Working hours and leave | काम गर्ने समय तथा बिदा | 15 | |
| 3 | Occupational health and safety | पेशागत स्वास्थ्य तथा सुरक्षा | 15 | |
| 4 | Unfair treatment or dismissal | अनुचित व्यवहार वा कामबाट निष्कासन | 30 | |
| 5 | Discrimination | भेदभाव | 30 | |
| 6 | Sexual harassment, exploitation and abuse | यौनजन्य दुर्व्यवहार, शोषण तथा हिंसा | 30 | R |
| 7 | Bullying, threats and violence | धम्की, हैरानी तथा हिंसा | 30 | |
| 8 | Retaliation | बदलाको कारबाही | 15 | |
| 9 | Reporting wrongdoing (fraud, corruption, breach of law) | गलत कामको सूचना (ठगी, भ्रष्टाचार, कानून उल्लङ्घन) | 45 | R |
| 10 | Child labour, forced labour or trafficking | बालश्रम, जबरजस्ती श्रम वा मानव बेचबिखन | 15 | |
| 11 | Contract and supply-chain workers | करार तथा आपूर्ति शृंखलाका कामदार | 30 | |
| 12 | Environment | वातावरण | 30 | |
| 13 | Community relations and culture | समुदायसँगको सम्बन्ध तथा संस्कृति | 30 | |
| 14 | Land and resources | भूमि तथा प्राकृतिक स्रोत in the answers, जग्गा तथा प्राकृतिक स्रोत in the brief (open) | 45 | |
| 15 | Privacy and personal data | गोपनीयता तथा व्यक्तिगत विवरण | 15 | |
| 16 | Suppliers and vendors | आपूर्तिकर्ता तथा विक्रेता | 30 | |
| 17 | Customers | ग्राहक | 15 | |
| 18 | Handling of a grievance | गुनासो व्यवस्थापनसम्बन्धी | 15 | |
| 19 | Other | अन्य | 30 | |

Roles in Nepali (brief 2): Administrator प्रशासक; Focal person गुनासो सम्पर्क व्यक्ति; Restricted-case officer संवेदनशील उजुरी अधिकृत.

### U4: the six feedback questions

The brief says to check questions 4 and 6 against Policy section 10 before building; the Policy text wins if it differs.

| # | English | Nepali | Answer options |
| --- | --- | --- | --- |
| 1 | Was the agreed remedy carried out? | सहमति भएको समाधान लागू भयो? | Yes / No (हो / होइन) |
| 2 | Has your concern been resolved? | के तपाईंको समस्या समाधान भयो? | Fully / Partly / Not at all (पूर्ण रूपमा / आंशिक रूपमा / पटक्कै भएन) |
| 3 | Have you been treated badly because you raised a grievance? | गुनासो दिएकै कारण तपाईंलाई कसैले नराम्रो व्यवहार गरेको छ? | Yes / No (छ / छैन) |
| 4 | Was the process fair and easy to follow? | के समाधान प्रक्रिया निष्पक्ष र बुझ्न सजिलो थियो? | Yes / Partly / No (हो / आंशिक / होइन) |
| 5 | How satisfied are you overall? | समग्रमा तपाईं कति सन्तुष्ट हुनुहुन्छ? | 1–5 (१ = पटक्कै सन्तुष्ट छैन, ५ = धेरै सन्तुष्ट) |
| 6 | How could we improve? | हामीले कसरी सुधार गर्न सक्छौं? | Free text |

### U10: text on the public pages

The brief says: "Use this text exactly; NEEK may edit it later through the Administrator settings if that is built."

| Where | English | Nepali |
| --- | --- | --- |
| Page title | Raise a grievance | गुनासो दर्ता गर्नुहोस् |
| Emergency line (top of the page) | In immediate danger? Call the police on 100. | तत्काल खतरामा हुनुहुन्छ भने प्रहरीलाई १०० मा फोन गर्नुहोस्। |
| Status page | Check your grievance | आफ्नो गुनासोको अवस्था हेर्नुहोस् |
| Other ways to raise a grievance | Phone, email, drop boxes at the head office and factory, or in person with the focal person. | फोन, इमेल, केन्द्रीय कार्यालय र उत्पादन कार्यस्थलमा राखिएका उजुरी पेटिका, वा गुनासो सम्पर्क व्यक्तिसँग प्रत्यक्ष। |

The company still has to send the hotline number and grievance email address for the last row.

Confidentiality notice, above the form (P3):

> Your grievance is safe with us.
>
> - Only the trained staff handling your grievance can see it. We will not share your name or details without your consent, unless the law requires it.
> - You can submit without giving your name. Keep your reference number and PIN safe. They are the only way to follow your grievance and receive our replies.
> - NEEK does not allow anyone to be punished or treated unfairly for raising a concern in good faith. If this happens, report it, and we will act on it as a priority.
> - Complaints of sexual harassment or abuse are seen only by a specially trained team, which includes a woman officer.

> तपाईंको गुनासो हामीसँग सुरक्षित छ
>
> - तपाईंको गुनासो त्यसको व्यवस्थापन गर्ने तालिमप्राप्त कर्मचारीले मात्र हेर्न सक्छन्। कानूनले नतोकेसम्म तपाईंको सहमतिबिना तपाईंको नाम वा विवरण अरू कसैलाई दिइने छैन।
> - तपाईं आफ्नो नाम नखुलाई पनि गुनासो दर्ता गर्न सक्नुहुन्छ। आफ्नो सन्दर्भ नम्बर र पिन सुरक्षित राख्नुहोस्। गुनासोको अवस्था हेर्न र हाम्रो जवाफ पाउन यही मात्र उपाय हो।
> - इमानदारीपूर्वक गुनासो गरेबापत कसैलाई सजाय वा अनुचित व्यवहार गर्न नदिन NEEK प्रतिबद्ध छ। यदि त्यस्तो भएमा हामीलाई जानकारी गराउनुहोस्, हामी प्राथमिकताका साथ कारबाही गर्नेछौं।
> - यौनजन्य दुर्व्यवहार वा हिंसासम्बन्धी गुनासो महिला अधिकृतसहितको विशेष तालिमप्राप्त टोलीले मात्र हेर्छ।

After submitting:

> Thank you. Your grievance has been received.
>
> Keep your reference number and PIN safe: you need both to check progress and reply.
>
> What happens next: We will tell you within 7 working days whether we can take up your grievance. Most cases are resolved within 15 to 45 working days, depending on the type. You can check progress at any time, and we will ask whether the solution worked for you.
>
> Free, and your rights stay with you: this service is free. Using it does not stop you from going to the police, the Labour Office, a national commission or the courts.

> धन्यवाद। तपाईंको गुनासो प्राप्त भयो।
>
> आफ्नो सन्दर्भ नम्बर र पिन सुरक्षित राख्नुहोस्। प्रगति हेर्न र जवाफ दिन दुवै चाहिन्छ।
>
> अब के हुन्छ: गुनासो दर्ता हुनासाथ तपाईंले सन्दर्भ नम्बर पाउनुहुनेछ। गुनासो अघि बढाउन सकिने वा नसकिने बारे ७ कार्यदिनभित्र जानकारी दिइनेछ। गुनासोको प्रकृतिअनुसार धेरैजसो गुनासो १५ देखि ४५ कार्यदिनभित्र समाधान गरिन्छ। तपाईं जुनसुकै बेला प्रगति हेर्न सक्नुहुन्छ, र समाधान चित्त बुझ्यो कि बुझेन भनेर हामी तपाईंसँग सोध्नेछौं।
>
> यो सेवा निःशुल्क हो। यसको प्रयोग गर्दा तपाईंलाई प्रहरी, श्रम कार्यालय, राष्ट्रिय आयोग वा अदालतमा जानबाट रोकिँदैन।

The answers give the confidentiality notice as one block, in this order:

1. the heading and the four points above;
2. the "What happens next" paragraph;
3. the "Free, and your rights stay with you" paragraph;
4. "In immediate danger? Call the police on 100."

The brief splits the same text between the notice, the top of the page and the page after submitting. The Nepali follows the same order, without a lead-in phrase before "यो सेवा निःशुल्क हो।"

Open: P3 asks the notice above the form to cover what happens next, which the brief places only after submitting (the answers put it in the notice); and the Nepali "What happens next" has a first sentence the English lacks. Both asked.
