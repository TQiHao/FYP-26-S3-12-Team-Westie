SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS unibee;
USE unibee;

-- ============================================
-- 1. UNIVERSITIES & FACULTIES
-- ============================================
INSERT INTO Universities (id, name, institutionType, email, country, postalCode, status, subscriptionPlan)
SELECT 1, 'UniBee Demo University', 'Private', 'info@unibee.com', 'Singapore', '123456', 'active', '2'
WHERE NOT EXISTS (SELECT 1 FROM Universities WHERE id = 1);

INSERT INTO Faculties (id, universityId, name, code)
SELECT 1, 1, 'School of Computing', 'SOC'
WHERE NOT EXISTS (SELECT 1 FROM Faculties WHERE id = 1);

INSERT INTO Programmes (id, facultyId, name, code, durationYears)
SELECT 1, 1, 'Bachelor of Computer Science', 'BSCS', 3
WHERE NOT EXISTS (SELECT 1 FROM Programmes WHERE id = 1);

-- ============================================
-- 2. USERS
-- ============================================
-- University Admin (PW: Admin123!)
INSERT INTO Users (universityId, email, passwordHash, fullName, role, status)
SELECT 1, 'admin@unibee.com', '$2y$12$dDVSzB.igttdMhMfMQtwhOZZ2nQS9h/y25P0gkZ7r5h/Hj0.kCyjC', 'Sarah Tan', 'university_admin', 'active'
WHERE NOT EXISTS (SELECT 1 FROM Users WHERE email = 'admin@unibee.com');

-- Student (PW: Student123!)
INSERT INTO Users (universityId, email, passwordHash, fullName, role, status)
SELECT 1, 'student@unibee.com', '$2y$12$931ktvH/mTPbXERHh0n9xOGauH28z6aqVMoU6n82/GXicF2s8AH52', 'Evan Lu', 'student', 'active'
WHERE NOT EXISTS (SELECT 1 FROM Users WHERE email = 'student@unibee.com');

-- Lecturer (PW: Lecturer123!)
INSERT INTO Users (universityId, email, passwordHash, fullName, role, status)
SELECT 1, 'lecturer@unibee.com', '$2y$12$JV4yqxR9siqHZfjOdtyurubeNhoK0Of5C4.ZTYpSu6MlwYYzqNqru', 'John Lim', 'lecturer', 'active'
WHERE NOT EXISTS (SELECT 1 FROM Users WHERE email = 'lecturer@unibee.com');

-- Course Coordinator (PW: CC123!)
INSERT INTO Users (universityId, email, passwordHash, fullName, role, status)
SELECT 1, 'cc@unibee.com', '$2y$10$7sgLPQIf7s9R0mtnROivcO/ts2otMgtQ5BmrMLI1/i4CDhhdHnkou', 'CC Chew', 'course_coordinator', 'active'
WHERE NOT EXISTS (SELECT 1 FROM Users WHERE email = 'cc@unibee.com');

-- ============================================
-- 3. MODULES
-- ============================================
INSERT INTO Modules (id, programmeId, name, code, credits, semester)
VALUES 
    (1, 1, 'Data Structures', 'CS201', 6, '1'),
    (2, 1, 'Maths Algorithm', 'MATH255', 6, '1'),
    (3, 1, 'System Security', 'CSIT111', 6, '1')
ON DUPLICATE KEY UPDATE name=VALUES(name), code=VALUES(code);

-- ============================================
-- 4. CLASSES (INCLUDES ACTIVE & INACTIVE CLASSES)
-- ============================================
-- CS201 - Active Class L1
INSERT INTO Classes (id, moduleId, staffId, className, classCode, dayOfWeek, startTime, endTime, room, academicYear, semester, status)
SELECT 1, 1, id, 'CS201-L1', 'CS201-L1', 'mon', '09:00:00', '12:00:00', 'B201', '2026/2027', '1', 'active'
FROM Users WHERE email = 'lecturer@unibee.com'
ON DUPLICATE KEY UPDATE className=VALUES(className), status='active';

-- CS201 - Inactive/Suspended Class L2 (To test sorting order)
INSERT INTO Classes (id, moduleId, staffId, className, classCode, dayOfWeek, startTime, endTime, room, academicYear, semester, status)
SELECT 2, 1, id, 'CS201-L2', 'CS201-L2', 'fri', '14:00:00', '17:00:00', 'B202', '2026/2027', '1', 'suspended'
FROM Users WHERE email = 'lecturer@unibee.com'
ON DUPLICATE KEY UPDATE className=VALUES(className), status='suspended';

-- MATH255 - Active Class L1
INSERT INTO Classes (id, moduleId, staffId, className, classCode, dayOfWeek, startTime, endTime, room, academicYear, semester, status)
SELECT 3, 2, id, 'MATH255-L1', 'MATH255-L1', 'tue', '10:00:00', '13:00:00', 'C101', '2026/2027', '1', 'active'
FROM Users WHERE email = 'lecturer@unibee.com'
ON DUPLICATE KEY UPDATE className=VALUES(className), status='active';

-- CSIT111 - Active Class L1
INSERT INTO Classes (id, moduleId, staffId, className, classCode, dayOfWeek, startTime, endTime, room, academicYear, semester, status)
SELECT 4, 3, id, 'CSIT111-L1', 'CSIT111-L1', 'thu', '10:00:00', '13:00:00', 'D3', '2026/2027', '1', 'active'
FROM Users WHERE email = 'lecturer@unibee.com'
ON DUPLICATE KEY UPDATE className=VALUES(className), status='active';

-- ============================================
-- 5. TIMETABLE ENTRIES
-- ============================================
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

-- ============================================
-- 6. EVENTS
-- ============================================
INSERT IGNORE INTO Events (id, universityId, createdBy, title, description, location, startDatetime, endDatetime, capacity, status)
VALUES 
(101, 1, 1, 'AI Innovation Seminar', 'Monday CS201 Data Structures class session.', 'Auditorium A', '2026-09-28 10:00:00', '2026-09-28 11:30:00', 50, 'active'),
(102, 1, 1, 'Algorithms Workshop', 'Tuesday MATH255 class session.', 'Lab C101', '2026-09-29 10:30:00', '2026-09-29 12:00:00', 30, 'active');

SET FOREIGN_KEY_CHECKS = 1;