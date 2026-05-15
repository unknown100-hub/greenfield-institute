# SmartField Institute Student Registration System

A web application designed to streamline the registration process for students at SmartField Institute, improve data accuracy, and enhance the overall user experience for both students and administrators.

## Features

### For Students
- **Easy Registration**: Simple online form to register with personal details
- **Data Validation**: Real-time validation ensures accurate information
- **Age Verification**: Ensures students are at least 16 years old
- **Duplicate Prevention**: Email uniqueness prevents duplicate registrations

### For Administrators
- **Secure Login**: Admin authentication with password hashing
- **Student Management**: View, edit, and delete student records
- **Dashboard Analytics**: Overview of total and recent student registrations
- **Data Integrity**: Comprehensive validation and error handling

## Technology Stack

- **Backend**: PHP with PDO for database operations
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Data Exchange**: XML used for structured course catalog delivery
- **Architecture**: MVC-like structure with separate models, controllers, and routes

## Project Structure

```
smartfield-institute/
├── backend/
│   ├── config/
│   │   └── db.php              # Database configuration
│   ├── controllers/
│   │   ├── AdminController.php # Admin authentication logic
│   │   └── StudentController.php # Student CRUD operations
│   ├── models/
│   │   ├── Admin.php          # Admin data model
│   │   └── Student.php        # Student data model
│   ├── routes/
│   │   └── api.php            # API endpoints
│   └── migrations/
│       └── schema.sql         # Database schema
├── frontend/
│   ├── index.html             # Student registration page
│   ├── admin.html             # Admin login page
│   ├── dashboard.html         # Admin dashboard
│   └── assets/                # Static assets (CSS, JS, images)
└── README.md
```

## Setup Instructions

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx) or PHP built-in server

> If `php` is not recognized in PowerShell, install PHP or XAMPP and add the PHP install directory to your system `PATH`.

### Database Setup
1. Create a MySQL database named `student_system`
2. Run the SQL script in `backend/migrations/schema.sql` to create tables and insert default admin and seeded courses

### Configuration
1. Update database credentials in `backend/config/db.php` if needed
2. Ensure the web server can access the project files

### Running the Application
1. Open PowerShell and go to the project root:
   ```powershell
   cd c:\Users\Admin\IAP\smartfield-institute
   ```
2. Verify PHP is available:
   ```powershell
   php --version
   ```
3. Start the built-in server:
   ```powershell
   php -S localhost:8000
   ```
4. Open the app in your browser:
   `http://localhost:8000/frontend/index.html`
5. Open the admin page:
   `http://localhost:8000/frontend/admin.html`

> If PowerShell reports `php` is not recognized, install PHP or XAMPP and add the PHP install directory to your Windows `PATH`.

### Troubleshooting
- Use HTTP access rather than opening `frontend/index.html` directly from the file system.
- If courses do not load, verify the backend API at:
  `http://localhost:8000/backend/routes/api.php/courses`
- If the API returns an error, check that MySQL is running and the database is configured correctly.

### Default Admin Credentials
- Username: `admin`
- Password: `admin123`

## API Endpoints

### Students
- `POST /api.php/students` - Register a new student
- `GET /api.php/students` - Get all students (admin only)
- `GET /api.php/students/{id}` - Get student by ID
- `PUT /api.php/students/{id}` - Update student (admin only)
- `DELETE /api.php/students/{id}` - Delete student (admin only)

### Courses
- `GET /api.php/courses` - Get course catalog as JSON
- `GET /api.php/courses/xml` - Get course catalog as XML
- `GET /api.php/courses/{id}` - Get course details by ID

### Admin
- `POST /api.php/admin/register` - Register new admin
- `POST /api.php/admin/login` - Admin login
- `POST /api.php/admin/logout` - Admin logout
- `GET /api.php/admin/check` - Check login status

## Business Logic Implementation

### Student Registration
- Validates all required fields
- Checks email format and uniqueness
- Validates date of birth format and minimum age (16 years)
- Sanitizes input data to prevent XSS
- Provides clear success/error messages

### Admin Authentication
- Secure password hashing using bcrypt
- Session-based authentication
- Username uniqueness validation
- Password strength requirements (minimum 8 characters)

### Data Management
- CRUD operations for student records
- Admin-only access to sensitive operations
- Comprehensive error handling
- Data integrity through validation

## Security Features

- Input sanitization and validation
- Password hashing
- Session management
- CORS headers for API security
- SQL injection prevention through prepared statements

## Future Enhancements

- Email verification for student registration
- Password reset functionality
- Advanced search and filtering
- Export student data to CSV/PDF
- Role-based access control
- Audit logging
- Mobile-responsive design improvements