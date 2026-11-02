<?php

namespace App\Filament\Pages;

use BackedEnum;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * A QR code to the public form for posters (P10), for every role: print the poster, or download the
 * code for the company's own designs. It points at the site root, which opens the form in the
 * visitor's last language, Nepali the first time.
 */
class QrPoster extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'QR poster';

    protected string $view = 'filament.pages.qr-poster';

    /**
     * The code as a PNG image, for download.
     */
    public static function png(): string
    {
        return (new QRCode(self::options(QROutputInterface::GDIMAGE_PNG, base64: false)))->render(url('/'));
    }

    /**
     * The code as SVG, which prints sharp at any size.
     */
    public function svgDataUri(): string
    {
        return (new QRCode(self::options(QROutputInterface::MARKUP_SVG, base64: true)))->render(url('/'));
    }

    public function formUrl(): string
    {
        return url('/');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')->label('Print poster')->icon('heroicon-o-printer')->alpineClickHandler('window.print()'),
            Action::make('download')->label('Download QR code (PNG)')->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->url('data:image/png;base64,'.base64_encode(self::png()))
                ->extraAttributes(['download' => 'grievance-form-qr.png']),
        ];
    }

    /**
     * Error correction M: a poster can be creased, scuffed or partly covered and still scan.
     */
    private static function options(string $type, bool $base64): QROptions
    {
        return new QROptions(['outputType' => $type, 'outputBase64' => $base64, 'eccLevel' => EccLevel::M, 'scale' => 12]);
    }
}
