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
 * CSV or Excel export of the cases a user may see, received in a date range (N4).
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
     * The RFQ N4 column list in its order, with names and contacts after the reference when included.
     *
     * @return list<string>
     */
    private static function columns(bool $personal): array
    {
        return ['Reference', ...($personal ? ['Name', 'Phone', 'Email'] : []), 'Received', 'Closed', 'Channel', 'Category',
            'Location', 'Priority', 'Status', 'Complainant type', 'Sex', 'Age group', 'Ethnicity', 'Disability'];
    }

    /**
     * @return list<string>
     */
    private static function row(Grievance $case, bool $personal): array
    {
        // Demographic fields hold their codes until WP1 adds their enums.
        return [$case->reference, ...($personal ? [(string) $case->name, (string) $case->phone, (string) $case->email] : []),
            $case->received_at->format('Y-m-d H:i'), (string) $case->closed_at?->format('Y-m-d H:i'), $case->channel->getLabel(),
            (string) $case->category?->name_en, (string) $case->location?->name_en, (string) $case->priority?->getLabel(), $case->status->getLabel(),
            (string) $case->complainant_type, (string) $case->sex, (string) $case->age_group, (string) $case->ethnicity, (string) $case->disability];
    }

    /**
     * A cell a spreadsheet would read as a formula is kept as text (CSV injection).
     */
    private static function harmless(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
