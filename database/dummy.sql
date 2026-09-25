USE unibee;

-- University Admin (PW: Admin123!)
INSERT INTO Users
(universityId, email, passwordHash, fullName, role, status, createdAt, updatedAt, lastLogin)
SELECT
    1,
    'admin@unibee.com',
    '$2y$12$dDVSzB.igttdMhMfMQtwhOZZ2nQS9h/y25P0gkZ7r5h/Hj0.kCyjC',
    'Sarah Tan',
    'university_admin',
    'active',
    NOW(),
    NOW(),
    NULL
WHERE NOT EXISTS (
    SELECT 1 FROM Users WHERE email = 'admin@unibee.com'
);

-- Student (PW: Student123!)
INSERT INTO Users
(universityId, email, passwordHash, fullName, role, status, createdAt, updatedAt, lastLogin)
SELECT
    1,
    'student@unibee.com',
    '$2y$12$931ktvH/mTPbXERHh0n9xOGauH28z6aqVMoU6n82/GXicF2s8AH52',
    'Evan Lu',
    'student',
    'active',
    NOW(),
    NOW(),
    NULL
WHERE NOT EXISTS (
    SELECT 1 FROM Users WHERE email = 'student@unibee.com'
);

-- Lecturer (PW: Lecturer123!)
INSERT INTO Users
(universityId, email, passwordHash, fullName, role, status, createdAt, updatedAt, lastLogin)
SELECT
    1,
    'lecturer@unibee.com',
    '$2y$12$JV4yqxR9siqHZfjOdtyurubeNhoK0Of5C4.ZTYpSu6MlwYYzqNqru',
    'John Lim',
    'lecturer',
    'active',
    NOW(),
    NOW(),
    NULL
WHERE NOT EXISTS (
    SELECT 1 FROM Users WHERE email = 'lecturer@unibee.com'
);

-- Course Coordinator (PW: CC123!)
INSERT INTO Users
(universityId, email, passwordHash, fullName, role, status, createdAt, updatedAt, lastLogin)
SELECT
    1,
    'cc@unibee.com',
    '$2y$10$7sgLPQIf7s9R0mtnROivcO/ts2otMgtQ5BmrMLI1/i4CDhhdHnkou',
    'CC Chew',
    'course_coordinator',
    'active',
    NOW(),
    NOW(),
    NULL
WHERE NOT EXISTS (
    SELECT 1 FROM Users WHERE email = 'cc@unibee.com'
);

-- University
INSERT INTO Universities (id, name, institutionType, email, country, postalCode, status, subscriptionPlan)
SELECT 1, 'UniBee Demo University', 'Private', 'info@unibee.com', 'Singapore', '123456', 'active', '2'
WHERE NOT EXISTS (SELECT 1 FROM Universities WHERE id = 1);

-- Faculties & Programmes
INSERT INTO Faculties (id, universityId, name, code)
SELECT 1, 1, 'School of Computing', 'SOC'
WHERE NOT EXISTS (SELECT 1 FROM Faculties WHERE id = 1);

INSERT INTO Programmes (id, facultyId, name, code, durationYears)
SELECT 1, 1, 'Bachelor of Computer Science', 'BSCS', 3
WHERE NOT EXISTS (SELECT 1 FROM Programmes WHERE id = 1);

-- Modules
INSERT INTO Modules (id, programmeId, name, code, credits, semester)
VALUES 
    (1, 1, 'Data Structures', 'CS201', 6, '1'),
    (2, 1, 'Maths Algorithm', 'MATH255', 6, '1'),
    (3, 1, 'System Security', 'CSIT111', 6, '1')
ON DUPLICATE KEY UPDATE name=VALUES(name), code=VALUES(code);

-- Classes (3-Hour Sessions) - Handles existing duplicates safely
INSERT INTO Classes (moduleId, staffId, className, classCode, dayOfWeek, startTime, endTime, room, academicYear, semester)
SELECT 1, id, 'CS201-L1', 'CS201-L1', 'mon', '09:00:00', '12:00:00', 'B201', '2026/2027', '1'
FROM Users WHERE email = 'lecturer@unibee.com'
ON DUPLICATE KEY UPDATE className=VALUES(className);

INSERT INTO Classes (moduleId, staffId, className, classCode, dayOfWeek, startTime, endTime, room, academicYear, semester)
SELECT 2, id, 'MATH255-L1', 'MATH255-L1', 'tue', '10:00:00', '13:00:00', 'C101', '2026/2027', '1'
FROM Users WHERE email = 'lecturer@unibee.com'
ON DUPLICATE KEY UPDATE className=VALUES(className);

INSERT INTO Classes (moduleId, staffId, className, classCode, dayOfWeek, startTime, endTime, room, academicYear, semester)
SELECT 3, id, 'CSIT111-L1', 'CSIT111-L1', 'thu', '10:00:00', '13:00:00', 'D3', '2026/2027', '1'
FROM Users WHERE email = 'lecturer@unibee.com'
ON DUPLICATE KEY UPDATE className=VALUES(className);

-- Timetable Entries - Clears existing entries first to avoid duplicates
DELETE FROM TimetableEntries WHERE userId IN (SELECT id FROM Users WHERE email IN ('student@unibee.com', 'lecturer@unibee.com'));

INSERT INTO TimetableEntries (userId, title, dayOfWeek, startTime, endTime, location)
SELECT id, 'CS201 - Data Structures', 'mon', '09:00:00', '12:00:00', 'B201' 
FROM Users WHERE email IN ('student@unibee.com', 'lecturer@unibee.com');

INSERT INTO TimetableEntries (userId, title, dayOfWeek, startTime, endTime, location)
SELECT id, 'MATH255 - Maths Algorithm', 'tue', '10:00:00', '13:00:00', 'C101' 
FROM Users WHERE email IN ('student@unibee.com', 'lecturer@unibee.com');

INSERT INTO TimetableEntries (userId, title, dayOfWeek, startTime, endTime, location)
SELECT id, 'CSIT111 - System Security', 'thu', '10:00:00', '13:00:00', 'D3' 
FROM Users WHERE email IN ('student@unibee.com', 'lecturer@unibee.com');

-- Dummy Events with Schedule Clash Test Data
INSERT IGNORE INTO Events (id, universityId, createdBy, title, description, location, startDatetime, endDatetime, capacity, status)
VALUES 
(101, 1, 1, 'AI Innovation Seminar (Monday Clash)', 'Overlaps with Monday CS201 Data Structures class.', 'Auditorium A', '2026-09-28 10:00:00', '2026-09-28 11:30:00', 50, 'active'),
(102, 1, 1, 'Algorithms & Math Workshop (Tuesday Clash)', 'Overlaps with Tuesday MATH255 class.', 'Lab C101', '2026-09-29 10:30:00', '2026-09-29 12:00:00', 30, 'active'),
(103, 1, 1, 'System Security Tech Talk (Thursday Clash)', 'Overlaps with Thursday CSIT111 class.', 'Grand Hall', '2026-10-01 11:00:00', '2026-10-01 12:30:00', 100, 'active'),
(104, 1, 1, 'Open Campus Hackathon (Valid Slot)', 'Non-conflicting event on Wednesday afternoon.', 'Atrium, Block A', '2026-09-30 14:00:00', '2026-09-30 17:00:00', 60, 'active');