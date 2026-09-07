-- Universities Table
CREATE TABLE Universities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    institutionType VARCHAR(100),
    country VARCHAR(100),
    postalCode VARCHAR(20),
    status VARCHAR(50) DEFAULT 'active',
    suspensionReason TEXT,
    registrationDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    subscriptionPlan VARCHAR(50)
);

-- Users Table
CREATE TABLE Users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    universityId INT,
    email VARCHAR(255) UNIQUE NOT NULL,
    passwordHash VARCHAR(255) NOT NULL,
    fullName VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL,
    status VARCHAR(50) DEFAULT 'active',
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    lastLogin DATETIME,
    FOREIGN KEY (universityId) REFERENCES Universities(id) ON DELETE CASCADE
);

-- User Profiles Table
CREATE TABLE UserProfiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    userId INT UNIQUE NOT NULL,
    contactNumber VARCHAR(50),
    address TEXT,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES Users(id) ON DELETE CASCADE
);

-- System Admins Table
CREATE TABLE SystemAdmins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    passwordHash VARCHAR(255) NOT NULL,
    fullName VARCHAR(255) NOT NULL,
    status VARCHAR(50) DEFAULT 'active',
    suspensionReason TEXT,
    suspendedBy INT NULL,
    suspendedAt DATETIME NULL,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    lastLogin DATETIME,
    FOREIGN KEY (suspendedBy) REFERENCES SystemAdmins(id)
);

-- System Admin Profiles Table
CREATE TABLE SystemAdminProfiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    systemAdminId INT UNIQUE NOT NULL,
    contactNumber VARCHAR(50),
    address TEXT,
    position VARCHAR(100),
    department VARCHAR(100),
    status VARCHAR(50) DEFAULT 'active',
    suspensionReason TEXT,
    suspendedBy INT NULL,
    suspendedAt DATETIME NULL,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (systemAdminId) REFERENCES SystemAdmins(id) ON DELETE CASCADE,
    FOREIGN KEY (suspendedBy) REFERENCES SystemAdmins(id)
);

-- ============================================
-- UNIVERSITY & REGISTRATION TABLES
-- ============================================

-- University Registrations Table
CREATE TABLE UniversityRegistrations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    universityId INT,
    applicantName VARCHAR(255) NOT NULL,
    applicantEmail VARCHAR(255) NOT NULL,
    applicantContact VARCHAR(50),
    status VARCHAR(50) DEFAULT 'pending',
    reviewedBy INT NULL,
    reviewedAt DATETIME NULL,
    rejectionReason TEXT,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (universityId) REFERENCES Universities(id) ON DELETE SET NULL,
    FOREIGN KEY (reviewedBy) REFERENCES SystemAdmins(id) ON DELETE SET NULL
);

-- Registration Documents Table
CREATE TABLE RegistrationDocuments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    registrationId INT NOT NULL,
    documentType VARCHAR(100),
    fileName VARCHAR(255),
    filePath VARCHAR(500),
    status VARCHAR(50) DEFAULT 'pending',
    uploadedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (registrationId) REFERENCES UniversityRegistrations(id) ON DELETE CASCADE
);

-- Registration Document Requests Table
CREATE TABLE RequestsRegistrationDocument (
    id INT PRIMARY KEY AUTO_INCREMENT,
    registrationId INT NOT NULL,
    requestedBy INT NOT NULL,
    requestMessage TEXT,
    status VARCHAR(50) DEFAULT 'pending',
    requestedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    completedAt DATETIME NULL,
    FOREIGN KEY (registrationId) REFERENCES UniversityRegistrations(id) ON DELETE CASCADE,
    FOREIGN KEY (requestedBy) REFERENCES SystemAdmins(id)
);

-- Licenses Table
CREATE TABLE Licenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    durationYears INT NOT NULL,
    description TEXT,
    status VARCHAR(50) DEFAULT 'active',
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updatedBy INT,
    FOREIGN KEY (updatedBy) REFERENCES SystemAdmins(id) ON DELETE SET NULL
);

-- University Licenses Table
CREATE TABLE UniversityLicenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    universityId INT NOT NULL,
    licenseId INT NOT NULL,
    startDate DATE NOT NULL,
    expiryDate DATE NOT NULL,
    status VARCHAR(50) DEFAULT 'active',
    renewedBy INT,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (universityId) REFERENCES Universities(id) ON DELETE CASCADE,
    FOREIGN KEY (licenseId) REFERENCES Licenses(id),
    FOREIGN KEY (renewedBy) REFERENCES Users(id) ON DELETE SET NULL
);

-- ============================================
-- ACADEMIC STRUCTURE TABLES
-- ============================================

-- Faculties Table
CREATE TABLE Faculties (
    id INT PRIMARY KEY AUTO_INCREMENT,
    universityId INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50),
    description TEXT,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (universityId) REFERENCES Universities(id) ON DELETE CASCADE
);

