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
INSERT INTO Users (universityId, email, passwordHash, fullName, role, status)
SELECT 1, 'admin@unibee.com', '$2y$12$dDVSzB.igttdMhMfMQtwhOZZ2nQS9h/y25P0gkZ7r5h/Hj0.kCyjC', 'Sarah Tan', 'university_admin', 'active'
WHERE NOT EXISTS (SELECT 1 FROM Users WHERE email = 'admin@unibee.com');

INSERT INTO Users (universityId, email, passwordHash, fullName, role, status)
SELECT 1, 'student@unibee.com', '$2y$12$931ktvH/mTPbXERHh0n9xOGauH28z6aqVMoU6n82/GXicF2s8AH52', 'Evan Lu', 'student', 'active'
WHERE NOT EXISTS (SELECT 1 FROM Users WHERE email = 'student@unibee.com');

INSERT INTO Users (universityId, email, passwordHash, fullName, role, status)
SELECT 1, 'lecturer@unibee.com', '$2y$12$JV4yqxR9siqHZfjOdtyurubeNhoK0Of5C4.ZTYpSu6MlwYYzqNqru', 'John Lim', 'lecturer', 'active'
WHERE NOT EXISTS (SELECT 1 FROM Users WHERE email = 'lecturer@unibee.com');

INSERT INTO Users (universityId, email, passwordHash, fullName, role, status)
SELECT 1, 'cc@unibee.com', '$2y$10$7sgLPQIf7s9R0mtnROivcO/ts2otMgtQ5BmrMLI1/i4CDhhdHnkou', 'CC Chew', 'course_coordinator', 'active'
WHERE NOT EXISTS (SELECT 1 FROM Users WHERE email = 'cc@unibee.com');

-- ============================================
-- 3. FACILITIES (CLASSROOMS)
-- ============================================
INSERT INTO Facilities (universityId, name, roomCode, type, description, location, blockFloor, capacity, status)
VALUES
    (1, 'Lecture Room B201', 'B201', 'classroom', 'Standard lecture room', 'Block B', 'B2', 70, 'active'),
    (1, 'Lecture Room B202', 'B202', 'classroom', 'Standard lecture room', 'Block B', 'B2', 70, 'active'),
    (1, 'Lecture Room B203', 'B203', 'classroom', 'Standard lecture room', 'Block B', 'B2', 60, 'active'),
    (1, 'Lecture Room C101', 'C101', 'classroom', 'Standard lecture room', 'Block C', 'C1', 80, 'active'),
    (1, 'Lecture Room D3', 'D3', 'classroom', 'Small lecture room', 'Block D', 'D1', 50, 'active'),
    (1, 'Lecture Room A101', 'A101', 'classroom', 'Large lecture hall', 'Block A', 'A1', 150, 'active'),
    (1, 'Lecture Room A102', 'A102', 'classroom', 'Large lecture hall', 'Block A', 'A1', 120, 'active'),
    (1, 'Lecture Room E201', 'E201', 'classroom', 'Computer lab classroom', 'Block E', 'E2', 40, 'active')
ON DUPLICATE KEY UPDATE name = VALUES(name), capacity = VALUES(capacity);

-- ============================================
-- 4. MODULES
-- ============================================
INSERT INTO Modules (id, programmeId, name, code, credits, semester)
VALUES 
    (1, 1, 'Data Structures', 'CS201', 6, '1'),
    (2, 1, 'Maths Algorithm', 'MATH255', 6, '1'),
    (3, 1, 'System Security', 'CSIT111', 6, '1')
ON DUPLICATE KEY UPDATE name=VALUES(name), code=VALUES(code);

-- ============================================
-- 5. CLASSES (INCLUDES ACTIVE & INACTIVE CLASSES)
-- ============================================
INSERT INTO Classes (id, moduleId, staffId, className, classCode, dayOfWeek, startTime, endTime, room, academicYear, semester, status)
SELECT 1, 1, id, 'CS201-L1', 'CS201-L1', 'mon', '09:00:00', '12:00:00', 'B201', '2026/2027', '1', 'active'
FROM Users WHERE email = 'lecturer@unibee.com'
ON DUPLICATE KEY UPDATE className=VALUES(className), status='active';

INSERT INTO Classes (id, moduleId, staffId, className, classCode, dayOfWeek, startTime, endTime, room, academicYear, semester, status)
SELECT 2, 1, id, 'CS201-L2', 'CS201-L2', 'fri', '14:00:00', '17:00:00', 'B202', '2026/2027', '1', 'suspended'
FROM Users WHERE email = 'lecturer@unibee.com'
ON DUPLICATE KEY UPDATE className=VALUES(className), status='suspended';

INSERT INTO Classes (id, moduleId, staffId, className, classCode, dayOfWeek, startTime, endTime, room, academicYear, semester, status)
SELECT 3, 2, id, 'MATH255-L1', 'MATH255-L1', 'tue', '10:00:00', '13:00:00', 'C101', '2026/2027', '1', 'active'
FROM Users WHERE email = 'lecturer@unibee.com'
ON DUPLICATE KEY UPDATE className=VALUES(className), status='active';

INSERT INTO Classes (id, moduleId, staffId, className, classCode, dayOfWeek, startTime, endTime, room, academicYear, semester, status)
SELECT 4, 3, id, 'CSIT111-L1', 'CSIT111-L1', 'thu', '10:00:00', '13:00:00', 'D3', '2026/2027', '1', 'active'
FROM Users WHERE email = 'lecturer@unibee.com'
ON DUPLICATE KEY UPDATE className=VALUES(className), status='active';

-- ============================================
-- 6. TIMETABLE ENTRIES
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
-- 6b. STUDENT ENROLMENTS（for student Evan Lu)
-- ============================================
INSERT INTO StudentEnrolments (studentId, classId, enrolledBy, status)
SELECT u.id, 1, u.id, 'enrolled' FROM Users u WHERE u.email = 'student@unibee.com';

INSERT INTO StudentEnrolments (studentId, classId, enrolledBy, status)
SELECT u.id, 3, u.id, 'enrolled' FROM Users u WHERE u.email = 'student@unibee.com';

INSERT INTO StudentEnrolments (studentId, classId, enrolledBy, status)
SELECT u.id, 4, u.id, 'enrolled' FROM Users u WHERE u.email = 'student@unibee.com';

-- ============================================
-- 7. EVENTS  
-- ============================================
INSERT IGNORE INTO Events (id, universityId, createdBy, title, description, location, startDatetime, endDatetime, capacity, status)
VALUES 
(101, 1, 1, 'AI Innovation Seminar', 'A seminar on the latest AI trends and innovations.', 'Auditorium A',
    '2026-12-10 10:00:00', '2026-12-10 11:30:00', 50, 'active'),
(102, 1, 1, 'Algorithms Workshop', 'Hands-on workshop on advanced algorithm design.', 'Lab C101',
    '2026-12-15 14:00:00', '2026-12-15 15:30:00', 30, 'active');

-- ============================================
-- 8. BSCS STUDENTS (S1 2026/2027)
-- ============================================
INSERT INTO Users (universityId, email, passwordHash, fullName, role, status)
VALUES
(1, 'alice.tan@unibee.com', '$2y$12$931ktvH/mTPbXERHh0n9xOGauH28z6aqVMoU6n82/GXicF2s8AH52', 'Alice Tan Wei Ling', 'student', 'active'),
(1, 'ben.lim@unibee.com', '$2y$12$931ktvH/mTPbXERHh0n9xOGauH28z6aqVMoU6n82/GXicF2s8AH52', 'Ben Lim Jun Hao', 'student', 'active'),
(1, 'chloe.ng@unibee.com', '$2y$12$931ktvH/mTPbXERHh0n9xOGauH28z6aqVMoU6n82/GXicF2s8AH52', 'Chloe Ng Hui Xuan', 'student', 'active'),
(1, 'daniel.lee@unibee.com', '$2y$12$931ktvH/mTPbXERHh0n9xOGauH28z6aqVMoU6n82/GXicF2s8AH52', 'Daniel Lee Zhi Wei', 'student', 'active'),
(1, 'elaine.wong@unibee.com', '$2y$12$931ktvH/mTPbXERHh0n9xOGauH28z6aqVMoU6n82/GXicF2s8AH52', 'Elaine Wong Mei Ling', 'student', 'active'),
(1, 'faris.hassan@unibee.com', '$2y$12$931ktvH/mTPbXERHh0n9xOGauH28z6aqVMoU6n82/GXicF2s8AH52', 'Faris Hassan', 'student', 'active'),
(1, 'grace.tan@unibee.com', '$2y$12$931ktvH/mTPbXERHh0n9xOGauH28z6aqVMoU6n82/GXicF2s8AH52', 'Grace Tan Jia Yi', 'student', 'active'),
(1, 'henry.goh@unibee.com', '$2y$12$931ktvH/mTPbXERHh0n9xOGauH28z6aqVMoU6n82/GXicF2s8AH52', 'Henry Goh Kai Ming', 'student', 'active'),
(1, 'irene.tan@unibee.com', '$2y$12$931ktvH/mTPbXERHh0n9xOGauH28z6aqVMoU6n82/GXicF2s8AH52', 'Irene Tan Pei Shan', 'student', 'active'),
(1, 'jason.chew@unibee.com', '$2y$12$931ktvH/mTPbXERHh0n9xOGauH28z6aqVMoU6n82/GXicF2s8AH52', 'Jason Chew Wei Jie', 'student', 'active'),
(1, 'karen.lim@unibee.com', '$2y$12$931ktvH/mTPbXERHh0n9xOGauH28z6aqVMoU6n82/GXicF2s8AH52', 'Karen Lim Xin Yi', 'student', 'active'),
(1, 'liam.teo@unibee.com', '$2y$12$931ktvH/mTPbXERHh0n9xOGauH28z6aqVMoU6n82/GXicF2s8AH52', 'Liam Teo Jun Kai', 'student', 'active'),
(1, 'may.ng@unibee.com', '$2y$12$931ktvH/mTPbXERHh0n9xOGauH28z6aqVMoU6n82/GXicF2s8AH52', 'May Ng Li Ting', 'student', 'active'),
(1, 'nathan.koh@unibee.com', '$2y$12$931ktvH/mTPbXERHh0n9xOGauH28z6aqVMoU6n82/GXicF2s8AH52', 'Nathan Koh Wei Ming', 'student', 'active'),
(1, 'olivia.tan@unibee.com', '$2y$12$931ktvH/mTPbXERHh0n9xOGauH28z6aqVMoU6n82/GXicF2s8AH52', 'Olivia Tan Zi Xuan', 'student', 'active'),
(1, 'peter.wong@unibee.com', '$2y$12$931ktvH/mTPbXERHh0n9xOGauH28z6aqVMoU6n82/GXicF2s8AH52', 'Peter Wong Chun Hong', 'student', 'active'),
(1, 'queenie.lim@unibee.com', '$2y$12$931ktvH/mTPbXERHh0n9xOGauH28z6aqVMoU6n82/GXicF2s8AH52', 'Queenie Lim Hui Min', 'student', 'active'),
(1, 'ryan.tan@unibee.com', '$2y$12$931ktvH/mTPbXERHh0n9xOGauH28z6aqVMoU6n82/GXicF2s8AH52', 'Ryan Tan Kai En', 'student', 'active'),
(1, 'sophia.lee@unibee.com', '$2y$12$931ktvH/mTPbXERHh0n9xOGauH28z6aqVMoU6n82/GXicF2s8AH52', 'Sophia Lee Jia Xuan', 'student', 'active'),
(1, 'tommy.goh@unibee.com', '$2y$12$931ktvH/mTPbXERHh0n9xOGauH28z6aqVMoU6n82/GXicF2s8AH52', 'Tommy Goh Wei Jian', 'student', 'active'),
(1, 'ursula.ng@unibee.com', '$2y$12$931ktvH/mTPbXERHh0n9xOGauH28z6aqVMoU6n82/GXicF2s8AH52', 'Ursula Ng Xin Hui', 'student', 'active'),
(1, 'victor.tan@unibee.com', '$2y$12$931ktvH/mTPbXERHh0n9xOGauH28z6aqVMoU6n82/GXicF2s8AH52', 'Victor Tan Jun Wei', 'student', 'active'),
(1, 'wendy.chew@unibee.com', '$2y$12$931ktvH/mTPbXERHh0n9xOGauH28z6aqVMoU6n82/GXicF2s8AH52', 'Wendy Chew Mei Xuan', 'student', 'active'),
(1, 'xavier.lim@unibee.com', '$2y$12$931ktvH/mTPbXERHh0n9xOGauH28z6aqVMoU6n82/GXicF2s8AH52', 'Xavier Lim Zhi Hao', 'student', 'active'),
(1, 'yuki.tan@unibee.com', '$2y$12$931ktvH/mTPbXERHh0n9xOGauH28z6aqVMoU6n82/GXicF2s8AH52', 'Yuki Tan Hui Ling', 'student', 'active')
ON DUPLICATE KEY UPDATE fullName = VALUES(fullName);

-- ============================================
-- 9. SYSTEM ADMIN
-- ============================================
-- System Admin (PW: SystemAdmin123!)
INSERT INTO SystemAdmins (email, passwordHash, fullName, status)
VALUES
( 'systemadmin@unibee.com', '$2y$12$0yPsP1593ENJj.gDG/5FD.6rva8FZgf0R0BKHW3MuUo0p.yvlWHYy', 'Sarah Tan', 'active');

-- ============================================
-- 10. LICENSES
-- ============================================
INSERT INTO Licenses (id, name, durationYears, description, status)
VALUES
(1, '1 Year License', 1, 'Standard 1-year subscription', 'active'),
(2, '2 Year License', 2, 'Standard 2-year subscription', 'active'),
(3, '5 Year License', 5, 'Standard 5-year subscription', 'active')
ON DUPLICATE KEY UPDATE name = VALUES(name), durationYears = VALUES(durationYears);

INSERT INTO UniversityLicenses (universityId, licenseId, startDate, expiryDate, status)
SELECT 1, 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 'active'
WHERE NOT EXISTS (SELECT 1 FROM UniversityLicenses WHERE universityId = 1);

SET FOREIGN_KEY_CHECKS = 1;