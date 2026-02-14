<?php

namespace App\Http\Controllers\Api;

use App\Events\EmployeeSalaryChanged;
use App\Http\Controllers\Controller;
use App\Mail\EmployeeCreatedNotification;
use App\Mail\EmployeeSalaryChangedNotification;
use App\Models\Employee;
use App\Models\EmployeeLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $employees = Employee::with('manager:id,name,email')
            ->latest('id')
            ->get();

        $this->logEmployeeOperation($request, 'employees.index', null, [
            'count' => $employees->count(),
        ]);

        return response()->json($employees);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:employees,email'],
            'salary' => ['required', 'numeric', 'min:0'],
            'is_founder' => ['sometimes', 'boolean'],
            'manager_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
        ]);

        $isFounder = (bool) ($data['is_founder'] ?? false);

        if ($isFounder) {
            $data['manager_id'] = null;
            $data['founder_key'] = 1;
        } else {
            if (empty($data['manager_id'])) {
                return response()->json([
                    'message' => 'Non-founder employee must have a manager.',
                ], 422);
            }
            $data['founder_key'] = null;
        }

        $data['salary_changed_at'] = now();
        $employee = Employee::create($data);

        if (!$employee->is_founder && $employee->manager) {
            Mail::to($employee->manager->email)
                ->send(new EmployeeCreatedNotification($employee, $employee->manager));
        }

        $this->logEmployeeOperation($request, 'employees.store', $employee->id, [
            'is_founder' => $employee->is_founder,
            'manager_id' => $employee->manager_id,
        ]);

        return response()->json([
            'message' => 'Employee created successfully.',
            'data' => $employee->load('manager:id,name,email'),
        ], 201);
    }

    public function show(Request $request, Employee $employee): JsonResponse
    {
        $this->logEmployeeOperation($request, 'employees.show', $employee->id);

        return response()->json($employee->load('manager:id,name,email'));
    }

    public function hierarchyNames(Request $request, Employee $employee): JsonResponse
    {
        $hierarchy = [];
        $visited = [];
        $current = $employee;

        while ($current) {
            if (isset($visited[$current->id])) {
                return response()->json([
                    'message' => 'Invalid managerial hierarchy: cycle detected.',
                ], 422);
            }

            $visited[$current->id] = true;
            if ($current->id === $employee->id) {
                $hierarchy[] = $current->name.' -> the employee';
            } elseif ($current->is_founder) {
                $hierarchy[] = $current->name.' -> founder';
            } else {
                $hierarchy[] = $current->name.' -> his manager';
            }
            $current = $current->manager;
        }

        // Required order: employee -> manager -> ... -> founder
        $this->logEmployeeOperation($request, 'employees.hierarchy.names', $employee->id, [
            'levels' => count($hierarchy),
        ]);

        return response()->json($hierarchy);
    }

    public function hierarchyNamesAndSalaries(Request $request, Employee $employee): JsonResponse
    {
        $hierarchy = [];
        $visited = [];
        $current = $employee;

        while ($current) {
            if (isset($visited[$current->id])) {
                return response()->json([
                    'message' => 'Invalid managerial hierarchy: cycle detected.',
                ], 422);
            }

            $visited[$current->id] = true;
            $hierarchy[$current->name] = (float) $current->salary;
            $current = $current->manager;
        }

        // Required order: employee -> manager -> ... -> founder
        $this->logEmployeeOperation($request, 'employees.hierarchy.names_salaries', $employee->id, [
            'levels' => count($hierarchy),
        ]);

        return response()->json($hierarchy);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'salary' => ['nullable', 'numeric', 'min:0'],
        ]);

        $name = $validated['name'] ?? null;
        $salary = array_key_exists('salary', $validated) ? (float) $validated['salary'] : null;

        $employees = Employee::with('manager:id,name,email')
            ->when($name !== null || $salary !== null, function ($query) use ($name, $salary) {
                $query->where(function ($q) use ($name, $salary) {
                    if ($name !== null) {
                        $q->where('name', 'like', '%'.$name.'%');
                    }

                    if ($salary !== null) {
                        if ($name !== null) {
                            $q->orWhere('salary', $salary);
                        } else {
                            $q->where('salary', $salary);
                        }
                    }
                });
            })
            ->latest('id')
            ->get();

        $this->logEmployeeOperation($request, 'employees.search', null, [
            'name' => $name,
            'salary' => $salary,
            'count' => $employees->count(),
        ]);

        return response()->json($employees);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $fileName = 'employees_'.now()->format('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ];

        $this->logEmployeeOperation($request, 'employees.export.csv', null, [
            'filename' => $fileName,
        ]);

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'id',
                'name',
                'email',
                'salary',
                'is_founder',
                'manager_id',
                'founder_key',
                'created_at',
                'updated_at',
            ]);

            Employee::orderBy('id')
                ->chunk(500, function ($employees) use ($handle) {
                    foreach ($employees as $employee) {
                        fputcsv($handle, [
                            $employee->id,
                            $employee->name,
                            $employee->email,
                            (float) $employee->salary,
                            $employee->is_founder ? 1 : 0,
                            $employee->manager_id,
                            $employee->founder_key,
                            $employee->created_at,
                            $employee->updated_at,
                        ]);
                    }
                });

            fclose($handle);
        }, 200, $headers);
    }

    public function importCsv(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $file = $validated['file'];
        $csv = new \SplFileObject($file->getRealPath());
        $csv->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);

        $headers = $csv->fgetcsv();
        if (!$headers || $headers === [null]) {
            return response()->json([
                'message' => 'CSV file is empty.',
            ], 422);
        }

        $headers = array_map(static fn ($h) => trim((string) $h), $headers);
        $requiredHeaders = ['name', 'email', 'salary'];
        foreach ($requiredHeaders as $requiredHeader) {
            if (!in_array($requiredHeader, $headers, true)) {
                return response()->json([
                    'message' => "Missing required CSV header: {$requiredHeader}",
                ], 422);
            }
        }

        $rows = [];
        foreach ($csv as $row) {
            if (!$row || $row === [null]) {
                continue;
            }

            $row = array_pad($row, count($headers), null);
            $item = array_combine($headers, array_slice($row, 0, count($headers)));

            if ($item === false) {
                continue;
            }

            if (empty(trim((string) ($item['email'] ?? '')))) {
                continue;
            }

            $rows[] = [
                'csv_id' => isset($item['id']) && $item['id'] !== '' ? (int) $item['id'] : null,
                'name' => trim((string) ($item['name'] ?? '')),
                'email' => trim((string) ($item['email'] ?? '')),
                'salary' => (float) ($item['salary'] ?? 0),
                'is_founder' => $this->toBoolean($item['is_founder'] ?? 0),
                'manager_id' => isset($item['manager_id']) && $item['manager_id'] !== '' ? (int) $item['manager_id'] : null,
            ];
        }

        if (empty($rows)) {
            return response()->json([
                'message' => 'No valid employee rows found in CSV.',
            ], 422);
        }

        $created = 0;
        $updated = 0;
        $skipped = [];
        $csvIdToDbId = [];
        $pending = $rows;

        DB::beginTransaction();
        try {
            $progress = true;
            while (!empty($pending) && $progress) {
                $progress = false;
                $remaining = [];

                foreach ($pending as $row) {
                    $resolvedManagerId = null;

                    if (!$row['is_founder']) {
                        if (empty($row['manager_id'])) {
                            $skipped[] = [
                                'email' => $row['email'],
                                'reason' => 'Non-founder employee must have a manager_id.',
                            ];
                            continue;
                        }

                        if (isset($csvIdToDbId[$row['manager_id']])) {
                            $resolvedManagerId = $csvIdToDbId[$row['manager_id']];
                        } elseif (Employee::query()->whereKey($row['manager_id'])->exists()) {
                            $resolvedManagerId = $row['manager_id'];
                        } else {
                            $remaining[] = $row;
                            continue;
                        }
                    }

                    $existing = Employee::query()->where('email', $row['email'])->first();
                    $targetId = $row['csv_id'];

                    if ($existing) {
                        $targetId = $existing->id;
                    } elseif ($targetId !== null && Employee::query()->whereKey($targetId)->exists()) {
                        $targetId = null;
                    }

                    if ($resolvedManagerId !== null && $targetId !== null && $resolvedManagerId === $targetId) {
                        $skipped[] = [
                            'email' => $row['email'],
                            'reason' => 'Employee cannot be their own manager.',
                        ];
                        continue;
                    }

                    $data = [
                        'name' => $row['name'],
                        'email' => $row['email'],
                        'salary' => $row['salary'],
                        'salary_changed_at' => now(),
                        'is_founder' => $row['is_founder'],
                        'manager_id' => $row['is_founder'] ? null : $resolvedManagerId,
                        'founder_key' => $row['is_founder'] ? 1 : null,
                    ];

                    if ($existing) {
                        $existing->update($data);
                        $employee = $existing->fresh();
                        $updated++;
                    } else {
                        $employee = new Employee();
                        if ($targetId !== null) {
                            $employee->id = $targetId;
                        }
                        $employee->fill($data);
                        $employee->save();
                        $created++;
                    }

                    if ($row['csv_id'] !== null) {
                        $csvIdToDbId[$row['csv_id']] = $employee->id;
                    }

                    $progress = true;
                }

                $pending = $remaining;
            }

            foreach ($pending as $unresolved) {
                $skipped[] = [
                    'email' => $unresolved['email'],
                    'reason' => 'Unable to resolve manager_id from CSV/imported records.',
                ];
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Import failed.',
                'error' => $e->getMessage(),
            ], 422);
        }

        $this->logEmployeeOperation($request, 'employees.import.csv', null, [
            'created' => $created,
            'updated' => $updated,
            'skipped_count' => count($skipped),
        ]);

        return response()->json([
            'message' => 'Employee CSV import completed.',
            'created' => $created,
            'updated' => $updated,
            'skipped_count' => count($skipped),
            'skipped' => $skipped,
        ]);
    }

    public function withoutRecentSalaryChange(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'months' => ['required', 'integer', 'min:1'],
        ]);

        $months = (int) $validated['months'];
        $threshold = now()->subMonths($months);

        $employees = Employee::with('manager:id,name,email')
            ->whereNotNull('salary_changed_at')
            ->where('salary_changed_at', '<=', $threshold)
            ->latest('id')
            ->get();

        $this->logEmployeeOperation($request, 'employees.salary.no_recent_change', null, [
            'months' => $months,
            'count' => $employees->count(),
        ]);

        return response()->json([
            'months' => $months,
            'threshold_date' => $threshold->toDateString(),
            'count' => $employees->count(),
            'data' => $employees,
        ]);
    }

    public function update(Request $request, Employee $employee): JsonResponse
    {
        $oldSalary = (float) $employee->salary;

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($employee->id)],
            'salary' => ['sometimes', 'required', 'numeric', 'min:0'],
            'is_founder' => ['sometimes', 'boolean'],
            'manager_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
        ]);

        if (array_key_exists('manager_id', $data) && (int) $data['manager_id'] === (int) $employee->id) {
            return response()->json([
                'message' => 'Employee cannot be their own manager.',
            ], 422);
        }

        $isFounder = array_key_exists('is_founder', $data)
            ? (bool) $data['is_founder']
            : $employee->is_founder;

        $managerId = array_key_exists('manager_id', $data)
            ? $data['manager_id']
            : $employee->manager_id;

        if ($managerId !== null && (int) $managerId === (int) $employee->id) {
            return response()->json([
                'message' => 'Employee cannot be their own manager.',
            ], 422);
        }

        if ($isFounder) {
            $data['manager_id'] = null;
            $data['founder_key'] = 1;
        } else {
            if (empty($managerId)) {
                return response()->json([
                    'message' => 'Non-founder employee must have a manager.',
                ], 422);
            }
            $data['founder_key'] = null;
        }

        $employee->update($data);
        $updatedEmployee = $employee->fresh()->load('manager:id,name,email');

        if (array_key_exists('salary', $data)) {
            $newSalary = (float) $updatedEmployee->salary;

            if (abs($newSalary - $oldSalary) > 0.00001) {
                $employee->forceFill([
                    'salary_changed_at' => now(),
                ])->save();
                $updatedEmployee = $employee->fresh()->load('manager:id,name,email');

                Mail::to($updatedEmployee->email)
                    ->send(new EmployeeSalaryChangedNotification($updatedEmployee, $oldSalary, $newSalary));

                $managerChain = $this->getManagerChain($updatedEmployee);
                $managerIds = $managerChain->pluck('id')->all();

                if (!empty($managerIds)) {
                    event(new EmployeeSalaryChanged($updatedEmployee, $oldSalary, $newSalary, $managerIds));
                }
            }
        }

        $this->logEmployeeOperation($request, 'employees.update', $updatedEmployee->id, [
            'salary_changed' => array_key_exists('salary', $data) && abs(((float) $updatedEmployee->salary) - $oldSalary) > 0.00001,
        ]);

        return response()->json([
            'message' => 'Employee updated successfully.',
            'data' => $updatedEmployee,
        ]);
    }

    public function destroy(Request $request, Employee $employee): JsonResponse
    {
        $employeeId = $employee->id;
        $reassignManagerId = $request->input('reassign_manager_id');

        $subordinates = $employee->subordinates()->pluck('id');
        if ($subordinates->isNotEmpty()) {
            if (! $reassignManagerId) {
                return response()->json([
                    'message' => 'This employee has subordinates. Provide reassign_manager_id to reassign them before deletion.',
                    'subordinate_ids' => $subordinates->values(),
                ], 409);
            }

            if ((int) $reassignManagerId === (int) $employeeId) {
                return response()->json([
                    'message' => 'reassign_manager_id cannot be the same as the employee being deleted.',
                ], 422);
            }

            $newManager = Employee::query()->find($reassignManagerId);
            if (! $newManager) {
                return response()->json([
                    'message' => 'reassign_manager_id does not exist.',
                ], 422);
            }

            $employee->subordinates()->update(['manager_id' => $newManager->id]);
        }

        // Log before delete so FK to employee_id remains valid.
        $this->logEmployeeOperation($request, 'employees.destroy', $employeeId, [
            'reassign_manager_id' => $reassignManagerId,
            'subordinates_reassigned' => $subordinates->count(),
        ]);

        $employee->delete();

        return response()->json([
            'message' => 'Employee deleted successfully.',
        ]);
    }

    private function toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes'], true);
    }

    private function getManagerChain(Employee $employee): Collection
    {
        $chain = collect();
        $visited = [];
        $current = $employee->manager;

        while ($current) {
            if (isset($visited[$current->id])) {
                break;
            }

            $visited[$current->id] = true;
            $chain->push($current);
            $current = $current->manager;
        }

        return $chain;
    }

    private function logEmployeeOperation(Request $request, string $action, ?int $employeeId = null, array $meta = []): void
    {
        $user = $request->user();
        $logEmployeeId = $employeeId;

        if ($logEmployeeId !== null && ! Employee::query()->whereKey($logEmployeeId)->exists()) {
            $logEmployeeId = null;
            $meta['employee_id_missing'] = $employeeId;
        }

        EmployeeLog::create([
            'employee_id' => $logEmployeeId,
            'user_id' => $user?->id,
            'action' => $action,
            'http_method' => $request->method(),
            'endpoint' => $request->path(),
            'meta' => $meta,
        ]);

        Log::channel('employee')->info($action, [
            'employee_id' => $logEmployeeId,
            'user_id' => $user?->id,
            'http_method' => $request->method(),
            'endpoint' => $request->path(),
            'meta' => $meta,
        ]);
    }
}
