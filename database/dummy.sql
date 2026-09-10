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
    SELECT 1 FROM Users
    WHERE email = 'admin@unibee.com'
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
    SELECT 1 FROM Users
    WHERE email = 'student@unibee.com'
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
    SELECT 1 FROM Users
    WHERE email = 'lecturer@unibee.com'
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
    SELECT 1 FROM Users
    WHERE email = 'cc@unibee.com'
);

-- System Admin (PW: systemadmin123!)
INSERT INTO systemadmins
(email, passwordHash, fullName, status, createdAt, updatedAt)
SELECT
    'sysadmin@unibee.com',
    '$2y$10$v35y3AhNTMSDRXMbBBD0oOHqRJNYZq8dgWDqrB.YqQpme9NFzM6s.',
    'James Tan',
    'active',
    NOW(),
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM systemadmins
    WHERE email = 'sysadmin@unibee.com'
);