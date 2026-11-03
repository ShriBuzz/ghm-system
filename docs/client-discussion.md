# Points to discuss with the client

For the first meeting with NBI and the company after contract signing, RFQ NBI/GHM/RFQ/01. These are questions 1, 3, 4, 5, 7, 8, 10, 11, 12, 13, 14 and 16 from `docs/clarification-questions.md`, kept under the same numbers. Requirement IDs refer to RFQ section 3.

The system is built and works on the assumptions given under "What it does today". Each answer changes one setting or list, not the design. After the meeting, record each answer in the "Apply when answered" table of `docs/clarification-questions.md`.

## Hosting and deployment

### 1. Can the company's hosting run the system?

The test version is due on 18 Nov and needs a staging site first. The server needs:

- PHP 8.4.1 or newer, with the `gd` and `exif` extensions (they remove location data from photos, S5);
- MySQL 8 or MariaDB 10.6 or newer, with one database and one user;
- uploads up to 6 MB per file and 32 MB per form, and 256 MB of memory or more, so that a 5 MB phone photo works (P5);
- a cron job every minute, for emails, the nightly backup and the working-day count;
- SSH access for deployment, and `mysqldump` for the nightly backup (S7);
- a subdomain with a free SSL certificate (S1);
- outgoing email (see question 3).

To ask: does the company's current hosting offer all of this? If not, who provides and pays for hosting that does? Who gives us SSH access to it?

What it does today: we assume the company's host passes. The deployment guide checks each item on the server before the first upload.

Decision:

### 3. How will emails be sent?

The system sends three kinds of email: alerts to staff about new cases and messages (N1), the acknowledgement with the reference number and PIN to complainants who gave an email (N2, P7), and backup failure notices.

To ask: will they go through the company's own mail server, or a sending service the company pays for? Who gives us the mail settings? Can the sending domain have SPF and DKIM records, so the emails do not land in spam?

What it does today: no email leaves a test machine. Staging needs a working sending address to test the acknowledgement.

Decision:

### 4. Can the host turn off or shorten the web server's access logs?

The system itself never stores an IP address or device details (S5). The web server in front of it keeps its own access logs, which record every visitor's IP address, including people sending a grievance.

To ask: can the host turn these logs off for the grievance subdomain, or keep them for a few days only?

What it does today: nothing; the app cannot reach those logs. If the host cannot change them, we record it as a known limit in the technical note.

Decision:

## Form fields

### 5. Which options should the optional "About you" questions offer?

These five questions are optional, and each also offers "Prefer not to say" (P6). If the company or NBI already reports with standard categories, we would use the same ones. The options below are our placeholders:

| Question | Options today (English / Nepali) |
|---|---|
| Who are you? | Worker / कामदार; Contractor or supplier / ठेकेदार वा आपूर्तिकर्ता; Community member / समुदायको सदस्य; Other / अन्य |
| Sex | Female / महिला; Male / पुरुष; Other / अन्य |
| Age group | Under 18 / १८ वर्षभन्दा कम; 18–29 / १८–२९; 30–44 / ३०–४४; 45–59 / ४५–५९; 60 or over / ६० वा सोभन्दा माथि |
| Ethnic group | Brahmin or Chhetri / ब्राह्मण वा क्षेत्री; Janajati / जनजाति; Madhesi / मधेसी; Dalit / दलित; Muslim / मुस्लिम; Other / अन्य |
| Disability | Yes / छ; No / छैन |

To ask: confirm or replace each list, in both languages.

Please settle the lists before real grievances arrive. Wording can change at any time, but adding or removing an option after go-live leaves older answers on the old list.

Decision:

### 7. Is the list of offline channels complete?

Staff record grievances that arrive outside the website, with the channel they came by (A6). The choices today are Phone, Email, Drop box and In person. Grievances from the website are marked "Online form" automatically.

To ask: should any channel be added, such as a letter, a meeting with a manager, or a hotline?

Decision:

### 8. Which calendar for the incident date, and which default language?

Today the form asks for the incident date in the international calendar (AD) in both languages. It uses three dropdowns, day, month and year, and the day can be "I don't remember the day". The public pages open in Nepali, with English one tap away (P2).

