# Student Management System (SMS)

A comprehensive web-based Student Management System built with PHP, MySQL, JavaScript, and Bootstrap 5.

## Features

- User Authentication (Admin, Teacher, Student roles)
- Dashboard with Statistics
- Student Management
- Teacher Management
- Class Management
- Attendance Tracking
- Grade Management
- Report Generation
- Responsive Design

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web Server (Apache/Nginx)
- Modern Web Browser

## Installation

1. Clone or download the repository to your web server directory:
```bash
git clone https://github.com/yourusername/student-management-system.git
```

2. Create a MySQL database named 'sms_db':
```sql
CREATE DATABASE sms_db;
```

3. Import the database schema:
```bash
mysql -u your_username -p sms_db < database/schema.sql
```

4. Configure the database connection:
   - Open `includes/config.php`
   - Update the database credentials according to your setup

5. Set up your web server:
   - For Apache: Ensure mod_rewrite is enabled
   - For Nginx: Configure URL rewriting according to your setup

6. Access the system:
   - URL: http://your-domain/SMS
   - Default admin credentials:
     - Username: admin
     - Password: password

## Directory Structure

```
SMS/
├── assets/
│   └── images/
├── css/
│   └── style.css
├── database/
│   └── schema.sql
├── includes/
│   └── config.php
├── js/
│   └── main.js
├── php/
│   └── logout.php
├── index.php
├── login.php
├── dashboard.php
└── README.md
```

## Security Features

- Password Hashing
- SQL Injection Prevention
- XSS Protection
- CSRF Protection
- Session Management
- Input Validation

## Usage

1. Login with appropriate credentials (admin/teacher/student)
2. Navigate through the dashboard
3. Manage students, teachers, classes, attendance, and grades
4. Generate and export reports
5. Update profile and settings

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a new Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support and queries, please create an issue in the repository or contact the development team.
