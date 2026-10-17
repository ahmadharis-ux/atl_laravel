<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactController extends Controller
{
    /**
     * Batas jumlah baris yang diproses sekali import agar tetap ringan.
     */
    private const IMPORT_ROW_LIMIT = 1000;

    public function index(Request $request): View|JsonResponse
    {
        $contacts = Contact::query()
            ->where('user_id', $request->user()->getKey())
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim((string) $request->query('q'))).'%';
                $query->where(function ($q) use ($term): void {
                    $q->where('name', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $data = [
            'contacts' => $contacts,
            'listRoute' => route('contacts.index'),
        ];

        if ($request->ajax()) {
            return response()->json([
                'rows' => view('user.contact._rows', $data)->render(),
                'footer' => view('user.contact._footer', $data)->render(),
                'modals' => view('user.contact._modals', $data)->render(),
            ]);
        }

        return view('user.contact.index', $data);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $request->user()->contacts()->create($data);

        return back()->with('status', 'Kontak berhasil ditambahkan.');
    }

    public function update(Request $request, Contact $contact): RedirectResponse
    {
        $this->authorizeContact($request, $contact);

        $contact->update($this->validated($request, $contact));

        return back()->with('status', 'Kontak berhasil diperbarui.');
    }

    public function destroy(Request $request, Contact $contact): RedirectResponse
    {
        $this->authorizeContact($request, $contact);

        $contact->delete();

        return back()->with('status', 'Kontak berhasil dihapus.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:4096'],
        ], [
            'file.mimes' => 'Format berkas harus CSV atau Excel (XLSX/XLS).',
            'file.max' => 'Ukuran berkas maksimal 4MB.',
        ]);

        $rows = $this->readRows($request->file('file'));

        if ($rows === []) {
            return back()->withErrors(['file' => 'Berkas tidak berisi data yang bisa dibaca.']);
        }

        $userId = $request->user()->getKey();
        $imported = 0;
        $skipped = 0;

        DB::transaction(function () use ($rows, $userId, &$imported, &$skipped): void {
            foreach (array_slice($rows, 0, self::IMPORT_ROW_LIMIT) as $row) {
                $name = trim((string) ($row['name'] ?? ''));
                $phone = trim((string) ($row['phone'] ?? ''));

                if ($name === '' || $phone === '') {
                    $skipped++;

                    continue;
                }

                Contact::query()->updateOrCreate(
                    ['user_id' => $userId, 'phone' => $phone],
                    [
                        'name' => $name,
                        'email' => ($email = trim((string) ($row['email'] ?? ''))) !== '' ? $email : null,
                        'notes' => ($notes = trim((string) ($row['notes'] ?? ''))) !== '' ? $notes : null,
                    ],
                );

                $imported++;
            }
        });

        $over = max(0, count($rows) - self::IMPORT_ROW_LIMIT);
        $message = $imported.' kontak berhasil diimpor.';
        if ($skipped > 0) {
            $message .= ' '.$skipped.' baris dilewati (nama/nomor kosong).';
        }
        if ($over > 0) {
            $message .= ' '.$over.' baris melebihi batas '.self::IMPORT_ROW_LIMIT.' tidak diproses.';
        }

        return back()->with('status', $message);
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['nama', 'nomor_hp', 'email', 'catatan']);
            fputcsv($out, ['Budi Santoso', '081234567890', 'budi@example.com', 'Pelanggan setia']);
            fclose($out);
        }, 'template-kontak.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Contact $contact = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => [
                'required',
                'string',
                'max:30',
                Rule::unique('contacts')
                    ->where('user_id', $request->user()->getKey())
                    ->ignore($contact?->getKey()),
            ],
            'email' => ['nullable', 'email', 'max:150'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'phone.unique' => 'Nomor ini sudah ada di buku kontakmu.',
        ]);
    }

    private function authorizeContact(Request $request, Contact $contact): void
    {
        abort_unless($contact->user_id === $request->user()->getKey(), 404);
    }

    /**
     * Baca berkas CSV/Excel menjadi array baris berisi name/phone/email/notes.
     *
     * @return array<int, array<string, string>>
     */
    private function readRows(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheetRows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        if ($sheetRows === []) {
            return [];
        }

        $header = array_shift($sheetRows);
        $map = [];
        foreach ($header as $index => $label) {
            $key = $this->normalizeHeader((string) $label);
            if ($key !== null) {
                $map[$index] = $key;
            }
        }

        if ($map === []) {
            return [];
        }

        $rows = [];
        foreach ($sheetRows as $sheetRow) {
            $row = [];
            foreach ($map as $index => $key) {
                $row[$key] = trim((string) ($sheetRow[$index] ?? ''));
            }

            if (implode('', $row) === '') {
                continue;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    private function normalizeHeader(string $label): ?string
    {
        $key = strtolower(trim($label));
        $key = str_replace([' ', '-'], '_', $key);

        return match ($key) {
            'nama', 'name', 'nama_kontak' => 'name',
            'nomor_hp', 'no_hp', 'nomor', 'telepon', 'telp', 'hp', 'phone', 'phone_number' => 'phone',
            'email', 'surel' => 'email',
            'catatan', 'keterangan', 'notes', 'note' => 'notes',
            default => null,
        };
    }
}
