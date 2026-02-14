<?php

namespace App\Events;

use App\Models\Employee;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmployeeSalaryChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  int[]  $managerIds
     */
    public function __construct(
        public Employee $employee,
        public float $oldSalary,
        public float $newSalary,
        public array $managerIds
    ) {
    }

    public function broadcastOn(): array
    {
        return array_map(
            fn (int $managerId) => new Channel('managers.'.$managerId),
            $this->managerIds
        );
    }

    public function broadcastAs(): string
    {
        return 'employee.salary.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'employee_id' => $this->employee->id,
            'employee_name' => $this->employee->name,
            'old_salary' => $this->oldSalary,
            'new_salary' => $this->newSalary,
            'changed_at' => now()->toISOString(),
        ];
    }
}
