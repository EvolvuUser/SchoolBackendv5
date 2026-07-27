<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendFeeReminderCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fees:reminder {connection}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send automatic fee reminders to students with pending fees';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $connection = $this->argument('connection');

        if (!config("database.connections.$connection")) {
            $this->error("Database connection {$connection} not found.");
            return Command::FAILURE;
        }

        config(['database.default' => $connection]);

        DB::purge($connection);
        DB::reconnect($connection);

        $this->info("Connected to {$connection}");

        $academicYear = DB::table('settings')
            ->where('active', 'Y')
            ->value('academic_yr');

        // Later this will come from settings table
        $reminderDays = DB::table('school_settings')
            ->value('fees_reminder_days');

        $query = DB::table('view_student_fees_category as fc')
            ->leftJoin('view_fees_payment_record as fp', function ($join) {
                $join
                    ->on('fc.student_id', '=', 'fp.student_id')
                    ->on('fc.fee_allotment_id', '=', 'fp.fee_allotment_id');
            })
            ->leftJoin('fee_concession_details as cd', function ($join) {
                $join
                    ->on('fc.student_id', '=', 'cd.student_id')
                    ->on('fc.installment', '=', 'cd.installment');
            })
            ->join('student as st', 'fc.student_id', '=', 'st.student_id')
            ->leftjoin('contact_details as ct', 'st.parent_id', '=', 'ct.id')
            ->where('fc.academic_yr', $academicYear)
            ->whereNotNull('fc.due_date')
            ->whereRaw('DATEDIFF(fc.due_date, CURDATE()) = ?', [$reminderDays])
            ->groupBy(
                'fc.student_id',
                'fc.first_name',
                'fc.last_name',
                'fc.roll_no',
                'fc.class_id',
                'fc.section_id',
                'fc.fee_allotment_id',
                'fc.installment',
                'fc.installment_fees',
                'fc.due_date'
            )
            ->select(
                'fc.student_id',
                'fc.first_name',
                'fc.last_name',
                'fc.roll_no',
                'fc.class_id',
                'fc.section_id',
                'fc.fee_allotment_id',
                'fc.installment',
                'fc.installment_fees',
                'fc.due_date',
                'ct.phone_no',
                DB::raw('COALESCE(SUM(fp.payment_amount),0) as paid_amount'),
                DB::raw('COALESCE(SUM(cd.amount),0) as concession')
            );
        // Log the exact SQL with bindings resolved
        $rawSql = vsprintf(
            str_replace('?', '%s', $query->toSql()),
            array_map(fn($binding) => is_numeric($binding) ? $binding : "'{$binding}'", $query->getBindings())
        );

        \Illuminate\Support\Facades\Log::info('FEE_REMINDER_DEBUG_SQL: ' . $rawSql);
        $this->info('Debug SQL logged. Check storage/logs/laravel.log');
        $this->info('Academic Year used: ' . $academicYear);
        $this->info('Reminder Days used: ' . $reminderDays);
        $this->info('Connection used: ' . $connection);

        $students = $query->get();

        $totalMatched = $students->count();  // total rows returned by query (installments due today)
        $pendingCount = 0;  // students who actually owe money
        $alreadyPaidCount = 0;  // matched but already fully paid/concessioned
        $missingPhoneCount = 0;  // pending but no phone number to send to

        foreach ($students as $student) {
            $pending = $student->installment_fees
                - $student->paid_amount
                - $student->concession;

            if ($pending <= 0) {
                $alreadyPaidCount++;
                continue;
            }

            $pendingCount++;

            $mobile = $student->phone_no;

            if (empty($mobile)) {
                $missingPhoneCount++;
                $this->warn("No mobile number for Student ID {$student->student_id}");
                continue;
            }

            $this->info($student->student_id . ' | ' . $student->first_name . ' ' . $student->last_name
                . ' | Installment : ' . $student->installment
                . ' | Pending : ' . $pending
                . ' | Due : ' . $student->due_date);

            // Call your existing WhatsApp/SMS service here
            // $this->whatsappService->sendMessage($mobile, $message);
        }

        $this->info('----------------------------------------');
        $this->info('Total Rows Matched (query)   : ' . $totalMatched);
        $this->info('Already Paid / No Pending    : ' . $alreadyPaidCount);
        $this->info('Pending (needs reminder)     : ' . $pendingCount);
        $this->info('Pending but Missing Phone    : ' . $missingPhoneCount);
        $this->info('Reminders Actually Sendable  : ' . ($pendingCount - $missingPhoneCount));

        $this->info('Students Found : ' . $students->count());

        return Command::SUCCESS;
    }
}
