<?php

namespace App\Jobs;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class GenerateReportCardJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $class_name;
    protected $class_id;
    protected $section_id;
    protected $academic_yr;

    /**
     * Create a new job instance.
     */
    public function __construct(array $data)
    {
        $this->class_name = $data['class_name'];
        $this->class_id = $data['class_id'];
        $this->section_id = $data['section_id'] ?? null;
        $this->academic_yr = $data['academic_yr'];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $fileName = "report_cards/{$this->class_name}_{$this->class_id}_{$this->academic_yr}.pdf";
        $fullPath = storage_path("app/public/$fileName");

        switch ($this->class_name) {
            case 'Nursery':
                Pdf::loadView(
                    'reportcard.SACS.nursery_report_card_pdf',
                    [
                        'section_id' => $this->section_id,
                        'class_id' => $this->class_id,
                        'academic_yr' => $this->academic_yr
                    ]
                )->save($fullPath);
                break;

            case 'LKG':
                Pdf::loadView(
                    'reportcard.SACS.lkg_report_card_pdf',
                    [
                        'student_id' => $this->student_id,
                        'class_id' => $this->class_id,
                        'academic_yr' => $this->academic_yr
                    ]
                )->save($fullPath);
                break;

            case 'UKG':
                Pdf::loadView(
                    'reportcard.SACS.ukg_report_card_pdf',
                    [
                        'student_id' => $this->student_id,
                        'class_id' => $this->class_id,
                        'academic_yr' => $this->academic_yr
                    ]
                )->save($fullPath);
                break;

            case '1':
            case '2':
                Pdf::loadView(
                    'reportcard.SACS.class1to2_report_card_pdf',
                    compact('student_id', 'class_id', 'academic_yr')
                )->save($fullPath);
                break;

            case '3':
            case '4':
            case '5':
                Pdf::loadView(
                    'reportcard.SACS.class3to5_report_card_pdf',
                    compact('student_id', 'class_id', 'academic_yr')
                )->save($fullPath);
                break;

            case '6':
            case '7':
            case '8':
                Pdf::loadView(
                    'reportcard.SACS.class6to8_report_card_pdf_all',
                    [
                        'section_id' => $this->section_id,
                        'class_id' => $this->class_id,
                        'academic_yr' => $this->academic_yr
                    ]
                )->save($fullPath);
                break;

            case '9':
            case '10':
                Pdf::loadView(
                    'reportcard.SACS.class9to10_report_card_pdf_all',
                    [
                        'section_id' => $this->section_id,
                        'class_id' => $this->class_id,
                        'academic_yr' => $this->academic_yr
                    ]
                )->save($fullPath);
                break;

            default:
                throw new \Exception('Invalid class');
        }

        // save path in DB if needed
        DB::table('report_card_generate_classwise')->updateOrInsert(
            [
                'class_id' => $this->class_id,
                'section_id' => $this->section_id,
                'academic_yr' => $this->academic_yr
            ],
            [
                'url' => $fileName,
                'status' => 'generated',
            ]
        );
    }
}
