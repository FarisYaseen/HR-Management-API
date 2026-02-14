<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Salary Updated</title>
</head>
<body>
    <p>Hello {{ $employee->name }},</p>

    <p>Your salary has been updated.</p>

    <ul>
        <li><strong>Previous Salary:</strong> {{ number_format($oldSalary, 2) }}</li>
        <li><strong>New Salary:</strong> {{ number_format($newSalary, 2) }}</li>
    </ul>

    <p>Regards,<br>HR Management System</p>
</body>
</html>
