<?php

namespace App\Filament\Pages;

use App\Enums\TaxType;
use App\Models\CompanySetting;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class CompanySettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon =
        'heroicon-o-building-office-2';

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
            'default_tax_type' => $settings->default_tax_type?->value
                ?? TaxType::Vat14->value,
            'logo_path' => $settings->logo_path,
            'default_sales_quotation_terms' =>
                $settings->default_sales_quotation_terms,
            'address' => $settings->address,
            'phone' => $settings->phone,
            'mobile' => $settings->mobile,
            'email' => $settings->email,
            'website' => $settings->website,
            'commercial_registry' => $settings->commercial_registry,
            'tax_number' => $settings->tax_number,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('هوية الشركة')
                    ->description(
                        'تظهر هذه البيانات في جميع المستندات والتقارير المطبوعة.'
                    )
                    ->icon('heroicon-o-building-office-2')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('company_name')
                                    ->label('اسم الشركة')
                                    ->required()
                                    ->maxLength(255),

                                Select::make('default_tax_type')
                                    ->label('نوع الضريبة الافتراضي')
                                    ->options([
                                        TaxType::Vat14->value =>
                                            'ضريبة قيمة مضافة 14%',
                                        TaxType::None->value =>
                                            'بدون ضريبة',
                                    ])
                                    ->default(TaxType::Vat14->value)
                                    ->required()
                                    ->native(false),
                            ]),

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
                            ->helperText(
                                'يفضل استخدام شعار PNG بخلفية شفافة.'
                            ),
                    ]),

                Section::make('بيانات الاتصال')
                    ->icon('heroicon-o-phone')
                    ->schema([
                        Textarea::make('address')
                            ->label('العنوان')
                            ->rows(2)
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('phone')
                                    ->label('التليفون الأرضي')
                                    ->tel()
                                    ->maxLength(50),

                                TextInput::make('mobile')
                                    ->label('رقم الموبايل')
                                    ->tel()
                                    ->maxLength(50),

                                TextInput::make('email')
                                    ->label('البريد الإلكتروني')
                                    ->email()
                                    ->maxLength(255),

                                TextInput::make('website')
                                    ->label('الموقع الإلكتروني')
                                    ->maxLength(255),
                            ]),
                    ]),

                Section::make('البيانات القانونية')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('commercial_registry')
                                    ->label('رقم السجل التجاري')
                                    ->maxLength(100),

                                TextInput::make('tax_number')
                                    ->label('الرقم الضريبي')
                                    ->maxLength(100),
                            ]),
                    ]),
                Section::make('إعدادات عروض الأسعار')
                    ->description(
                        'تُضاف هذه الشروط تلقائيًا إلى عروض الأسعار الجديدة فقط.'
                    )
                    ->icon('heroicon-o-document-check')
                    ->schema([
                        Textarea::make(
                            'default_sales_quotation_terms'
                        )
                            ->label(
                                'الشروط والأحكام الافتراضية'
                            )
                            ->rows(8)
                            ->placeholder(
                                "مثال:\n"
                                ."1- صلاحية العرض 15 يومًا.\n"
                                ."2- مدة التوريد طبقًا للاتفاق.\n"
                                ."3- الأسعار لا تشمل التركيب إلا إذا ذُكر خلاف ذلك."
                            )
                            ->helperText(
                                'يمكن تعديل هذه الشروط داخل أي عرض سعر، دون تغيير الإعداد الافتراضي أو العروض القديمة.'
                            )
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function save(): void
    {
        $settings = CompanySetting::current();
        $oldLogo = $settings->logo_path;
        $data = $this->form->getState();

        $settings->update($data);

        if (
            filled($oldLogo)
            && $oldLogo !== $settings->logo_path
        ) {
            Storage::disk('public')->delete($oldLogo);
        }

        Notification::make()
            ->success()
            ->title('تم حفظ إعدادات الشركة بنجاح.')
            ->send();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasPermission('company_settings.view') === true;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('company_settings.view') === true;
    }
}
