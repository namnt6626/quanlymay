<?php

namespace Tests\Unit;

use App\Services\ProfitAnalysis\ProfitAnalysisImportService;
use App\Services\ProfitAnalysis\SimpleXlsxReader;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ProfitAnalysisImportServiceTest extends TestCase
{
    public function test_it_normalizes_seller_sku_case_and_accents_before_grouping(): void
    {
        $service = new ProfitAnalysisImportService(new SimpleXlsxReader());
        $method = new ReflectionMethod($service, 'normalizeSellerSku');
        $method->setAccessible(true);

        $this->assertSame('QD81-DAI', $method->invoke($service, 'QD81-Dài'));
        $this->assertSame('QD81-DAI', $method->invoke($service, 'QD81-DÀI'));
        $this->assertSame('QD81-DAI', $method->invoke($service, 'QĐ81-DÀI'));
    }
}
