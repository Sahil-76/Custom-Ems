<?php

namespace App\Exports;

use App\Models\Asset;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
class AssetExport implements FromCollection ,WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Asset::all();
    }

    public function headings(): array
    {
        return [
            'Id',
            'Sub_Type_Id',
            'Barcode',
            'Status',
            'Assigned_To',
            'Description',
            'company_id',
            'is_exported',
        ];
    }
}

