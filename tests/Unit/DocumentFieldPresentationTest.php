<?php

namespace Tests\Unit;

use App\Support\DocumentFieldPresentation;
use PHPUnit\Framework\TestCase;

class DocumentFieldPresentationTest extends TestCase
{
    public function test_reusable_document_field_classes_are_scoped_and_direction_safe(): void
    {
        $itemCode = DocumentFieldPresentation::itemCode();
        $unit = DocumentFieldPresentation::unit();
        $money = DocumentFieldPresentation::money();
        $stock = DocumentFieldPresentation::stock();

        $this->assertStringContainsString('octram-readonly-box', $itemCode['class']);
        $this->assertStringContainsString('octram-item-code-box', $itemCode['class']);
        $this->assertSame('ltr', $itemCode['dir']);
        $this->assertStringContainsString('unicode-bidi: isolate', $itemCode['style']);
        $this->assertStringContainsString('octram-unit-box', $unit['class']);
        $this->assertStringContainsString('octram-money-box', $money['class']);
        $this->assertStringContainsString('octram-stock-box', $stock['class']);
        $this->assertSame(['class' => 'octram-centered-entry'], DocumentFieldPresentation::wrapper());
    }

    public function test_shared_styles_have_no_unscoped_filament_or_width_overrides(): void
    {
        $styles = file_get_contents(dirname(__DIR__, 2).'/resources/views/filament/styles/sales-quotation-entries.blade.php');

        $this->assertStringContainsString('.octram-readonly-box', $styles);
        $this->assertStringContainsString('.octram-item-code-box', $styles);
        $this->assertStringContainsString('min-height: 2.5rem', $styles);
        $this->assertStringContainsString('white-space: nowrap', $styles);
        $this->assertStringContainsString('word-break: normal', $styles);
        $this->assertStringNotContainsString('max-width', $styles);
        $this->assertDoesNotMatchRegularExpression('/(^|})\s*\.fi-/m', $styles);
    }
}