-- Programmes Table
CREATE TABLE Programmes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    facultyId INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50),
    durationYears INT,
    description TEXT,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (facultyId) REFERENCES Faculties(id) ON DELETE CASCADE
);

-- Modules Table
CREATE TABLE Modules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    programmeId INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50),
    credits INT,
    semester ENUM('1', '2', 'special') DEFAULT '1',
    description TEXT,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (programmeId) REFERENCES Programmes(id) ON DELETE CASCADE
);

-- Classes Table
CREATE TABLE Classes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    moduleId INT NOT NULL,
    courseCoordinatorId INT,
    staffId INT,
    className VARCHAR(255) NOT NULL,
    classCode VARCHAR(50),
    dayOfWeek ENUM('mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'),
    startTime TIME,
    endTime TIME,
    room VARCHAR(100),
    capacity INT,
    academicYear VARCHAR(20),
    semester VARCHAR(20),
    status VARCHAR(50) DEFAULT 'active',
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (moduleId) REFERENCES Modules(id) ON DELETE CASCADE,
    FOREIGN KEY (courseCoordinatorId) REFERENCES Users(id) ON DELETE SET NULL,
    FOREIGN KEY (staffId) REFERENCES Users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_class_code_per_module (moduleId, classCode)
);

-- Student Enrolments Table
CREATE TABLE StudentEnrolments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    studentId INT NOT NULL,
    classId INT NOT NULL,
    enrolledBy INT,
    enrolmentDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('enrolled', 'dropped', 'completed') DEFAULT 'enrolled',
    FOREIGN KEY (studentId) REFERENCES Users(id) ON DELETE CASCADE,
    FOREIGN KEY (classId) REFERENCES Classes(id) ON DELETE CASCADE,
    FOREIGN KEY (enrolledBy) REFERENCES Users(id) ON DELETE SET NULL
);

-- Exams Table
CREATE TABLE Exam (
    id INT PRIMARY KEY AUTO_INCREMENT,
    classId INT NOT NULL,
    examDate DATE,
    startTime TIME,
    endTime TIME,
    venue VARCHAR(255),
    createdBy INT,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (classId) REFERENCES Classes(id) ON DELETE CASCADE,
    FOREIGN KEY (createdBy) REFERENCES Users(id) ON DELETE SET NULL
);

-- ============================================
-- FACILITIES & BOOKING TABLES
-- ============================================

-- Facilities Table
CREATE TABLE Facilities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    universityId INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    type ENUM('study room', 'gym', 'lecture hall', 'lab') DEFAULT 'study room',
    description TEXT,
    location VARCHAR(255),
    blockFloor VARCHAR(100),
    capacity INT,
    status VARCHAR(50) DEFAULT 'active',
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (universityId) REFERENCES Universities(id) ON DELETE CASCADE
);

-- Bookable Facilities Table
CREATE TABLE BookableFacilities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    facilityId INT NOT NULL,
    isBookable BOOLEAN DEFAULT FALSE,
    slotDuration INT,
    bookingCapacity INT NULL,
    openTime TIME,
    closeTime TIME,
    status ENUM('available', 'unavailable') DEFAULT 'available',
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (facilityId) REFERENCES Facilities(id) ON DELETE CASCADE
);

-- Facility Bookings Table
CREATE TABLE FacilityBookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    facilityId INT NOT NULL,
    userId INT NOT NULL,
    bookingDate DATE,
    startTime TIME,
    endTime TIME,
    purpose TEXT,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    cancelledAt DATETIME NULL,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (facilityId) REFERENCES BookableFacilities(id) ON DELETE CASCADE,
    FOREIGN KEY (userId) REFERENCES Users(id) ON DELETE CASCADE
);

-- Floor Plans Table
CREATE TABLE FloorPlans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    universityId INT NOT NULL,
    buildingName VARCHAR(255),
    fileName VARCHAR(255),
    filePath VARCHAR(500),
    uploadedBy INT,
    uploadedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (universityId) REFERENCES Universities(id) ON DELETE CASCADE,
    FOREIGN KEY (uploadedBy) REFERENCES Users(id) ON DELETE SET NULL
);

-- ============================================
-- EVENTS TABLES
-- ============================================

-- Events Table
CREATE TABLE Events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    universityId INT NOT NULL,
    createdBy INT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    location VARCHAR(255),
    startDatetime DATETIME,
    endDatetime DATETIME,
    capacity INT,
    status ENUM('active', 'suspended', 'cancelled', 'completed') DEFAULT 'active',
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (universityId) REFERENCES Universities(id) ON DELETE CASCADE,
    FOREIGN KEY (createdBy) REFERENCES Users(id) ON DELETE SET NULL
);

-- Event Registrations Table
CREATE TABLE EventRegistrations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    eventId INT NOT NULL,
    userId INT NOT NULL,
    registrationDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('registered', 'cancelled', 'attended') DEFAULT 'registered',
    FOREIGN KEY (eventId) REFERENCES Events(id) ON DELETE CASCADE,
    FOREIGN KEY (userId) REFERENCES Users(id) ON DELETE CASCADE
);

