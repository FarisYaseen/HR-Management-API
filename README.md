# HR Management API

Use these commands to test the API.

## 1) Start Server

```bash
php artisan serve
```

## 2) Authentication

**Register**

```bash
curl -X POST http://127.0.0.1:8000/api/register \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"testuser@example.com","password":"password123","password_confirmation":"password123"}'
```

**Login (copy token from response)**

```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"testuser@example.com","password":"password123"}'
```

**Set token**

```bash
TOKEN="PASTE_TOKEN_HERE"
```

**Logout**

```bash
curl -X POST http://127.0.0.1:8000/api/logout \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

## 3) Employees CRUD

**Create founder**

```bash
curl -X POST http://127.0.0.1:8000/api/employees \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"name":"Dr. Ahmed Ameen","email":"founder@hr.com","salary":1045,"is_founder":true}'
```

**Create manager (under founder id=1)**

```bash
curl -X POST http://127.0.0.1:8000/api/employees \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"name":"Destinee King","email":"manager@hr.com","salary":5895,"is_founder":false,"manager_id":1}'
```

**Create employee (under manager id=2)**

```bash
curl -X POST http://127.0.0.1:8000/api/employees \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"name":"Faris Little","email":"faris@hr.com","salary":9762,"is_founder":false,"manager_id":2}'
```

**Read all employees**

```bash
curl -X GET http://127.0.0.1:8000/api/employees \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

**Update employee (id=3)**

```bash
curl -X PUT http://127.0.0.1:8000/api/employees/3 \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"salary":10000}'
```

**Delete employee (id=3)**

```bash
curl -X DELETE http://127.0.0.1:8000/api/employees/3 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

**Delete manager/founder with reassignment**

```bash
curl -X DELETE "http://127.0.0.1:8000/api/employees/2?reassign_manager_id=4" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

## 4) Managerial Hierarchy

**Hierarchy names**

```bash
curl -X GET http://127.0.0.1:8000/api/employees/4/hierarchy/names \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

**Hierarchy names + salaries**

```bash
curl -X GET http://127.0.0.1:8000/api/employees/4/hierarchy/names-salaries \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

## 5) Employee Search

**All employees**

```bash
curl -X GET "http://127.0.0.1:8000/api/employees-search" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

**Search by name**

```bash
curl -X GET "http://127.0.0.1:8000/api/employees-search?name=Oswald" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

**Search by salary**

```bash
curl -X GET "http://127.0.0.1:8000/api/employees-search?salary=5895" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

## 6) CSV Export / Import

**Export employees to CSV**

```bash
curl -L "http://127.0.0.1:8000/api/employees-export/csv" \
  -H "Accept: text/csv" \
  -H "Authorization: Bearer $TOKEN" \
  -o employees.csv
```

**Import employees from CSV**

```bash
curl -X POST "http://127.0.0.1:8000/api/employees-import/csv" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -F "file=@/absolute/path/to/employees.csv"
```

## 7) Salary Change Endpoint

**Update salary (triggers notification flow)**

```bash
curl -X PUT http://127.0.0.1:8000/api/employees/4 \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"salary":13000}'
```

**Employees without recent salary change**

```bash
curl -X GET "http://127.0.0.1:8000/api/employees-no-recent-salary-change?months=1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

## 8) Positions CRUD

**Create position**

```bash
curl -X POST http://127.0.0.1:8000/api/positions \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"name":"HR Manager","description":"Handles HR operations"}'
```

**Read all positions**

```bash
curl -X GET http://127.0.0.1:8000/api/positions \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

**Update position (id=1)**

```bash
curl -X PUT http://127.0.0.1:8000/api/positions/1 \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"description":"Updated description"}'
```

**Delete position (id=1)**

```bash
curl -X DELETE http://127.0.0.1:8000/api/positions/1 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

## 9) Artisan Commands

**Delete old employee logs (older than one month)**

```bash
php artisan employee-logs:delete-old
```

**Remove all log files**

```bash
php artisan logs:remove-all
```

**Insert employees with progress bar**

```bash
php artisan employees:insert 50
```

**Export entire database to SQL**

```bash
php artisan db:export-sql
```

**Export employees to JSON**

```bash
php artisan employees:export-json
```

## Logs

- Mail notifications: `storage/logs/mail.log`
- Employee operation logs: `storage/logs/employee.log`
- General Laravel logs: `storage/logs/laravel.log`
