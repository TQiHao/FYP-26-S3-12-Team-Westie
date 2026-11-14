# FYP-26-S3-12-Team-Westie
Privacy-Preserving AI Assistant for Smart Campuses

================================================================================
UNIBEE: SMART CAMPUS WEB APPLICATION	
================================================================================

Project Group : FYP-26-S3-12
Members       : Chu Koh Hin, Ong Shi Yan, Toh Qi Hao, Koh Li Er

A web-based smart campus platform for universities. UniBee ties university 
admins, course coordinators, students, lecturers and system admins into
one system: academics, facility booking, campus events, an AI chatbot,
campus navigation, and full admin tooling.

Built as a Final Year Project (FYP-26-S3-12).
Platform: PHP 8 · MySQL · Vanilla JavaScript · Open-source Phi-3 (via
Hugging Face) for AI chat.

================================================================================
1. WHAT IS UNIBEE?
================================================================================

UniBee is a web application that lets a university manage its entire
campus digitally. Every user has a role, and every role sees a
role-specific dashboard with the tools and data relevant to them.

We built it around five roles:
	
  - University Admin
  - Course Coordinator
  - Student
  - Lecturer
  - System Admin

Plus one public flow (unregistered visitor) for landing page, signup, and
login.

Each role is intentionally isolated. A student cannot see admin tools. A
lecturer only sees their own classes. A course coordinator can only manage
classes at their own university. This is enforced at the controller layer,
not just the page because controllers are directly reachable via URL.

================================================================================
2. WHAT CAN EACH ROLE DO?
================================================================================

UNREGISTERED VISITOR
  - View landing page
  - Register a university (with verification documents)
  - Log in
  - Reset password

UNIVERSITY ADMIN
  - Upload bulk lists: faculty, programme, module, facility,
    course coordinator, lecturer, student
  - Manage bookable facilities (open/close time, slot duration, status)
  - Manage campus events (create, edit, suspend, upload posters)
  - Manage FAQ database (import from CSV, edit, delete)
  - Upload campus map, buildings, floors, floor plans
  - Edit navigation nodes and edges for campus navigation
  - Renew the university license
  - View facility utilisation reports
  - Use the AI chatbot
  - Submit feedback

COURSE COORDINATOR
  - Create, edit, suspend, and reactivate classes
  - Assign lecturers to classes
  - Enrol students one-by-one or in bulk from a CSV file
  - Schedule exams with venue suggestions
  - Get room suggestions for classes
  - Use the AI chatbot (with class-creation intent)
  - Submit feedback

STUDENT
  - View personal timetable (weekly, with semester and start/end date filtering)
  - View enrolled courses and upcoming exams
  - Create and join study groups (per-module)
  - Book study rooms and gym slots
  - Register for campus events (with schedule-conflict checks)
  - View campus events
  - Use the AI chatbot (with encrypted chat history)
  - Submit feedback
  - Receive notifications

LECTURER
  - View personal timetable (merges personal entries + assigned classes)
  - View assigned modules
  - Register for campus events
  - Use the AI chatbot
  - Submit feedback
  - Receive notifications

SYSTEM ADMIN
  - Approve or reject university registrations
  - Suspend or reactivate universities (with license validation)
  - Manage landing page content (live preview, in-place editing)
  - Deploy new AI model versions (with staged confirmation)
  - View system logs (searchable, filterable)
  - Create and restore database backups
  - Review user feedback (status workflow: new → in_review → resolved)
  - Manage system admin profile

================================================================================
3. TECH STACK
================================================================================

BACKEND
  - PHP 8 (no framework)
  - MySQL 8 (via PDO, prepared statements only)
  - MVC-style layering: Boundary -> Controller -> Entity -> Database

FRONTEND
  - Vanilla HTML5, CSS3, JavaScript (no framework)
  - Fetch API for all AJAX
  - Responsive via CSS media queries

AI
  - Open-source Phi-3 model
  - Hosted on Hugging Face Spaces
  - Called via the Gradio API
  - Fallback: three-layer FAQ search (exact -> TF-IDF -> Phi-3)

ENCRYPTION
  - AES-256-CBC with per-message random IV
  - Applied to all chat history (question + answer)

HOSTING
  - Production: InfinityFree (shared PHP/MySQL hosting)
  - Local dev: XAMPP (Apache + MySQL on port 3307)

================================================================================
4. SYSTEM ARCHITECTURE
================================================================================

