# Testing the Backend API

## 1. Start a Web Server

### Option A: PHP Built-in Server (Simple)
```powershell
cd c:\Users\Admin\IAP\smartfield-institute
php --version
php -S localhost:8000
```
Then access: http://localhost:8000/frontend/index.html

> If PowerShell reports `php` is not recognized, install PHP or XAMPP and add the PHP installation folder to your system `PATH`.
> If courses still do not appear, verify the API directly at `http://localhost:8000/backend/routes/api.php/courses`.

### Option B: Apache/Nginx
Configure your web server to serve the `smartfield-institute` directory.

## 2. Test API Endpoints

### Student Registration
```bash
curl -X POST http://localhost:8000/backend/routes/api.php/students \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john.doe@example.com",
    "phone": "+1234567890",
    "address": "123 Main St",
    "date_of_birth": "2000-01-01"
  }'
```

### Admin Login
```bash
curl -X POST http://localhost:8000/backend/routes/api.php/admin/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "admin",
    "password": "admin123"
  }'
```

### Get Course Catalog (JSON)
```bash
curl -X GET http://localhost:8000/backend/routes/api.php/courses
```

### Get Course Catalog (XML)
```bash
curl -X GET http://localhost:8000/backend/routes/api.php/courses/xml
```

### Get All Students (Admin Only)
```bash
curl -X GET http://localhost:8000/backend/routes/api.php/students \
  -H "Cookie: PHPSESSID=YOUR_SESSION_ID"
```

## 3. Frontend Testing

1. Serve the application over HTTP, do not open `frontend/index.html` directly with `file://`.
   - If you are using PHP's built-in server, open `http://localhost:8000/frontend/index.html`.
2. Try registering a student
3. Open `frontend/admin.html` and login with admin/admin123
4. Access the dashboard to manage students

## 4. Expected Responses

### Successful Registration
```json
{
  "success": true,
  "message": "Student registered successfully."
}
```

### Validation Error
```json
{
  "success": false,
  "message": "All fields are required."
}
```

### Authentication Error
```json
{
  "success": false,
  "message": "Invalid password."
}
```