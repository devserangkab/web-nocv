<?php

namespace App\Actions\Documents;

use App\Actions\Activities\RecordActivity;
use App\Enums\DocumentCategory;
use App\Models\Application;
use App\Models\Document;
use App\Models\Location;
use App\Models\Nas;
use App\Models\Panel;
use App\Models\Proxmox;
use App\Models\Server;
use App\Models\Vms;
use App\Support\Authorize;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class StoreDocument
{
    /**
     * Simpan dokumen (pengadaan, desain, instalasi, deployment, SOP, dll)
     * dengan keterkaitan ke entitas infrastruktur/application (polymorphic).
     *
     * @param  array<string, mixed>  $data
     */
    public function __invoke(?Model $documentable, array $data, ?int $addedBy = null): Document
    {
        Authorize::documentsCreate();

        $data = Validator::make($data, static::rules(), static::messages())->validate();

        // Set aplikasi lama tetap terisi agar relasi historis tidak hilang
        $applicationId = $data['application_id'] ?? null;

        $attributes = [
            'name' => $data['name'],
            'category' => filled($data['category'] ?? null) ? $data['category'] : null,
            'application_id' => filled($applicationId) ? $applicationId : null,
            'added_by' => $addedBy,
        ];

        if ($documentable !== null) {
            $attributes['documentable_type'] = $documentable->getMorphClass();
            $attributes['documentable_id'] = $documentable->id;
        }

        if (! blank($data['file'])) {
            /** @var UploadedFile $file */
            $file = $data['file'];
            $attributes['document'] = $file->store('documents', 'private');
        }

        $document = Document::create($attributes);

        app(RecordActivity::class)->audit($document, 'Menambahkan dokumen '.$document->name);

        return $document;
    }

    /**
     * Daftar tipe entitas yang valid sebagai target dokumen.
     *
     * @return list<class-string<Model>>
     */
    public static function allowedTypes(): array
    {
        return [
            Application::class,
            Location::class,
            Rack::class,
            Server::class,
            Proxmox::class,
            Vms::class,
            Panel::class,
            Waf::class,
            Nas::class,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'max:255'],
            'category' => ['nullable', Rule::enum(DocumentCategory::class)],
            'application_id' => ['nullable', 'exists:applications,application_id'],
            'file' => ['required', function (string $attribute, mixed $value, $fail) {
                if ($value instanceof UploadedFile) {
                    // mime perlu di-guess dari ekstensi karena file bebas type kecuali daftar aman
                    $check = Validator::make(
                        ['file' => $value],
                        ['file' => ['extensions:pdf,doc,docx,xls,xlsx,ppt,pptx,md,txt,csv,png,jpg,jpeg,webp', 'max:20480']],
                        [
                            'file.extensions' => 'Format file tidak didukung (pdf, docx, xlsx, ppt, md, txt, csv, png, jpg, webp).',
                            'file.max' => 'Ukuran file maksimal 20 MB.',
                        ],
                    );

                    if ($check->fails()) {
                        $fail((string) $check->errors()->first('file'));
                    }
                }
            }],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'name.required' => 'Nama dokumen wajib diisi.',
            'name.max' => 'Nama dokumen maksimal 255 karakter.',
            'application_id.exists' => 'Application tidak ditemukan.',
            'file.required' => 'File dokumen wajib diunggah.',
        ];
    }
}
