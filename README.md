# GABAY: Guidance and Counsel Appointment and Consultation System

Campus mental health web app (PHP 8.1+ and MySQL/MariaDB, runs under XAMPP). Students book OSDS counselor sessions and use an anonymous peer support space.

## Run it under XAMPP
1. Copy this folder to `C:\xampp\htdocs\gabay` (macOS/Linux: `htdocs/gabay`).
2. Start Apache and MySQL in the XAMPP control panel.
3. Open phpMyAdmin, choose Import, and import `database/gabay.sql`. It creates the `gabay` database, the admin account and three counselor accounts (logins are in the README).
4. If your folder is not named `gabay`, or MySQL has a password, copy `config.local.example.php` to `config.local.php` and edit it. That file is ignored by Git, so your database login is never committed.
5. Open `http://localhost/gabay/` and log in with one of the demo accounts below.

Demo path: log in as admin, create a counselor, log in as that counselor and add slots, register a student, and book.

## Demo accounts
These are demo logins created by `database/gabay.sql`. They are public in this repository, so never reuse these passwords anywhere real, and change them before any real use.

| Role | Email | Password |
|---|---|---|
| Admin | `admin@gabay.local` | `ChangeMe123!` |
| Counselor | `maria.santos@gabay.local` | `Counselor123!` |
| Counselor | `jose.reyes@gabay.local` | `Counselor123!` |
| Counselor | `ana.cruz@gabay.local` | `Counselor123!` |

Students register themselves on the login page.

## Working on this as a team
1. Clone the repo into `htdocs` (for example `git clone <repo-url> gabay`).
2. Import `database/gabay.sql` into your own local MySQL and add your own `config.local.php` if your setup differs.
3. Make changes on a branch (`git checkout -b feature/your-change`), commit, push, and open a pull request. Avoid committing straight to `main`.
4. If you change the database structure, edit `database/gabay.sql` in the same pull request and say so in the description, so teammates know to re-import.

Each person's database is separate. Only the structure and demo accounts in `gabay.sql` are shared through Git, never real data.

## Starting over
Importing `database/gabay.sql` again deletes the `gabay` database and rebuilds it with only the four starter accounts, so use it whenever you want a clean slate.

## Folder structure
```
gabay/
  config.php            settings, hotline list (placeholders), privacy threshold
  index.php             login and student registration (the only page guests can open)
  logout.php
  database/gabay.sql      fresh-start database (wipes and rebuilds; includes the 4 starter accounts)
  includes/             bootstrap, db (PDO), auth (role guard), helpers (icons, charts), crisis, layout,
                        toolkit, account (shared My account page), community (staff view of the Peer Circle)
  assets/css, assets/js
  student/    dashboard (with Toolkit), appointments, book, mood, peer, account
  counselor/  dashboard (with Toolkit), slots, requests, flagged, community, account
  admin/      dashboard, counselors, admins, community, account
```

## Who can see what
| Data | Student | Counselor | Admin |
|---|---|---|---|
| Own appointments and notes to counselor | Yes | | |
| Requests booked with them | | Yes (anonymous: reference code only) | No |
| Private session notes | No | Own only | No |
| Mood entries | Own only | No | Weekly averages only |
| Peer Circle | Yes | Read all, aliases only, can hide | Read all, aliases only, cannot hide |
| Counselor account details | | Own | Name, title, email (hidden until shown); can edit and reset password |
| Create, deactivate or delete accounts | | | Create counselors and admins; deactivate or reactivate counselors; delete admins |

Clicking Create my account opens a privacy notice pop-up (citing the Data Privacy Act of 2012, RA 10173) whose Continue button stays off until the student ticks the agreement box (the time is saved in `users.consented_at`). Staff can read the Peer Circle to keep students safe, and students are told this on those pages. Staff never see real names there, only aliases. Passwords are hashed, so nobody (including admins) can view them. Admins reset a password instead, and the person must choose a new one at next login.

Each page calls `require_role()` first. Counselor queries join `slots.counselor_id`, so a counselor cannot act on another counselor's requests. For anonymous bookings the name and email are not selected from the database at all.

## How the main protections work
- **No double booking:** booking runs in a transaction that flips `slots.is_taken` from 0 to 1 only if it is still 0. `appointments.active_slot_id` is also UNIQUE, so MySQL refuses a second active booking even if the code were wrong. Cancelling or declining clears both.
- **Crisis language:** `includes/crisis.php` matches English and Filipino phrases on posts and replies. A match flags the post for counselors and immediately shows the student a supportive message with the hotlines.
- **Admin privacy:** admin queries are counts and averages. The weekly mood chart hides weeks with fewer than `MIN_GROUP_SIZE` (3) entries. Set it to 1 for a small demo.
- Passwords use `password_hash`. All queries are parameterized. Output is escaped.

## Known gaps (state these in the documentation or demo)
- Counselor session notes are stored as plain text, not encrypted.
- No CSRF protection on forms.
- No login rate limiting or account lockout. No password reset or account deactivation.
- Crisis detection is keyword matching (`includes/crisis.php`, easy to extend). It catches common disguises such as "4yaW k0 n4", spaced-out letters, repeated letters and look-alike characters, but it still misses some posts and flags some harmless ones ("ayoko na" and "di ko na kaya" are common slang). Very short phrases written with letters spread apart can slip through.
- Flagged posts stay visible to peers until a counselor hides them.
- Counselors see the real name and email of non-anonymous students.
- Sessions are not time-limited beyond the PHP default.

## Before the demo
- **Hotline numbers are placeholders.** Verify every number in `config.php` (`hotlines()`) with the school and the agencies, then set `HOTLINES_VERIFIED` to `true`. Until then the app shows "pending verification" under each list.
- Set `PRIVACY_CONTACT` in `config.php` (the contact shown in the privacy notice) and have the school's Data Protection Officer review the notice wording. The notice says students can ask to see, correct or delete their data, so there must be an actual process for that (the app has no student delete button).
- Set `SCHOOL_EMAIL_DOMAIN` in `config.php` to restrict registration to your school's email domain.
- Fonts load from Google Fonts. Offline, the app falls back to system fonts.

## Optional next steps
Public crisis hotline page (no login), admin account deactivation and password reset, CSRF tokens, encrypted notes.

Icons: Lucide (ISC licence), inlined as SVG.