Every feature follows the SAME four-layer pattern. Learn this once and the
whole codebase opens up.

    boundary/xxxPage.php
        |
        v   (require_once)
    controller/xxxController.php
        |   - calls requireRole(...) FIRST (auth + crude CSRF)
        |   - validates input
        |   - orchestrates entities
        |
        v   (require_once)
    entity/xxx.php
        |   - holds SQL
        |   - holds data shaping
        |
        v
    database/database.php
        - PDO connection factory

Why this layering?
  - The page has no SQL. Easy to redesign without touching business logic.
  - The controller is the auth boundary. Every controller starts with
    requireRole(...). Controllers are directly reachable via URL, so
    enforcing auth here (not just on the page) is essential.
  - The entity is the only place SQL lives. Easy to audit for injection.

================================================================================
5. DIRECTORY STRUCTURE
================================================================================

    /
    |-- boundary/           User-facing pages (page files with HTML+PHP)
    |-- controller/         Business logic + auth + validation
    |-- entity/             Data-access classes (SQL lives here)
    |-- database/           Database connection (database.php)
    |-- uploads/            User-uploaded files (floor plans, posters, docs)
    |-- csv/                Student list CSV files (per-university folders)
    |-- backups/            Database backup files (denied via .htaccess)
    |-- models/             AI model files + active_model.json
    |-- images/             Static assets
    |-- style.css           Global stylesheet
    |-- script.js           Global scripts
    |-- database.sql        Full schema for a fresh install

================================================================================
6. GETTING STARTED (LOCAL DEV)
================================================================================

PREREQUISITES
  - XAMPP (or similar: Apache + MySQL + PHP 8)
  - A web browser

