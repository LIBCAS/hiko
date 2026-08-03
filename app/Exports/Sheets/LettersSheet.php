<?php

namespace App\Exports\Sheets;

use Generator;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class LettersSheet extends AbstractLetterSheet implements WithColumnWidths
{
    public function title(): string
    {
        return 'Letters';
    }

    public function headings(): array
    {
        return [
            'ID', 'UUID', 'Created At', 'Updated At',
            'Date Computed', 'Date Year', 'Date Month', 'Date Day', 'Date Marked',
            'Date Uncertain', 'Date Approximate', 'Date Inferred', 'Date Is Range', 'Date Note',
            'Range Year', 'Range Month', 'Range Day',
            'Author Inferred', 'Author Uncertain', 'Author Note',
            'Recipient Inferred', 'Recipient Uncertain', 'Recipient Note',
            'Origin Inferred', 'Origin Uncertain', 'Origin Note',
            'Destination Inferred', 'Destination Uncertain', 'Destination Note',
            'People Mentioned Note', 'Abstract CS', 'Abstract EN', 'Explicit', 'Incipit',
            'Content', 'Content Stripped', 'Copyright', 'Languages', 'Notes Private', 'Notes Public',
            'Status', 'Approval', 'History',
        ];
    }

    public function generator(): Generator
    {
        foreach ($this->letters() as $letter) {
            yield [
                $letter->id,
                $letter->uuid,
                $this->dateTime($letter->created_at),
                $this->dateTime($letter->updated_at),
                $this->dateTime($letter->date_computed),
                $letter->date_year,
                $letter->date_month,
                $letter->date_day,
                $letter->date_marked,
                $this->boolean($letter->date_uncertain),
                $this->boolean($letter->date_approximate),
                $this->boolean($letter->date_inferred),
                $this->boolean($letter->date_is_range),
                $letter->date_note,
                $letter->range_year,
                $letter->range_month,
                $letter->range_day,
                $this->boolean($letter->author_inferred),
                $this->boolean($letter->author_uncertain),
                $letter->author_note,
                $this->boolean($letter->recipient_inferred),
                $this->boolean($letter->recipient_uncertain),
                $letter->recipient_note,
                $this->boolean($letter->origin_inferred),
                $this->boolean($letter->origin_uncertain),
                $letter->origin_note,
                $this->boolean($letter->destination_inferred),
                $this->boolean($letter->destination_uncertain),
                $letter->destination_note,
                $letter->people_mentioned_note,
                $this->translation($letter, 'abstract', 'cs'),
                $this->translation($letter, 'abstract', 'en'),
                $letter->explicit,
                $letter->incipit,
                $letter->content,
                $letter->content_stripped,
                $letter->copyright,
                $letter->languages,
                $letter->notes_private,
                $letter->notes_public,
                $letter->status,
                $this->boolean($letter->approval),
                $letter->history,
            ];
        }
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 38,
            'AE' => 35,
            'AF' => 35,
            'AI' => 70,
            'AJ' => 70,
            'AM' => 45,
            'AN' => 45,
            'AQ' => 70,
        ];
    }
}
