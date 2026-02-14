<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Employee Assigned</title>
</head>
<body>
    <p>Hello {{ $manager->name }},</p>

    <p>A new employee has been created under your management:</p>

    <ul>
        <li><strong>Name:</strong> {{ $employee->name }}</li>
        <li><strong>Email:</strong> {{ $employee->email }}</li>
        <li><strong>Salary:</strong> {{ number_format((float) $employee->salary, 2) }}</li>
    </ul>

    <p>Regards,<br>HR Management System</p>
</body>
</html>
