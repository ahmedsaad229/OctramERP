<?php

namespace App\Filament\Pages;

use App\Enums\TaxType;
use App\Models\CompanySetting;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class CompanySettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'إعدادات الشركة';

    protected static ?string $title = 'إعدادات الشركة';

    protected static string|\UnitEnum|null $navigationGroup = 'الإعدادات';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.company-settings';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $settings = CompanySetting::current();
        $this->form->fill([
            'company_name' => $settings->company_name,
            'default_tax_type' => $settings->default_tax_type?->value ?? TaxType::Vat14->value,
            'logo_path' => $settings->logo_path,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('بيانات الشركة')
                    ->description('تُستخدم هذه البيانات في المستندات التجارية.')
                    ->icon('heroicon-o-building-office-2')
                    ->schema([
                        TextInput::make('company_name')
                            ->label('اسم الشركة')
                            ->required()
                            ->maxLength(255)
                            ->helperText('أدخل الاسم التجاري الذي سيظهر في عروض الأسعار والمستندات المطبوعة.'),
                        Select::make('default_tax_type')
                            ->label('نوع الضريبة الافتراضي')
                            ->options([
                                TaxType::Vat14->value => 'ضريبة قيمة مضافة 14%',
                                TaxType::None->value => 'بدون ضريبة',
                            ])
                            ->default(TaxType::Vat14->value)
                            ->required()
                            ->native(false)
                            ->helperText('سيتم تطبيق هذا الاختيار تلقائيًا على المستندات الجديدة، ويمكن تغييره داخل كل مستند عند الحاجة.'),
                        FileUpload::make('logo_path')
                            ->label('شعار الشركة')
                            ->disk('public')
                            ->directory('company')
                            ->visibility('public')
                            ->image()
                            ->imagePreviewHeight('160')
                            ->acceptedFileTypes([
                                'image/png',
                                'image/jpeg',
                                'image/webp',
                            ])
                            ->maxSize(2048)
                            ->downloadable()
                            ->openable()
                            ->helperText('PNG أو JPG أو WEBP، بحد أقصى 2 ميجابايت.'),
                    ]),
            ]);
    }

    public function save(): void
    {
        $settings = CompanySetting::current();
        $oldLogo = $settings->logo_path;
        $data = $this->form->getState();
        $settings->update($data);

        if (filled($oldLogo) && $oldLogo !== $settings->logo_path) {
            Storage::disk('public')->delete($oldLogo);
        }

        Notification::make()
            ->success()
            ->title('تم حفظ إعدادات الشركة بنجاح.')
            ->send();
    }
}
