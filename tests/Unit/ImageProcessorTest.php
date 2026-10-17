<?php

namespace Tests\Unit;

use App\Support\ImageProcessor;
use PHPUnit\Framework\TestCase;

class ImageProcessorTest extends TestCase
{
    private function png(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        ob_start();
        imagepng($image);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        return $bytes;
    }

    public function test_it_converts_an_image_to_webp(): void
    {
        $webp = ImageProcessor::toWebp($this->png(100, 100), 1000, 1000);

        $this->assertNotNull($webp);
        $this->assertSame('image/webp', (string) (getimagesizefromstring($webp)['mime'] ?? ''));
    }

    public function test_it_resizes_down_within_the_maximum_bounds(): void
    {
        $webp = ImageProcessor::toWebp($this->png(2000, 1000), 800, 800);

        $this->assertNotNull($webp);

        $info = getimagesizefromstring($webp);
        $this->assertSame(800, $info[0]);
        $this->assertSame(400, $info[1]);
    }

    public function test_it_does_not_enlarge_small_images(): void
    {
        $webp = ImageProcessor::toWebp($this->png(120, 60), 800, 800);

        $this->assertNotNull($webp);

        $info = getimagesizefromstring($webp);
        $this->assertSame(120, $info[0]);
        $this->assertSame(60, $info[1]);
    }

    public function test_it_returns_null_for_invalid_input(): void
    {
        $this->assertNull(ImageProcessor::toWebp('not-an-image', 100, 100));
        $this->assertNull(ImageProcessor::toWebp('', 100, 100));
    }
}
