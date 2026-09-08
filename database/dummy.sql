INSERT INTO Users
(universityId, email, passwordHash, fullName, role, status, createdAt, updatedAt, lastLogin)
VALUES

-- University Admin
(1,
 'admin@unibee.com',
 '$2y$12$dDVSzB.igttdMhMfMQtwhOZZ2nQS9h/y25P0gkZ7r5h/Hj0.kCyjC',
 'Sarah Tan',
 'university_admin',
 'active',
 NOW(),
 NOW(),
 NULL),

-- Student
(1,
 'student@unibee.com',
 '$2y$12$931ktvH/mTPbXERHh0n9xOGauH28z6aqVMoU6n82/GXicF2s8AH52',
 'Qi Hao',
 'student',
 'active',
 NOW(),
 NOW(),
 NULL),

-- Lecturer
(1,
 'lecturer@unibee.com',
 '$2y$12$JV4yqxR9siqHZfjOdtyurubeNhoK0Of5C4.ZTYpSu6MlwYYzqNqru',
 'John Lim',
 'lecturer',
 'active',
 NOW(),
 NOW(),
 NULL);