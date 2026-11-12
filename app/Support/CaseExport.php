<?php

namespace App\Support;

use App\Models\Grievance;
use App\Models\User;
use Illuminate\Support\Carbon;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV or Excel export of the cases a user may see, received in a date range (N4), with each date in AD and BS (U9).
 * Names and contact details only when the Administrator asks for them; every export is logged (S6).
 */
class CaseExport
{
    public static function download(User $user, Carbon $from, Carbon $until, string $format, bool $personal): StreamedResponse
    {
        $personal = $personal && $user->can('exportPersonalData', Grievance::class);
        $cases = Grievance::visibleTo($user)->with(['category', 'location'])
            ->whereBetween('received_at', [$from->copy()->startOfDay(), $until->copy()->endOfDay()]);

        activity()->causedBy($user)->event('exported')->withProperties([
            'from' => $from->toDateString(), 'until' => $until->toDateString(), 'format' => $format,
            'personal_data' => $personal, 'cases' => $cases->count(),
        ])->log('Cases exported');
        // Also on each case, so the log's reference filter answers who exported it (S6).
        $cases->lazyById()->each(fn (Grievance $case) => activity()->performedOn($case)->causedBy($user)->event('exported')
            ->withProperties(['personal_data' => $personal])->log('Case exported'));

        // shortcut: Livewire buffers the whole file to send it; for exports of tens of MB, download from a plain route instead.
        return response()->streamDownload(function () use ($cases, $format, $personal): void {
            // The CSV starts with a byte-order mark (openspout's default), so Excel reads Nepali text as UTF-8.
            $writer = $format === 'xlsx' ? new XlsxWriter : new CsvWriter;
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues(self::columns($personal)));

            $cases->lazyById()->each(fn (Grievance $case) => $writer->addRow(Row::fromValues(array_map(self::harmless(...), self::row($case, $personal)))));

            $writer->close();
        }, "cases-{$from->toDateString()}-to-{$until->toDateString()}.{$format}");
    }

    /**
     * The RFQ N4 column list in its order, with names and contacts after the reference when included,
     * and each date twice: AD with the time, then the BS date.
     *
     * @return list<string>
     */
    private static function columns(bool $personal): array
    {
        return ['Reference', ...($personal ? ['Name', 'Phone', 'Email'] : []), 'Received (AD)', 'Received (BS)', 'Closed (AD)', 'Closed (BS)', 'Channel', 'Category',
            'Location', 'Priority', 'Status', 'Complainant type', 'Sex', 'Age group', 'Ethnicity', 'Disability'];
    }

    /**
     * @return list<string>
     */
    private static function row(Grievance $case, bool $personal): array
    {
        return [$case->reference, ...($personal ? [(string) $case->name, (string) $case->phone, (string) $case->email] : []),
            $case->received_at->format('Y-m-d H:i'), (string) BsDate::format($case->received_at),
            (string) $case->closed_at?->format('Y-m-d H:i'), $case->closed_at ? (string) BsDate::format($case->closed_at) : '', $case->channel->getLabel(),
            (string) $case->category?->name_en, (string) $case->location?->name_en, (string) $case->priority?->getLabel(), $case->status->getLabel(),
            ...array_map(fn (string $field): string => (string) $case->answerLabel($field), array_keys(Grievance::OPTIONAL_ANSWERS))];
    }

    /**
     * A cell a spreadsheet would read as a formula is kept as text (CSV injection).
     */
    private static function harmless(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
