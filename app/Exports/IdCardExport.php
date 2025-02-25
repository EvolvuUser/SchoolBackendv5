<?php

namespace App\Exports;

use App\Models\ConfirmationIdCard;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithImages;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class IdCardExport implements FromCollection, WithHeadings, WithMapping,WithImages
{
    protected $idcarddetails;
    protected $counter = 1;
    public function __construct($idcarddetails)
    {
        $this->idcarddetails = $idcarddetails;
    }
    public function collection()
    {
        return $this->idcarddetails;
    }

    public function headings(): array
    {
        return [
            'Sr No.',
            'Photo',
            'Roll No',
            'Class',
            'Student Name',
            'DOB',
            'Father Mobile No.',
            'Mother Mobile No.',
            'Address',
            'Blood Group',
            'Grn No.',
            'House',
            'Image Name'
        ];
    }

    public function map($student): array
    {
        return [
            $this->counter++,
            $student->image_name,
            $student->roll_no,
            $student->class_name.'-'. $student->sec_name,
            $student->first_name . ' ' . $student->mid_name . ' ' . $student->last_name,
            $student->dob,
            $student->f_mobile,
            $student->m_mobile,
            $student->permant_add,
            $student->blood_group,
            $student->reg_no,
            $student->house,
            $student->image_name,
        ];
    }

    public function images(): array
    {
        $images = [];

        foreach ($this->idcarddetails as $index => $student) {
            if (!empty($student->image_url)) {
                $images[$index] = $student->image_url;
            }
        }

        return $images;
    }

    public function mapImage($student, $index)
    {
        // Check if the student has a valid image URL
        if (!empty($student->image_url)) {
            $drawing = new Drawing();
            $drawing->setName('Student Image');
            $drawing->setDescription('Student Image');
            $drawing->setPath($student->image_url); // Path to the image file
            $drawing->setHeight(50);  // You can adjust the height and width as needed
            $drawing->setWidth(50);
            $drawing->setCoordinates('R' . ($index + 2)); // Assuming images will be placed in the 'R' column

            return $drawing;
        }

        return null;
    }
}
