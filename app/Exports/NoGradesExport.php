<?php

namespace App\Exports;

use App\Models\Grade;
use App\Models\Program;
use App\Models\Setting;
use App\Models\Anathesi;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class NoGradesExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{

    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function (AfterSheet $event) {
                $event->sheet->getDelegate()->getStyle('A1:C1')->getFont()->setSize(12)->setBold(true);
                $event->sheet->getDelegate()->getStyle('A1:C1')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFE0E0E0');
                $event->sheet->getDefaultRowDimension()->setRowHeight(20);
            },
        ];
    }

    public function headings(): array
    {
        return [
            'ΤΜΗΜΑ',
            'ΚΑΘΗΓΗΤΗΣ',
            'ΜΑΘΗΜΑ'
        ];
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $activeGradePeriod = Setting::getValueOf('activeGradePeriod');
        $insertedAnatheseis = Grade::where('period_id', $activeGradePeriod)->distinct('anathesi_id')->pluck('anathesi_id');
        $notInsertedAnatheseis = Anathesi::whereNotIn('id', $insertedAnatheseis)->with('user:id,name')->orderby('user_id')->orderby('tmima')->get(['user_id', 'tmima', 'mathima'])->toArray();
        $notInserted = [];
        foreach ($notInsertedAnatheseis as $not) {
            $notInserted[] = [ $not['tmima'], $not['user']['name'],  $not['mathima']];
        }
        return collect($notInserted);    
    }
}