-- ============================================
-- NOTIFICATIONS & FEEDBACK TABLES
-- ============================================

-- Notifications Table
CREATE TABLE Notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    userId INT NOT NULL,
    type ENUM('booking', 'event', 'class', 'exam', 'system', 'study_grp') DEFAULT 'system',
    title VARCHAR(255),
    message TEXT,
    isRead BOOLEAN DEFAULT FALSE,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES Users(id) ON DELETE CASCADE
);

-- Feedback Table
CREATE TABLE Feedback (
    id INT PRIMARY KEY AUTO_INCREMENT,
    userId INT NOT NULL,
    universityId INT NOT NULL,
    category ENUM('general', 'bug', 'feature_request', 'complaint') DEFAULT 'general',
    message TEXT,
    status ENUM('new', 'in_review', 'resolved') DEFAULT 'new',
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES Users(id) ON DELETE CASCADE,
    FOREIGN KEY (universityId) REFERENCES Universities(id) ON DELETE CASCADE
);

-- ============================================
-- TIMETABLE ENTRIES TABLE
-- ============================================

CREATE TABLE TimetableEntries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    userId INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    dayOfWeek ENUM('mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'),
    startTime TIME,
    endTime TIME,
    location VARCHAR(255),
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES Users(id) ON DELETE CASCADE
);

-- ============================================
-- STUDY GROUPS TABLES
-- ============================================

-- Study Groups Table
CREATE TABLE StudyGroups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    universityId INT NOT NULL,
    moduleId INT NOT NULL,
    adminId INT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    maxMembers INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (universityId) REFERENCES Universities(id) ON DELETE CASCADE,
    FOREIGN KEY (moduleId) REFERENCES Modules(id) ON DELETE CASCADE,
    FOREIGN KEY (adminId) REFERENCES Users(id) ON DELETE SET NULL
);

-- Study Group Members Table
CREATE TABLE StudyGroupMembers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    groupId INT NOT NULL,
    studentId INT NOT NULL,
    status ENUM('active', 'left', 'removed') DEFAULT 'active',
    joinedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    leftAt DATETIME NULL,
    FOREIGN KEY (groupId) REFERENCES StudyGroups(id) ON DELETE CASCADE,
    FOREIGN KEY (studentId) REFERENCES Users(id) ON DELETE CASCADE
);

-- ============================================
-- AI CHATBOT TABLES
-- ============================================

-- AI Chatbot Session Table
CREATE TABLE AIChatbotSession (
    id INT PRIMARY KEY AUTO_INCREMENT,
    userId INT NOT NULL,
    startedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    endedAt DATETIME NULL,
    messageCount INT DEFAULT 0,
    clearedAt DATETIME NULL,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES Users(id) ON DELETE CASCADE
);

-- FAQs Table
CREATE TABLE FAQs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    universityId INT NOT NULL,
    createdBy INT,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (universityId) REFERENCES Universities(id) ON DELETE CASCADE,
    FOREIGN KEY (createdBy) REFERENCES Users(id) ON DELETE SET NULL
);

-- AI Model Versions Table
CREATE TABLE AIModelVersions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    modelName VARCHAR(255) NOT NULL,
    version VARCHAR(50) NOT NULL,
    releaseNotes TEXT,
    filePath VARCHAR(500),
    status VARCHAR(50) DEFAULT 'pending',
    approvedAt DATETIME NULL,
    deployedAt DATETIME NULL,
    updatedBy INT,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (updatedBy) REFERENCES SystemAdmins(id) ON DELETE SET NULL
);

-- ============================================
-- SYSTEM TABLES
-- ============================================

-- System Logs Table
CREATE TABLE SystemLogs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category VARCHAR(100),
    severity VARCHAR(50),
    action VARCHAR(255),
    description TEXT,
    source VARCHAR(255),
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Backup Records Table
CREATE TABLE BackupRecords (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fileName VARCHAR(255),
    filePath VARCHAR(500),
    backupType VARCHAR(50),
    status VARCHAR(50) DEFAULT 'pending',
    createdBy INT,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    restoredBy INT NULL,
    restoredAt DATETIME NULL,
    FOREIGN KEY (createdBy) REFERENCES SystemAdmins(id) ON DELETE SET NULL,
    FOREIGN KEY (restoredBy) REFERENCES SystemAdmins(id) ON DELETE SET NULL
);

-- FAQ Database Metadata Table
CREATE TABLE FAQDatabaseMetadata (
    id INT PRIMARY KEY AUTO_INCREMENT,
    universityId INT NOT NULL,
    status VARCHAR(50) DEFAULT 'active',
    storageUsage VARCHAR(50),
    connectionStatus VARCHAR(50),
    backupStatus VARCHAR(50),
    lastBackupAt DATETIME,
    lastMaintenanceAt DATETIME,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (universityId) REFERENCES Universities(id) ON DELETE CASCADE
);