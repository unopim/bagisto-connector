<?php

namespace Webkul\Bagisto\Tests\Unit;

use Mockery;
use Psr\Log\LoggerInterface;
use Tests\TestCase;
use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Bagisto\Helpers\Exporters\Product\Exporter;
use Webkul\Bagisto\Repositories\AttributeMappingRepository;
use Webkul\Bagisto\Repositories\BagistoDataMapping;
use Webkul\Bagisto\Repositories\CredentialRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\DataTransfer\Helpers\Sources\Export\ProductSource;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\Product\Repositories\ProductRepository;

class ExporterTest extends TestCase
{
    private $exporter;

    private $jobLogger;

    protected function setUp(): void
    {
        parent::setUp();

        $batchRepo = Mockery::mock(JobTrackBatchRepository::class);
        $fileBuffer = Mockery::mock(FlatItemBuffer::class);
        $bagistoMapping = Mockery::mock(BagistoDataMapping::class);
        $attrRepo = Mockery::mock(AttributeRepository::class);
        $prodRepo = Mockery::mock(ProductRepository::class);
        $catRepo = Mockery::mock(CategoryRepository::class);
        $attrOptionRepo = Mockery::mock(AttributeOptionRepository::class);
        $attrMappingRepo = Mockery::mock(AttributeMappingRepository::class);
        $channelRepo = Mockery::mock(ChannelRepository::class);
        $credentialRepo = Mockery::mock(CredentialRepository::class);
        $productSource = Mockery::mock(ProductSource::class);

        $this->exporter = new Exporter(
            $batchRepo,
            $fileBuffer,
            $bagistoMapping,
            $attrRepo,
            $prodRepo,
            $catRepo,
            $attrOptionRepo,
            $attrMappingRepo,
            $channelRepo,
            $credentialRepo,
            $productSource
        );

        $this->jobLogger = Mockery::mock(LoggerInterface::class);
        $this->exporter->setLogger($this->jobLogger);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_exporter_instantiation()
    {
        $this->assertInstanceOf(Exporter::class, $this->exporter);
    }

    public function test_apply_fixed_values_defaults_visible_individually_for_variant()
    {
        $this->setProperty($this->exporter, 'mappingAttributes', [
            'standard_attribute' => (object) ['fixed_value' => ['status' => '1']],
        ]);

        $mergedFields = [];
        $method = new \ReflectionMethod($this->exporter, 'applyFixedValues');
        $method->setAccessible(true);
        $method->invokeArgs($this->exporter, [&$mergedFields, ['sku' => 'parent-sku']]);

        $this->assertSame('1', $mergedFields['visible_individually']);
    }

    public function test_apply_fixed_values_sets_visible_individually_for_simple_product()
    {
        $this->setProperty($this->exporter, 'mappingAttributes', [
            'standard_attribute' => (object) ['fixed_value' => ['status' => '1']],
        ]);

        $mergedFields = [];
        $method = new \ReflectionMethod($this->exporter, 'applyFixedValues');
        $method->setAccessible(true);
        $method->invokeArgs($this->exporter, [&$mergedFields, null]);

        $this->assertSame('1', $mergedFields['visible_individually']);
    }

    private function setProperty(object $object, string $property, $value): void
    {
        $ref = new \ReflectionProperty($object, $property);
        $ref->setAccessible(true);
        $ref->setValue($object, $value);
    }
}