SETUP STEPS

  1. Place the project inside XAMPP's htdocs folder.

  2. Start Apache and MySQL from the XAMPP control panel.
     IMPORTANT: MySQL must run on port 3307 (matching database.php).

  3. Import the schema:
       - Open phpMyAdmin (http://localhost/phpmyadmin)
       - Create a database named "unibee"
       - Import database.sql

  4. Edit database/database.php to point to your local DB:
       private $host     = 'localhost:3307';
       private $dbname   = 'unibee';
       private $username = 'root';
       private $password = '';

  5. (Optional) Seed the DB with a System Admin:
       INSERT INTO SystemAdmins (email, passwordHash, fullName)
       VALUES ('admin@unibee.com',
               '$2y$10$<your_bcrypt_hash_here>',
               'System Admin');
     Generate the hash with:
       php -r "echo password_hash('Admin@123', PASSWORD_DEFAULT);"

  6. Open http://localhost/<project-folder>/boundary/loginPage.php

================================================================================
7. DEPLOYMENT (InfinityFree + Hugging Face)
================================================================================

This project is designed to run on InfinityFree (shared hosting) with an
AI backend on Hugging Face Spaces.

INFINITYFREE SETUP
  1. Upload all files via the File Manager or FTP.
  2. Update database/database.php with production credentials:
       host     = 'sqlXXX.infinityfree.com'
       dbname   = 'if0_XXXXX_unibee'
       username = 'if0_XXXXX'
       password = '<production password>'
  3. Import database.sql via phpMyAdmin.
  4. Ensure HTTPS is enabled (FREE SSL via InfinityFree control panel).

HUGGING FACE SPACE SETUP
  1. Create a Space running your Phi-3 model with a /respond endpoint
     using the Gradio API format.
  2. Copy the Space URL (e.g. https://username-space.hf.space).
  3. In controller/aiChatbotController.php, update askPhi3() with your
     Space URL:
       $baseUrl = 'https://your-space.hf.space';

KNOWN LIMITATIONS ON INFINITYFREE

  - proc_open is DISABLED on InfinityFree.
    -> The backup feature falls back to DatabaseDump::dump() (pure PHP).
    -> Still works, just slower for large DBs.

  - upload_max_filesize is small (10-20MB).
    -> Uploading a real GGUF model file WILL fail.
    -> The AI model deploy page works but cannot accept full-size models.

  - MySQL port is standard (3306), not 3307.
    -> Update database.php accordingly.

================================================================================
8. DATABASE OVERVIEW
================================================================================

Core tables (33 total). Simplified relationships:

  Universities
    |-- Users                     (login accounts, all roles)
    |     |-- UserProfiles
    |     |-- Students            (student-specific data: year, semester)
    |     |-- StudentEnrolments   (student <-> class)
    |     |-- Notifications
    |     |-- ChatHistory
    |     |-- TimetableEntries
    |     |-- StudyGroupMembers
    |     |-- FacilityBookings
    |     |-- EventRegistrations
    |     |-- UniversityLicenses
    |
    |-- UniversityRegistrations   (pending signups)
    |     |-- RegistrationDocuments
    |     |-- RequestsRegistrationDocument
    |
    |-- UniversityLicenses
    |     |-- Licenses            (plans: 1y, 2y, 5y)
    |
    |-- Faculties
    |     |-- Programmes          (per-coordinator)
    |           |-- Modules
    |                 |-- Classes
    |                       |-- StudentEnrolments
    |                       |-- Exam
    |
    |-- Facilities                (physical rooms)
    |     |-- BookableFacilities  (booking rules per room)
    |           |-- FacilityBookings
    |           |-- Events
    |     |-- FloorPlans
    |     |-- NavigationNodes
    |           |-- NavigationEdges
    |
    |-- FAQs
    |-- Feedback
    |-- StudyGroups
          |-- StudyGroupMembers

System-wide tables (not tied to one university):
  - SystemAdmins
  - SystemAdminProfiles
  - AIModelVersions
  - BackupRecords
  - SystemLogs
  - ChatHistory
  - PasswordResetTokens
  - SystemAdminResetTokens
  - LandingPageContent

KEY CONSTRAINTS
  - Classes: UNIQUE(moduleId, classCode)
  - BookableFacilities: UNIQUE(facilityId)
  - Feedback.rating: CHECK(rating BETWEEN 1 AND 5)
  - Students.userId: UNIQUE
  - Students.studentId: UNIQUE
  - LandingPageContent.sectionKey: UNIQUE

================================================================================
9. SECURITY NOTES
================================================================================

AUTHENTICATION
  - password_hash / password_verify (bcrypt)
  - session_regenerate_id(true) after login (prevents session fixation)
  - requireRole() at the top of EVERY controller
  - Role strings are lowercase and exact ('student', 'course_coordinator', etc.)

SQL INJECTION
  - All queries use PDO prepared statements.
  - No user input is concatenated into SQL (except integers already cast
    via (int)).

XSS
  - Every echoed value uses htmlspecialchars().
  - JSON to JS uses JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP.

CSRF
  - Every POST goes through isSameOriginRequest() (checks Origin/Referer
    against Host). Blocks the simplest cross-site form attacks.
  - FULL session-bound CSRF tokens are used on the two destructive
    endpoints:
      - Backup / restore
      - AI model deploy
    Both use hash_equals() to compare tokens.

FILE UPLOADS
  - Extension whitelist
  - MIME sniffing via finfo
  - Size limit
  - Random 16-byte hex filename on disk (never trust user filename)
  - .htaccess Deny on upload folders

ENCRYPTION AT REST
  - ChatHistory encrypted with AES-256-CBC, random IV per message
  - (See section 10 for honest limitations)

================================================================================
10. AI CHATBOT — DATA PRIVACY
================================================================================

"Is the environment private?"

Honest answer:

  BETTER THAN PUBLIC COMMERCIAL LLMs — YES:
    * Phi-3 is open source.
    * We host it on our own Hugging Face Space.
    * No commercial model-training pipeline collects prompts.
    * No opt-out-from-training checkbox needed.

  BUT NOT FULLY PRIVATE — NO:
    * Hugging Face is still a third-party central server.
    * InfinityFree is another central server.
    * Data in transit (browser <-> InfinityFree <-> HF) needs HTTPS.
    * Data at rest is encrypted (AES-CBC) but the app can read it back.

Exposure per message:
  browser -> InfinityFree (HTTPS) -> Hugging Face Space (HTTPS)
         -> back to InfinityFree -> encrypted into DB

Trusted parties: InfinityFree (host), Hugging Face (AI), you (operator).

TRUE PRIVACY WOULD NEED:
  - Self-hosted Phi-3 on your own GPU hardware.
  - HTTPS everywhere.
  - Key rotation via env vars, not hardcoded.
  - Data minimisation (don't send user PII to the AI — currently we only
    send the question itself, no name/email — good).

================================================================================
11. KNOWN LIMITATIONS
================================================================================

  * Encryption key hardcoded in source — prototype only.
  * AI model file upload cannot complete on InfinityFree (upload size limit).
  * Backup uses pure-PHP fallback on InfinityFree (no proc_open).
  * Soft foreign key between Classes.room and Facilities.roomCode — not
    enforced at DB level.
  * TimetableEntries table is legacy but still merged with Classes on
    lecturer timetable. Should be documented/cleaned up in future.
  * Chat history is per-user; cross-user sharing not implemented.
  * Single-school per user — a user cannot belong to multiple universities.

================================================================================
12. PROJECT GROUP
================================================================================

FYP-26-S3-12
  Chu Koh Hin 
  Ong Shi Yan
  Toh Qi Hao
  Koh Li Er

================================================================================
END OF README.TXT
================================================================================
