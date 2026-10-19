<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class MerchandiseImageStorage
{
    /**
     * Simpan gambar merchandise ke disk `merchandise` dan kembalikan nama berkasnya.
     * Gambar diperkecil dan diubah ke WebP; gambar lama (bila ada) dihapus agar
     * tidak menumpuk. Bila konversi WebP gagal, berkas asli tetap disimpan.
     */
    public static function store(UploadedFile $file, ?string $previous = null): string
    {
        $diskName = (string) config('merchandise.disk', 'merchandise');
        $disk = Storage::disk($diskName);

        if ($previous !== null && $previous !== '') {
            $disk->delete($previous);
        }

        $webp = ImageProcessor::toWebp((string) $file->get(), 800, 800);

        if ($webp !== null) {
            $name = Str::uuid()->toString().'.webp';
            $disk->put($name, $webp);

            return $name;
        }

        $name = Str::uuid()->toString().'.'.strtolower((string) $file->extension());
        $file->storeAs('/', $name, ['disk' => $diskName]);

        return $name;
    }

    public static function delete(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        try {
            Storage::disk((string) config('merchandise.disk', 'merchandise'))->delete($path);
        } catch (Throwable) {
            // Berkas mungkin sudah hilang — abaikan.
        }
    }
}
