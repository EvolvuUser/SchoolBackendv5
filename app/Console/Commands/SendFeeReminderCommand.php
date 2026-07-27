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

        $students = DB::table('view_student_fees_category as fc')
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
            )
            ->get();

        foreach ($students as $student) {
            $pending = $student->installment_fees
                - $student->paid_amount
                - $student->concession;

            if ($pending <= 0) {
                continue;
            }

            $this->info($student->student_id . ' | ' . $student->first_name . ' ' . $student->last_name . ' | Installment : ' . $student->installment . ' | Pending : ' . $pending . ' | Due : ' . $student->due_date);

            // $mobile = $student->phone_no;

            // if (empty($mobile)) {
            //     $this->warn("No mobile number for Student ID {$student->student_id}");
            //     continue;
            // }

            // $message = "Dear Parent,\n";
            // $message .= "This is a reminder that the {$student->installment}" . getOrdinalSuffix($student->installment) . ' fee installment is due by ' . date('d M Y', strtotime($student->due_date)) . '.';
            // $message .= 'Please pay the fees as soon as possible to avoid a fine.';
            // $message .= "Please check the school application for more details.\n";
            // $message .= '– Evolvu';

            // $this->info("Sending to {$mobile}");

            // Call your existing WhatsApp service here
            // Example:
            // $this->whatsappService->sendMessage($mobile, $message);

            // Or your SMS service
            // $this->smsService->sendSMS($mobile, $message);
        }

        $this->info('Students Found : ' . $students->count());

        return Command::SUCCESS;
    }
}