To ask:

- Should the Nepali form take the date in Bikram Sambat instead (P5)? If so, should staff screens and exports show it in Bikram Sambat too, or stay in AD?
- Should Nepali stay the default language?

Decision:

## Case handling

### 10. What counts as a working day?

Cases should be handled within 15 working days, and older ones are highlighted for staff (A2).

What it does today:

- Saturday is the only weekly holiday, and no public holidays are entered yet. The Administrator can enter them in the settings.
- The count runs while a case is Received, Under assessment, Under investigation or Reopened.
- It stops at Resolved, Referred / Out of scope and Closed.
- A reopened case counts again from the day it was first received.

To ask:

- Is Saturday the only weekly holiday?
- Which list of public holidays applies, and who keeps it up to date each year?
- Are the statuses that stop the count right?

Decision:

### 11. Are the Nepali names for statuses and priorities right?

The statuses and priorities are the ones the RFQ lists (A4). Complainants see the status on the status page, so the Nepali matters. These are our drafts:

| English | Proposed Nepali | Correct? If not, the right Nepali |
|---|---|---|
| **Statuses** | | |
| Received | प्राप्त | |
| Under assessment | मूल्याङ्कनमा | |
| Under investigation | छानबिनमा | |
| Referred / Out of scope | अन्यत्र पठाइएको / दायराबाहिर | |
| Resolved | समाधान भएको | |
| Closed | बन्द | |
| Reopened | पुनः खोलिएको | |
| **Priorities** | | |
| Low | न्यून | |
| Medium | मध्यम | |
| High | उच्च | |
| Critical | अति गम्भीर | |

Decision:

### 12. Who may export case data, and who may lift a restriction?

What it does today:

- Every staff role can export the cases it can see, to Excel or CSV, without names or contact details. Only the Administrator can choose to include them (N4).
- A case is restricted when its category is restricted or when staff mark it so. Only the Administrator can remove a mark that staff set (A5).

To ask: are these the right rules?

Decision:

### 13. Who is told about new cases and replies?

What it does today:

- Alerts about new cases and new complainant messages go to the staff who can see the case: focal persons for ordinary cases, restricted-case officers for restricted ones (N1). The Administrator gets none, and nor does a staff member about a case they entered themselves.
- If the company has no active restricted-case officer, nobody is told about a restricted case. Before go-live we check that at least one exists.
- A complainant learns about a staff reply only by checking the status page. The RFQ does not ask for a reply email.

To ask:

- Should the Administrator also get new-case alerts, or at least the restricted ones when there is no restricted-case officer?
- Should a complainant who gave an email be told when staff reply? We suggest the email says only that there is a reply, not what it says.

Decision:

### 14. Which language for the staff screens?

The RFQ asks for Nepali and English on the public side (P2). The staff screens are in English today.

To ask: should they be in English, in Nepali, or in both with a switch for each user?

Decision:

### 16. How do people who complain offline follow their grievance?

Staff enter a grievance that came by phone, email, drop box or in person (A6). It gets a reference number and a PIN like an online one, shown once on a slip that staff can print. With both, the complainant can check the case and read replies on the status page. How the two reach the complainant depends on the channel:

| Channel | How they get the reference and PIN today |
|---|---|
| In person | The printed slip |
| Email | The acknowledgement email, sent automatically |
| Phone | Staff read them out; the system cannot send text messages |
| Drop box | Only if the note has contact details; an anonymous note leaves nobody to give them to |

To ask:

- For the drop box: should the box hold a numbered form with a tear-off part, so the person can follow up? Or are drop-box grievances one-way?
- Should people who complained by phone or in person use the status page, or should staff keep them updated by phone or in person? This decides whether staff answer them on the status page or note the call on the case.
- The slip is in English today. Should it be in Nepali or in both languages, and what should it say? For example the status page address or the QR code.
- The optional "About you" questions (P6) are not asked for offline grievances, so those columns are empty in the export for them. Should staff ask them?

What it does today: staff choose the complainant's language, Nepali or English, and the acknowledgement email is sent in it. If the complainant remembers only the month, only the month and year are kept, as on the online form.

Decision:
