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
-- 1b. CAMPUS BUILDINGS & FLOORS
--     (required for the navigation test data at the bottom)
-- ============================================
INSERT INTO CampusBuildings (id, universityId, buildingName, buildingCode, description, status)
SELECT 1, 1, 'Block A', 'BLK-A', 'Main academic block', 'active'
WHERE NOT EXISTS (SELECT 1 FROM CampusBuildings WHERE id = 1);

INSERT INTO CampusFloors (id, buildingId, floorName, floorNumber, description, status)
SELECT 1, 1, 'Level 1', 1, 'Ground floor', 'active'
WHERE NOT EXISTS (SELECT 1 FROM CampusFloors WHERE id = 1);

INSERT INTO CampusFloors (id, buildingId, floorName, floorNumber, description, status)
SELECT 2, 1, 'Level 2', 2, 'Second floor', 'active'
WHERE NOT EXISTS (SELECT 1 FROM CampusFloors WHERE id = 2);

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
    (1, 'Lecture Room B201', 'B201', 'lecture hall', 'Standard lecture room', 'Block B', 'B2', 70, 'active'),
    (1, 'Lecture Room B202', 'B202', 'lecture hall', 'Standard lecture room', 'Block B', 'B2', 70, 'active'),
    (1, 'Lecture Room B203', 'B203', 'lecture hall', 'Standard lecture room', 'Block B', 'B2', 60, 'active'),
    (1, 'Lecture Room C101', 'C101', 'lecture hall', 'Standard lecture room', 'Block C', 'C1', 80, 'active'),
    (1, 'Lecture Room D3', 'D3', 'lecture hall', 'Small lecture room', 'Block D', 'D1', 50, 'active'),
    (1, 'Lecture Room A101', 'A101', 'lecture hall', 'Large lecture hall', 'Block A', 'A1', 150, 'active'),
    (1, 'Lecture Room A102', 'A102', 'lecture hall', 'Large lecture hall', 'Block A', 'A1', 120, 'active'),
    (1, 'Lecture Room E201', 'E201', 'lecture hall', 'Computer lab classroom', 'Block E', 'E2', 40, 'active')
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
-- 6b. STUDENT ENROLMENTS (for student Evan Lu)
-- ============================================
INSERT INTO StudentEnrolments (studentId, classId, enrolledBy, status)
SELECT u.id, 1, u.id, 'enrolled' FROM Users u WHERE u.email = 'student@unibee.com';

INSERT INTO StudentEnrolments (studentId, classId, enrolledBy, status)
SELECT u.id, 3, u.id, 'enrolled' FROM Users u WHERE u.email = 'student@unibee.com';

INSERT INTO StudentEnrolments (studentId, classId, enrolledBy, status)
SELECT u.id, 4, u.id, 'enrolled' FROM Users u WHERE u.email = 'student@unibee.com';

-- ============================================
-- 6c. BOOKABLE FACILITIES
-- ============================================
INSERT INTO BookableFacilities
(id, facilityId, isBookable, slotDuration, bookingCapacity, openTime, closeTime, status)
VALUES
(1, 1, TRUE, 60, 70, '08:00:00', '18:00:00', 'available'),
(2, 5, TRUE, 60, 80, '08:00:00', '18:00:00', 'available')
ON DUPLICATE KEY UPDATE
    isBookable = VALUES(isBookable),
    bookingCapacity = VALUES(bookingCapacity),
    openTime = VALUES(openTime),
    closeTime = VALUES(closeTime);

-- ============================================
-- 7. EVENTS
-- ============================================

INSERT IGNORE INTO Events
(id, universityId, createdBy, facilityId, title, description,
 startDatetime, endDatetime, capacity, eventInfo, status)
VALUES
(101, 1, 1, 1, 'AI Innovation Seminar', 'A seminar on the latest AI trends and innovations.', '2026-12-10 10:00:00', '2026-12-10 11:30:00', 50, 'A seminar covering the latest developments in artificial intelligence.', 'active'),
(102, 1, 1, 2, 'Algorithms Workshop', 'Hands-on workshop on advanced algorithm design.', '2026-12-15 14:00:00', '2026-12-15 15:30:00', 30, 'Participants will work through advanced algorithm design exercises.', 'active');

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
INSERT INTO SystemAdmins (email, passwordHash, fullName, status)
VALUES
('systemadmin@unibee.com', '$2y$10$v35y3AhNTMSDRXMbBBD0oOHqRJNYZq8dgWDqrB.YqQpme9NFzM6s.', 'James Low', 'active');

-- ============================================
-- 9b. SYSTEM ADMIN PROFILE
-- ============================================
INSERT INTO SystemAdminProfiles (systemAdminId, contactNumber, position, department)
SELECT id, '+65 92217459', 'System Administrator', 'IT'
FROM SystemAdmins
WHERE email = 'systemadmin@unibee.com'
AND NOT EXISTS (SELECT 1 FROM SystemAdminProfiles WHERE systemAdminId = (SELECT id FROM SystemAdmins WHERE email = 'systemadmin@unibee.com'));

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

-- ============================================
-- 11. LANDING PAGE CONTENT
-- ============================================
INSERT INTO LandingPageContent (sectionKey, title, subtitle, content, imagePath)
VALUES
('hero',
 'Connect Smarter,<br>Bee Smarter.',
 'Your smart campus hub for effortless learning, schedules, and collaboration.',
 NULL,
 '../images/landingpgCampus.avif'),

('feature_admin',
 'Features for University Admin',
 NULL,
 '[{"title":"Manage Facilities Booking","desc":"Manage facilities to enable students to book campus space."},{"title":"Manage University Information","desc":"Manage and update university information to keep campus resources accurate and accessible."}]',
 NULL),

('feature_cc',
 'Features for Course Coordinator',
 NULL,
 '[{"title":"Manage Classes","desc":"Create and manage class information and schedules for students and lecturers."},{"title":"AI Chatbot","desc":"Get quick answers and smart classroom suggestions for class scheduling."}]',
 NULL),

('feature_student',
 'Features for Students',
 NULL,
 '[{"title":"Campus Navigation","desc":"Help navigate campus easily and find classrooms, facilities and other important locations."},{"title":"Organise Study Groups","desc":"Create or join study groups, find students with similar academic interests, and make studying more engaging and productive."}]',
 NULL),

('feature_lecturer',
 'Features for Lecturers',
 NULL,
 '[{"title":"Event Reminder","desc":"Keep lecturers informed with timely reminders about upcoming university events."},{"title":"Participate in events","desc":"Explore upcoming university events and discover new activities, experiences, and opportunities to get involved."}]',
 NULL);

-- ============================================================
-- Navigation test data (for Facility Location Tracking)
-- ============================================================

-- 0. Make sure @l1 and @n_j1 are defined in this session.
SET @l1 := (SELECT f.id FROM CampusFloors f
            JOIN CampusBuildings b ON b.id = f.buildingId
            WHERE b.universityId = 1 AND b.buildingCode = 'BLK-A'
              AND f.floorName = 'Level 1' LIMIT 1);

SET @l2 := (SELECT f.id FROM CampusFloors f
            JOIN CampusBuildings b ON b.id = f.buildingId
            WHERE b.universityId = 1 AND b.buildingCode = 'BLK-A'
              AND f.floorName = 'Level 2' LIMIT 1);

-- 1. Level 1 nodes
INSERT INTO NavigationNodes (floorId, name, type, x, y)
SELECT @l1, 'Main entrance', 'entrance', 30, 150
WHERE NOT EXISTS (SELECT 1 FROM NavigationNodes WHERE floorId = @l1 AND name = 'Main entrance');

INSERT INTO NavigationNodes (floorId, name, type, x, y)
SELECT @l1, 'Junction 1', 'junction', 110, 150
WHERE NOT EXISTS (SELECT 1 FROM NavigationNodes WHERE floorId = @l1 AND name = 'Junction 1');

INSERT INTO NavigationNodes (floorId, name, type, x, y)
SELECT @l1, 'Canteen', 'room', 110, 80
WHERE NOT EXISTS (SELECT 1 FROM NavigationNodes WHERE floorId = @l1 AND name = 'Canteen');

INSERT INTO NavigationNodes (floorId, name, type, x, y)
SELECT @l1, 'Junction 2', 'junction', 210, 150
WHERE NOT EXISTS (SELECT 1 FROM NavigationNodes WHERE floorId = @l1 AND name = 'Junction 2');

INSERT INTO NavigationNodes (floorId, name, type, x, y)
SELECT @l1, 'Lift', 'lift', 210, 80
WHERE NOT EXISTS (SELECT 1 FROM NavigationNodes WHERE floorId = @l1 AND name = 'Lift');

INSERT INTO NavigationNodes (floorId, name, type, x, y)
SELECT @l1, 'Stairs', 'stairs', 290, 150
WHERE NOT EXISTS (SELECT 1 FROM NavigationNodes WHERE floorId = @l1 AND name = 'Stairs');

-- 2. Level 2 nodes
INSERT INTO NavigationNodes (floorId, name, type, x, y)
SELECT @l2, 'Room 201', 'room', 210, 80
WHERE NOT EXISTS (SELECT 1 FROM NavigationNodes WHERE floorId = @l2 AND name = 'Room 201');

INSERT INTO NavigationNodes (floorId, name, type, x, y)
SELECT @l2, 'Room 202', 'room', 110, 150
WHERE NOT EXISTS (SELECT 1 FROM NavigationNodes WHERE floorId = @l2 AND name = 'Room 202');

INSERT INTO NavigationNodes (floorId, name, type, x, y)
SELECT @l2, 'Lift', 'lift', 210, 150
WHERE NOT EXISTS (SELECT 1 FROM NavigationNodes WHERE floorId = @l2 AND name = 'Lift');

INSERT INTO NavigationNodes (floorId, name, type, x, y)
SELECT @l2, 'Stairs', 'stairs', 290, 150
WHERE NOT EXISTS (SELECT 1 FROM NavigationNodes WHERE floorId = @l2 AND name = 'Stairs');

-- 3. Node lookups
SET @n_ent  := (SELECT id FROM NavigationNodes WHERE floorId = @l1 AND name = 'Main entrance');
SET @n_j1   := (SELECT id FROM NavigationNodes WHERE floorId = @l1 AND name = 'Junction 1');
SET @n_can  := (SELECT id FROM NavigationNodes WHERE floorId = @l1 AND name = 'Canteen');
SET @n_j2   := (SELECT id FROM NavigationNodes WHERE floorId = @l1 AND name = 'Junction 2');
SET @n_l1   := (SELECT id FROM NavigationNodes WHERE floorId = @l1 AND name = 'Lift');
SET @n_s1   := (SELECT id FROM NavigationNodes WHERE floorId = @l1 AND name = 'Stairs');
SET @n_r201 := (SELECT id FROM NavigationNodes WHERE floorId = @l2 AND name = 'Room 201');
SET @n_r202 := (SELECT id FROM NavigationNodes WHERE floorId = @l2 AND name = 'Room 202');
SET @n_l2   := (SELECT id FROM NavigationNodes WHERE floorId = @l2 AND name = 'Lift');
SET @n_s2   := (SELECT id FROM NavigationNodes WHERE floorId = @l2 AND name = 'Stairs');

-- 4. Edges (Level 1)
INSERT INTO NavigationEdges (fromNodeId, toNodeId, distanceMeters, isAccessible)
SELECT @n_ent, @n_j1, 8.00, TRUE
WHERE NOT EXISTS (SELECT 1 FROM NavigationEdges WHERE fromNodeId=@n_ent AND toNodeId=@n_j1);

INSERT INTO NavigationEdges (fromNodeId, toNodeId, distanceMeters, isAccessible)
SELECT @n_j1, @n_can, 7.00, TRUE
WHERE NOT EXISTS (SELECT 1 FROM NavigationEdges WHERE fromNodeId=@n_j1 AND toNodeId=@n_can);

INSERT INTO NavigationEdges (fromNodeId, toNodeId, distanceMeters, isAccessible)
SELECT @n_j1, @n_j2, 10.00, TRUE
WHERE NOT EXISTS (SELECT 1 FROM NavigationEdges WHERE fromNodeId=@n_j1 AND toNodeId=@n_j2);

INSERT INTO NavigationEdges (fromNodeId, toNodeId, distanceMeters, isAccessible)
SELECT @n_j2, @n_l1, 7.00, TRUE
WHERE NOT EXISTS (SELECT 1 FROM NavigationEdges WHERE fromNodeId=@n_j2 AND toNodeId=@n_l1);

INSERT INTO NavigationEdges (fromNodeId, toNodeId, distanceMeters, isAccessible)
SELECT @n_j2, @n_s1, 8.00, TRUE
WHERE NOT EXISTS (SELECT 1 FROM NavigationEdges WHERE fromNodeId=@n_j2 AND toNodeId=@n_s1);

-- 5. Edges (Level 2)
INSERT INTO NavigationEdges (fromNodeId, toNodeId, distanceMeters, isAccessible)
SELECT @n_l2, @n_r201, 7.00, TRUE
WHERE NOT EXISTS (SELECT 1 FROM NavigationEdges WHERE fromNodeId=@n_l2 AND toNodeId=@n_r201);

INSERT INTO NavigationEdges (fromNodeId, toNodeId, distanceMeters, isAccessible)
SELECT @n_l2, @n_s2, 8.00, TRUE
WHERE NOT EXISTS (SELECT 1 FROM NavigationEdges WHERE fromNodeId=@n_l2 AND toNodeId=@n_s2);

INSERT INTO NavigationEdges (fromNodeId, toNodeId, distanceMeters, isAccessible)
SELECT @n_s2, @n_r202, 18.00, TRUE
WHERE NOT EXISTS (SELECT 1 FROM NavigationEdges WHERE fromNodeId=@n_s2 AND toNodeId=@n_r202);

INSERT INTO NavigationEdges (fromNodeId, toNodeId, distanceMeters, isAccessible)
SELECT @n_r202, @n_l2, 13.00, TRUE
WHERE NOT EXISTS (SELECT 1 FROM NavigationEdges WHERE fromNodeId=@n_r202 AND toNodeId=@n_l2);

-- 6. Vertical links
INSERT INTO NavigationEdges (fromNodeId, toNodeId, distanceMeters, isAccessible)
SELECT @n_l1, @n_l2, 15.00, TRUE
WHERE NOT EXISTS (SELECT 1 FROM NavigationEdges WHERE fromNodeId=@n_l1 AND toNodeId=@n_l2);

INSERT INTO NavigationEdges (fromNodeId, toNodeId, distanceMeters, isAccessible)
SELECT @n_s1, @n_s2, 10.00, FALSE
WHERE NOT EXISTS (SELECT 1 FROM NavigationEdges WHERE fromNodeId=@n_s1 AND toNodeId=@n_s2);

-- 7. Link event facilities to nodes (so the "Navigate" button works)
INSERT INTO NavigationNodes (floorId, name, type, x, y, facilityId)
SELECT @l1, f.name, 'room', 30, 80, f.id
FROM Facilities f
INNER JOIN BookableFacilities bf ON bf.facilityId = f.id
INNER JOIN Events e ON e.facilityId = bf.id
WHERE f.universityId = 1
  AND NOT EXISTS (
      SELECT 1 FROM NavigationNodes n WHERE n.facilityId = f.id
  );

SET @n_j1 := (SELECT id FROM NavigationNodes WHERE floorId = @l1 AND name = 'Junction 1');

INSERT INTO NavigationEdges (fromNodeId, toNodeId, distanceMeters, isAccessible)
SELECT n.id, @n_j1, 8.00, TRUE
FROM NavigationNodes n
WHERE n.floorId = @l1
  AND n.facilityId IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM NavigationEdges e
      WHERE e.fromNodeId = n.id AND e.toNodeId = @n_j1
  );

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Navigation demo building: "Library Tower"
-- (Extra building for testing the navigation feature end-to-end.)
-- ============================================================

-- 1. Building
INSERT INTO CampusBuildings (universityId, buildingName, buildingCode, description, status)
SELECT 1, 'Library Tower', 'LIB-TOWER', 'Demo building for navigation tests', 'active'
WHERE NOT EXISTS (
    SELECT 1 FROM CampusBuildings WHERE universityId = 1 AND buildingCode = 'LIB-TOWER'
);

SET @lib := (SELECT id FROM CampusBuildings WHERE buildingCode = 'LIB-TOWER' LIMIT 1);

-- 2. Floors
INSERT INTO CampusFloors (buildingId, floorName, floorNumber, description, status)
SELECT @lib, 'Level 1', 1, 'Ground floor', 'active'
WHERE NOT EXISTS (
    SELECT 1 FROM CampusFloors WHERE buildingId = @lib AND floorName = 'Level 1'
);

INSERT INTO CampusFloors (buildingId, floorName, floorNumber, description, status)
SELECT @lib, 'Level 2', 2, 'Upper floor', 'active'
WHERE NOT EXISTS (
    SELECT 1 FROM CampusFloors WHERE buildingId = @lib AND floorName = 'Level 2'
);

SET @lib_l1 := (SELECT id FROM CampusFloors WHERE buildingId = @lib AND floorName = 'Level 1' LIMIT 1);
SET @lib_l2 := (SELECT id FROM CampusFloors WHERE buildingId = @lib AND floorName = 'Level 2' LIMIT 1);

-- 3. Level 1 nodes
INSERT INTO NavigationNodes (floorId, name, type, x, y)
SELECT @lib_l1, 'Main Door', 'entrance', 30, 150
WHERE NOT EXISTS (SELECT 1 FROM NavigationNodes WHERE floorId = @lib_l1 AND name = 'Main Door');

INSERT INTO NavigationNodes (floorId, name, type, x, y)
SELECT @lib_l1, 'Hallway', 'junction', 150, 150
WHERE NOT EXISTS (SELECT 1 FROM NavigationNodes WHERE floorId = @lib_l1 AND name = 'Hallway');

INSERT INTO NavigationNodes (floorId, name, type, x, y)
SELECT @lib_l1, 'Lift', 'lift', 290, 150
WHERE NOT EXISTS (SELECT 1 FROM NavigationNodes WHERE floorId = @lib_l1 AND name = 'Lift');

-- 4. Level 2 nodes
INSERT INTO NavigationNodes (floorId, name, type, x, y)
SELECT @lib_l2, 'Lift', 'lift', 290, 150
WHERE NOT EXISTS (SELECT 1 FROM NavigationNodes WHERE floorId = @lib_l2 AND name = 'Lift');

INSERT INTO NavigationNodes (floorId, name, type, x, y)
SELECT @lib_l2, 'Reading Hall', 'room', 150, 100
WHERE NOT EXISTS (SELECT 1 FROM NavigationNodes WHERE floorId = @lib_l2 AND name = 'Reading Hall');

-- 5. Lookup node IDs
SET @md  := (SELECT id FROM NavigationNodes WHERE floorId = @lib_l1 AND name = 'Main Door');
SET @hw  := (SELECT id FROM NavigationNodes WHERE floorId = @lib_l1 AND name = 'Hallway');
SET @l1  := (SELECT id FROM NavigationNodes WHERE floorId = @lib_l1 AND name = 'Lift');
SET @l2  := (SELECT id FROM NavigationNodes WHERE floorId = @lib_l2 AND name = 'Lift');
SET @rh  := (SELECT id FROM NavigationNodes WHERE floorId = @lib_l2 AND name = 'Reading Hall');

-- 6. Edges (same floor)
INSERT INTO NavigationEdges (fromNodeId, toNodeId, distanceMeters, isAccessible)
SELECT @md, @hw, 12.00, TRUE
WHERE NOT EXISTS (SELECT 1 FROM NavigationEdges WHERE fromNodeId = @md AND toNodeId = @hw);

INSERT INTO NavigationEdges (fromNodeId, toNodeId, distanceMeters, isAccessible)
SELECT @hw, @l1, 14.00, TRUE
WHERE NOT EXISTS (SELECT 1 FROM NavigationEdges WHERE fromNodeId = @hw AND toNodeId = @l1);

INSERT INTO NavigationEdges (fromNodeId, toNodeId, distanceMeters, isAccessible)
SELECT @l2, @rh, 15.00, TRUE
WHERE NOT EXISTS (SELECT 1 FROM NavigationEdges WHERE fromNodeId = @l2 AND toNodeId = @rh);

-- 7. Cross-floor edge (Lift ↔ Lift)
INSERT INTO NavigationEdges (fromNodeId, toNodeId, distanceMeters, isAccessible)
SELECT @l1, @l2, 15.00, TRUE
WHERE NOT EXISTS (
    SELECT 1 FROM NavigationEdges
    WHERE (fromNodeId = @l1 AND toNodeId = @l2)
       OR (fromNodeId = @l2 AND toNodeId = @l1)
);