# Administrator's guide

For the company's Administrator. Read the user guide for focal persons (`docs/user-guide.md`) first: you can do everything it describes, on every case, restricted ones included. This guide covers what only the Administrator does. The server itself (backups, emails not arriving, error pages) is for the IT contact (`docs/troubleshooting.md`).

## 1. On the first day

1. Sign in with the account you were given and set up two-factor login (user guide, section 2). Keep the recovery codes with the account's entry in the company's password manager.
2. Change the password: **Profile**, in the menu under your initials.
3. Add the staff (section 2 below).
4. Make sure at least one **Restricted-case officer** is active. Without one, new restricted cases come to you alone.
5. Switch off the vendor's account: **Settings → Users**, **Edit**, switch off **Active**. Switch it on again only for support you have agreed to in writing, and off once the work is done.
6. Read the public notice, the categories and the locations, and correct anything that is not right.

## 2. Staff accounts

**Settings → Users.**

- **New user:** **Name**, **Email address**, **Role**, **Active** and a **Password** of at least 8 characters. Give the password in person, never by email or chat. At first sign-in the user sets up two-factor login, then changes the password on **Profile**.
- **Roles:** **Administrator** (every case and every setting), **Focal person** (every case that is not restricted) and **Restricted-case officer** (restricted cases only). Every Administrator sees every case, so keep the role to as few people as possible.
- **A forgotten password:** **Edit**, type a new **Password**, and tell the user in person. Leave the box empty to keep the current one.
- **A lost phone and no recovery codes left:** the panel cannot reset two-factor login. Ask the IT contact, after checking that the request really comes from that person.
- **Someone leaves:** **Edit** and switch off **Active**. They can no longer sign in. Users are never deleted, so the activity log keeps their name. Give their open cases to someone else: the case list shows who each case is **Assigned to**.
- You cannot change your own role or switch off your own account. If you are the only Administrator and lose access, only the IT contact can let you back in.

## 3. Categories and locations

**Settings → Categories** and **Settings → Locations.** Each has a **Name in English**, a **Name in Nepali**, **Offered on the forms** and an **Order** (lowest first in the forms' lists). Categories also have **Restricted**.

- **Nothing is deleted.** To stop offering a category or location, switch off **Offered on the forms**. Cases that already have it keep it.
- **Restricted** sends every case in the category, past ones included, to the restricted-case officers and the Administrator only. Focal persons lose those cases at once, and any of them assigned to a focal person become unassigned.
- **Switching Restricted off** shows the category's past cases to focal persons again, except the cases someone marked restricted by hand. Think before you do it.

## 4. The public notice

**Settings → Public notice** is the text shown above the grievance form, in **Nepali** and **English**. Until you save your own, the company's notice is shown, and the boxes show it as a grey example.

Write plain text. The first paragraph is the heading. Leave an empty line between paragraphs, and start each point of a list with "- " on its own line. Press **Save**.

## 5. Holidays

Focal persons enter the public holidays (user guide, section 4). Check each one in the **Activity log** (section 8), and delete a wrong one: **Settings → Holidays**, **Delete**. Only you can delete a holiday. The working-day counts change overnight.

## 6. Restricted cases

You see every restricted case. Restricted-case officers work them as focal persons work the others.

**Removing a mark made by hand.** Open the case, press **Remove restricted mark**, and give a **Reason**; it is required. The reason is kept as an internal note on the case, in **Notes**, so the focal person can read why once the case is back in their list. A case in a restricted category stays restricted.

## 7. Emails only you get

| Email | When | What to do |
|---|---|---|
| **Grievance set to Critical** | Someone set a case to Critical, which means it needs action today | Open it at once and make sure someone is working on it |
| **Grievance past its target** | An open case has passed 15 working days. Sent once, overnight | Find out why, and who is working on it |
| A new restricted case, or a message on one | No restricted-case officer is active | Work the case yourself, and activate or add a restricted-case officer |

You are not emailed about a change you made yourself.

## 8. The activity log

**Activity log**, in the menu, records who did what and when: **When**, **What**, **Case or setting**, **Who** and **Details**. Filter it by what happened, by **Who**, by **Case reference** or by date. Nobody can change or delete an entry, you included. It holds no case text and no contact details.

Use it to check the holidays focal persons enter, the exports, who opened a case, and why a restriction was removed.

## 9. Exports with names

Your **Export** dialog, on **Cases**, has **Include names and contact details**. No other role has it. Switch it on only when you need the names. The file then holds personal data: keep it off shared computers, and delete it when you are done. Every export is recorded in the activity log, with whether names were included.
