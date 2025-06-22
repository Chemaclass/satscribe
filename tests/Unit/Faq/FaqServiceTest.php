<?php

declare(strict_types=1);

namespace Tests\Unit\Faq;

use Illuminate\Support\Collection;
use Modules\Faq\Application\FaqService;
use Modules\Faq\Domain\Repository\FaqRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class FaqServiceTest extends TestCase
{
    public function test_get_faq_data_returns_result(): void
    {
        $faqs = new Collection([(object) ['question' => 'q1']]);

        $repo = $this->createStub(FaqRepositoryInterface::class);
        $repo->method('getCollectionBySearch')->willReturn($faqs);
        $repo->method('getCategories')->with($faqs)->willReturn(new Collection(['cat1']));

        $service = new FaqService($repo);

        $result = $service->getFaqData('foo');

        $this->assertSame('foo', $result['search']);
        $this->assertSame($faqs, $result['faqs']);
        $this->assertEquals(new Collection(['cat1']), $result['categories']);
    }

    public function test_get_faq_data_throws_not_found_when_empty(): void
    {
        $repo = $this->createStub(FaqRepositoryInterface::class);
        $repo->method('getCollectionBySearch')->willReturn(new Collection());

        $service = new FaqService($repo);

        $this->expectException(NotFoundHttpException::class);
        $service->getFaqData('bar');
    }
}
