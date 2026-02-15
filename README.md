HR Management API
=======

Instructions : use these curl commands.

**Run server first:**
php artisan serve

**To Register (or use login if user exists)**
curl -X POST http://127.0.0.1:8000/api/register \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"testuser@example.com","password":"password123","password_confirmation":"password123"}'

**Login (copy token from response)**
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"testuser@example.com","password":"password123"}'

**Set token:**
TOKEN="PASTE_TOKEN_HERE"

**To Create a founder**
curl -X POST http://127.0.0.1:8000/api/employees \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"name":"Dr. ahmed Ameen","email":"founder@hr.com","salary":1045,"is_founder":true}'

**To Create manager (under founder id=1)**
curl -X POST http://127.0.0.1:8000/api/employees \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"name":"Destinee King","email":"manager@hr.com","salary":5895,"is_founder":false,"manager_id":1}'

**To Create employee (under manager id=2)**
curl -X POST http://127.0.0.1:8000/api/employees \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"name":"Faris Little","email":"Faris@hr.com","salary":9762,"is_founder":false,"manager_id":2}'

**Read all employees**
curl -X GET http://127.0.0.1:8000/api/employees \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"

**To Update employee (id=3)**
curl -X PUT http://127.0.0.1:8000/api/employees/3 \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"salary":10000}'

**To Delete employee (id=3)**
curl -X DELETE http://127.0.0.1:8000/api/employees/3 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"

**To Delete Manager,founder and reassing their employees run:**
curl -X DELETE "http://127.0.0.1:8000/api/employees/2?reassign_manager_id=4" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"

**To Logout**
curl -X POST http://127.0.0.1:8000/api/logout \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"

**To see employee hierarchy run:**
curl -X GET http://127.0.0.1:8000/api/employees/4/hierarchy/names \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"



**To see employee with their salary run:** 
curl -X GET http://127.0.0.1:8000/api/employees/28/hierarchy/names-salaries \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"

**Searching employees without giving any parameter run:**
curl -X GET "http://127.0.0.1:8000/api/employees-search" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"

**Searching employees with their name run:**
curl -X GET "http://127.0.0.1:8000/api/employees-search?name=Oswald" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"

**Searching employees with their salary run:**
curl -X GET "http://127.0.0.1:8000/api/employees-search?salary=5895" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"



**To export Data to .CSV run:**
curl -L "http://127.0.0.1:8000/api/employees-export/csv" \
  -H "Accept: text/csv" \
  -H "Authorization: Bearer $TOKEN" \
  -o employees.csv


**To import Data from .CSV run:**
curl -X POST "http://127.0.0.1:8000/api/employees-import/csv" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -F "file=@/absolute/path/to/employees.csv"


**Changing salary while triggering email notficaiton run:**
curl -X PUT http://127.0.0.1:8000/api/employees/4 \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"salary":13000}'


**Checking employee salary hasn’t change with X month run:**
curl -X GET "http://127.0.0.1:8000/api/employees-no-recent-salary-change?months=1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"

**To create a Postion in Positions Table run:**
curl -X POST http://127.0.0.1:8000/api/positions \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"name":"HR Manager","description":"Handles HR operations"}'

**To retrive all postions available run:**
curl -X GET http://127.0.0.1:8000/api/positions \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"

**To update postions by ID run:**
curl -X PUT http://127.0.0.1:8000/api/positions/1 \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"description":"Updated description"}'

**To reomve a postion run:**
curl -X DELETE http://127.0.0.1:8000/api/positions/1 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"


**To delete old logs run:**

php artisan employee-logs:delete-old

**To delete all logs run:**

php artisan logs:remove-all

**To show progress while inserting employees Run:**

php artisan employees:insert 50

**To export entire datbase to SQL file run:**

php artisan db:export-sql

**To exprot employee data into JSON file run:**

php artisan employees:export-json


Note:
•	Check email notifications in mail.log:
•	Mails for employees and manager are saved in mail.log
•	Employees Operation log are saved in employee.log along with DB table
