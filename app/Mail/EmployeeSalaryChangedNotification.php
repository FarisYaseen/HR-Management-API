<?php

namespace App\Mail;

use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmployeeSalaryChangedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Employee $employee,
        public float $oldSalary,
        public float $newSalary
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Salary Has Been Updated',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.employee-salary-changed',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
